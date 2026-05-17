<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Application;
use App\Models\LoanDisbursement;
use Illuminate\Http\Request;

use App\Models\SiteSetting;

class AdminController extends Controller
{
    /**
     * Get all site settings.
     */
    public function getSettings()
    {
        $settings = SiteSetting::all()->pluck('value', 'key');
        return response()->json([
            'settings' => $settings
        ]);
    }

    /**
     * Update site settings.
     */
    public function updateSettings(Request $request)
    {
        $settings = $request->input('settings', []);
        
        foreach ($settings as $key => $value) {
            SiteSetting::updateOrCreate(['key' => $key], ['value' => $value]);
        }

        return response()->json([
            'message' => 'Settings updated successfully'
        ]);
    }

    /**
     * Get summary statistics for the admin dashboard.
     */
    public function getStats()
    {
        try {
            $totalUsers        = User::count();
            $activeApplications = Application::whereIn('status', ['pending', 'under_review'])->count();
            $pendingReview     = Application::where('status', 'pending')->count();
            $rejectedCount     = Application::where('status', 'rejected')->count();
            $totalApps         = Application::count();

            // Safely calculate total funded in PHP — handles any string format like "$200,000" or "$0"
            $fundedApps  = Application::whereIn('status', ['approved', 'credited', 'disbursed'])
                ->pluck('amount');
            $totalFunded = $fundedApps->reduce(function ($carry, $amount) {
                if (!$amount) return $carry;
                $numeric = (float) preg_replace('/[^0-9.]/', '', $amount);
                return $carry + $numeric;
            }, 0);

            // Monthly data for chart (Last 6 Months)
            $monthlyStats = [];
            for ($i = 5; $i >= 0; $i--) {
                $date   = now()->subMonths($i);
                $count  = Application::whereYear('created_at', $date->year)
                                     ->whereMonth('created_at', $date->month)
                                     ->count();
                $monthlyStats[] = ['label' => $date->format('M'), 'value' => $count];
            }

            // Loan types distribution
            $typeStats = Application::select('type', \DB::raw('count(*) as total'))
                ->groupBy('type')
                ->get()
                ->map(function ($item) use ($totalApps) {
                    return [
                        'label' => $item->type ?: 'Other',
                        'pct'   => $totalApps > 0 ? round(($item->total / $totalApps) * 100) : 0,
                    ];
                });

            // Format funded value
            $fundedLabel = $totalFunded >= 1000000
                ? '$' . number_format($totalFunded / 1000000, 1) . 'M'
                : '$' . number_format($totalFunded / 1000, 0) . 'K';

            return response()->json([
                'stats' => [
                    ['label' => 'Total Users',          'value' => number_format($totalUsers),   'change' => '+0%', 'up' => true,  'color' => 'text-blue-500',   'bg' => 'bg-blue-500/10'],
                    ['label' => 'Active Applications',  'value' => (string)$activeApplications,  'change' => '+0%', 'up' => true,  'color' => 'text-amber-500',  'bg' => 'bg-amber-500/10'],
                    ['label' => 'Loans Funded (Total)', 'value' => $fundedLabel,                 'change' => '+0%', 'up' => true,  'color' => 'text-emerald-500','bg' => 'bg-emerald-500/10'],
                    ['label' => 'Rejected Loans',       'value' => (string)$rejectedCount,        'change' => '+0%', 'up' => false, 'color' => 'text-red-500',    'bg' => 'bg-red-500/10'],
                ],
                'charts' => [
                    'monthly' => $monthlyStats,
                    'types'   => $typeStats,
                ]
            ]);
        } catch (\Exception $e) {
            \Log::error('getStats error: ' . $e->getMessage());
            
            // Specifically detect connection issues
            $isConnectionError = str_contains($e->getMessage(), 'Connection refused') || str_contains($e->getMessage(), 'SQLSTATE[HY000]');
            
            return response()->json([
                'error'   => 'Platform Intelligence Offline',
                'message' => $isConnectionError ? 'Database connection refused. Please ensure MySQL is running.' : $e->getMessage(),
                'type'    => $isConnectionError ? 'connection_error' : 'general_error'
            ], 500);
        }
    }

    /**
     * Get all loan applications with applicant details.
     */
    public function getApplications()
    {
        $applications = Application::with(['user', 'documents', 'disbursements'])->orderBy('created_at', 'desc')->get();

        return response()->json([
            'applications' => $applications->map(function ($app) {
                return [
                    'id' => $app->id,
                    'user' => $app->user ? $app->user->name : 'Unknown User',
                    'user_email' => $app->user ? $app->user->email : 'N/A',
                    'borrower' => [
                        'name' => $app->user ? $app->user->name : 'N/A',
                        'email' => $app->user ? $app->user->email : 'N/A',
                        'phone' => $app->user ? $app->user->phone : 'N/A',
                        'ssn' => $app->user ? $app->user->ssn : 'N/A',
                        'dob' => $app->user ? $app->user->dob : 'N/A',
                        'address' => $app->user ? (($app->user->address ?? '') . ' ' . ($app->user->city ?? '') . ' ' . ($app->user->state ?? '') . ' ' . ($app->user->zip_code ?? '')) : 'N/A',
                        'occupation' => $app->user ? $app->user->occupation : 'N/A',
                        'fico' => $app->user ? $app->user->estimated_fico : 'N/A',
                        'net_worth' => $app->user ? $app->user->estimated_net_worth : 'N/A',
                    ],
                    'type' => $app->type ?? 'N/A',
                    'property' => $app->property_address ?? $app->property ?? 'N/A', // Using fallback
                    'amount' => '$' . number_format((float)str_replace(['$', ','], '', $app->amount ?? '0')), // Safe formatting
                    'amount_raw' => $app->amount,
                    'ltv' => $app->ltv . '%',
                    'status' => $app->status,
                    'date' => $app->created_at->format('M d, Y'),
                    'processing_stage' => $app->processing_stage,
                    'processing_level' => $app->processing_level,
                    'approval_code' => $app->approval_code,
                    'tracking_code' => $app->tracking_code,
                    'codes_requested' => (bool) $app->codes_requested,
                    'guarantor' => [
                        'name' => $app->guarantor_name,
                        'phone' => $app->guarantor_phone,
                        'email' => $app->guarantor_email,
                        'address' => $app->guarantor_address,
                    ],
                    'documents' => $app->documents,
                    'disbursements' => $app->disbursements,
                    'bank_details' => [
                        'bank_name' => $app->bank_name,
                        'account_name' => $app->account_name,
                        'account_number' => $app->account_number,
                    ],
                    'form_data' => $app->form_data,
                ];
            })
        ]);
    }

    /**
     * Update the status of a loan application.
     */
    public function updateApplicationStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|string|in:pending,under_review,approved,rejected,credited,disbursed'
        ]);

        $application = Application::findOrFail($id);
        
        // Automatic record storage handler
        if ($request->status === 'disbursed' && $application->status !== 'disbursed') {
            LoanDisbursement::create([
                'application_id' => $application->id,
                'amount' => $application->amount,
            ]);
        }
        
        $application->status = $request->status;

        // Auto-generate code if approved and not exists
        if (in_array($request->status, ['approved', 'credited', 'disbursed']) && !$application->approval_code) {
            $application->approval_code = 'BWA-' . strtoupper(bin2hex(random_bytes(3)));
        }

        $application->save();

        return response()->json([
            'message' => 'Application status updated successfully',
            'application' => $application
        ]);
    }

    /**
     * Update processing stage and level.
     */
    public function updateApplicationProgress(Request $request, $id)
    {
        $request->validate([
            'processing_stage' => 'required|string|max:255',
            'processing_level' => 'required|integer|min:0|max:100',
        ]);

        $application = Application::findOrFail($id);
        $application->processing_stage = $request->processing_stage;
        $application->processing_level = $request->processing_level;
        $application->save();

        return response()->json([
            'message' => 'Progress updated successfully',
            'application' => $application
        ]);
    }

    /**
     * Manually generate or update approval code.
     */
    public function generateApprovalCode(Request $request, $id)
    {
        $application = Application::findOrFail($id);
        $application->approval_code = 'BWA-' . strtoupper(bin2hex(random_bytes(3)));
        $application->save();

        return response()->json([
            'message' => 'Approval code generated',
            'code' => $application->approval_code
        ]);
    }

    /**
     * Manually generate or update tracking/payment code.
     */
    public function generateTrackingCode(Request $request, $id)
    {
        $application = Application::findOrFail($id);
        $application->tracking_code = 'PAY-' . strtoupper(bin2hex(random_bytes(4)));
        $application->save();

        return response()->json([
            'message' => 'Tracking code generated',
            'code' => $application->tracking_code
        ]);
    }

    /**
     * Manually generate both codes and clear requested status.
     */
    public function generateBothCodes(Request $request, $id)
    {
        $application = Application::findOrFail($id);
        
        if (!$application->approval_code) {
            $application->approval_code = 'BWA-' . strtoupper(bin2hex(random_bytes(3)));
        }
        
        if (!$application->tracking_code) {
            $application->tracking_code = 'PAY-' . strtoupper(bin2hex(random_bytes(4)));
        }
        
        $application->codes_requested = false;
        $application->save();

        return response()->json([
            'message' => 'Both codes generated successfully',
            'approval_code' => $application->approval_code,
            'tracking_code' => $application->tracking_code
        ]);
    }

    /**
     * Update arbitrary loan application details.
     */
    public function updateApplication(Request $request, $id)
    {
        $application = Application::findOrFail($id);
        
        $request->validate([
            'type' => 'nullable|string|max:255',
            'property' => 'nullable|string|max:255',
            'amount' => 'nullable|string|max:255',
            'ltv' => 'nullable|integer|min:0|max:100',
            'guarantor_name' => 'nullable|string|max:255',
            'guarantor_phone' => 'nullable|string|max:255',
            'guarantor_email' => 'nullable|string|email|max:255',
            'guarantor_address' => 'nullable|string|max:255',
        ]);

        $application->update($request->all());

        return response()->json([
            'message' => 'Application updated successfully',
            'application' => $application
        ]);
    }

    /**
     * Get all registered users with application count and activation info.
     */
    public function getUsers()
    {
        $users = User::withCount('applications')->orderBy('created_at', 'desc')->get();

        return response()->json([
            'users' => $users
        ]);
    }

    /**
     * Activate a user account.
     */
    public function activateUser(Request $request, $id)
    {
        $user = User::findOrFail($id);
        $user->is_active = true;
        $user->activation_requested = false;
        $user->activated_at = now();
        $user->save();

        return response()->json([
            'message' => 'User account has been activated successfully.',
            'user'    => $user,
        ]);
    }

    /**
     * Deactivate a user account.
     */
    public function deactivateUser(Request $request, $id)
    {
        $user = User::findOrFail($id);
        $user->is_active = false;
        $user->activated_at = null;
        $user->save();

        return response()->json([
            'message' => 'User account has been deactivated.',
            'user'    => $user,
        ]);
    }

    /**
     * Update a user's details.
     */
    public function updateUser(Request $request, $id)
    {
        $user = User::findOrFail($id);
        
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $id,
            'phone' => 'nullable|string|max:20',
            'role' => 'required|string|in:user,admin',
        ]);

        $user->update($request->only(['name', 'email', 'phone', 'role']));

        return response()->json([
            'message' => 'User updated successfully',
            'user' => $user
        ]);
    }

    /**
     * Get a specific user's profile with all related data.
     */
    public function getUserProfile($id)
    {
        $user = User::with(['documents', 'applications.documents', 'applications.disbursements'])->findOrFail($id);
        
        return response()->json([
            'user' => $user
        ]);
    }

    /**
     * Delete a user.
     */
    public function deleteUser($id)
    {
        $user = User::findOrFail($id);
        $user->delete();

        return response()->json([
            'message' => 'User deleted successfully'
        ]);
    }

    /**
     * Get all interested leads.
     */
    public function getLeads()
    {
        return response()->json([
            'leads' => \App\Models\Lead::orderBy('created_at', 'desc')->get()
        ]);
    }

    /**
     * Create a new lead and generate an interest code.
     */
    public function createLead(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:255',
            'loan_type' => 'nullable|string|max:255',
        ]);

        $lead = \App\Models\Lead::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'loan_type' => $request->loan_type,
            'interest_code' => 'LIT-' . strtoupper(bin2hex(random_bytes(3))),
            'status' => 'interested',
        ]);

        return response()->json([
            'message' => 'Lead created and code generated successfully',
            'lead' => $lead
        ]);
    }

    /**
     * Publicly submit a lead from the contact form.
     */
    public function publicSubmitLead(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:255',
            'purpose' => 'required|string',
        ]);

        $lead = \App\Models\Lead::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'purpose' => $request->purpose,
            'interest_code' => 'LIT-' . strtoupper(bin2hex(random_bytes(3))),
            'status' => 'new_inquiry',
        ]);

        return response()->json([
            'message' => 'Your inquiry has been received. Our team will contact you shortly.',
            'lead' => $lead
        ]);
    }
}
