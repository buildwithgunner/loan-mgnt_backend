<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\Document;
use App\Models\Notification;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Get dashboard summary (stats + recent apps).
     */
    public function summary(Request $request)
    {
        $user = $request->user();

        // Handle case where Admin accesses user dashboard (Admins don't have applications)
        if ($user instanceof \App\Models\Admin || !method_exists($user, 'applications')) {
            return response()->json([
                'user' => $user,
                'stats' => [
                    'total_applications' => 0,
                    'approved_count' => 0,
                    'active_count' => 0,
                    'total_requested' => '$0.00',
                    'approved_amount' => '$0.00',
                    'repaid_amount' => '$0.00',
                    'available_balance' => '$0.00',
                ],
                'recent_applications' => [],
            ]);
        }

        // Calculate totals in PHP for database compatibility (SQLite vs MySQL)
        $allApps = $user->applications()->get();
        
        $totalRequested = $allApps->reduce(function ($carry, $app) {
            $numeric = (float) preg_replace('/[^0-9.]/', '', $app->amount ?? '0');
            return $carry + $numeric;
        }, 0);

        $approvedAmount = $allApps->where('status', 'approved')->reduce(function ($carry, $app) {
            $numeric = (float) preg_replace('/[^0-9.]/', '', $app->amount ?? '0');
            return $carry + $numeric;
        }, 0);

        $stats = [
            'total_applications' => $allApps->count(),
            'approved_count' => $allApps->where('status', 'approved')->count(),
            'active_count' => $allApps->whereIn('status', ['pending', 'under_review', 'approved', 'credited', 'disbursed'])->count(),
            'total_requested' => '$' . number_format((float)$totalRequested, 2),
            'approved_amount' => '$' . number_format((float)$approvedAmount, 2),
            'repaid_amount' => '$' . number_format((float)($user->repaid_amount ?? 0), 2),
            'available_balance' => (string)($user->available_balance ?? '$0.00'),
        ];

        $recent_applications = $user->applications()
            ->latest()
            ->take(5)
            ->get();

        return response()->json([
            'user' => $user,
            'stats' => $stats,
            'recent_applications' => $recent_applications,
        ]);
    }

    /**
     * Get all applications.
     */
    public function applications(Request $request)
    {
        $applications = $request->user()->applications()->with('documents')->latest()->get()->map(function ($app) {
            return [
                'id' => $app->id,
                'type' => $app->type,
                'property' => $app->property,
                'amount' => '$' . number_format((float)str_replace(['$', ','], '', $app->amount), 2),
                'status' => $app->status,
                'ltv' => $app->ltv,
                'processing_stage' => $app->processing_stage ?? 'Pending Review',
                'processing_level' => $app->processing_level ?? 0,
                'approval_code' => $app->approval_code,
                'tracking_code' => $app->tracking_code,
                'codes_requested' => (bool) $app->codes_requested,
                'bank_name' => $app->bank_name,
                'account_name' => $app->account_name,
                'account_number' => $app->account_number,
                'documents' => $app->documents,
                'created_at' => $app->created_at,
            ];
        });
        return response()->json($applications);
    }

    /**
     * Get all documents.
     */
    public function documents(Request $request)
    {
        return response()->json($request->user()->documents()->latest()->get());
    }

    /**
     * Get all notifications.
     */
    public function notifications(Request $request)
    {
        return response()->json($request->user()->notifications()->latest()->get());
    }

    /**
     * Mark notification as read.
     */
    public function markNotificationRead(Request $request, $id)
    {
        $notification = $request->user()->notifications()->findOrFail($id);
        $notification->update(['read_at' => now()]);

        return response()->json(['success' => true]);
    }

    /**
     * Submit a new loan request.
     */
    public function submitApplication(Request $request)
    {
        $request->validate([
            'type' => 'required|string',
            'property' => 'required|string',
            'amount' => 'required|string',
            'guarantor_name' => 'nullable|string',
            'guarantor_phone' => 'nullable|string',
            'guarantor_email' => 'nullable|string|email',
            'guarantor_address' => 'nullable|string',
            'form_data' => 'nullable|array',
        ]);
 
        $application = $request->user()->applications()->create([
            'type' => $request->type,
            'property' => $request->property,
            'amount' => $request->amount,
            'status' => 'pending',
            'ltv' => 'TBD',
            'guarantor_name' => $request->guarantor_name,
            'guarantor_phone' => $request->guarantor_phone,
            'guarantor_email' => $request->guarantor_email,
            'guarantor_address' => $request->guarantor_address,
            'form_data' => $request->form_data,
        ]);

        return response()->json($application);
    }

    /**
     * Upload a document for a specific application.
     */
    public function uploadDocument(Request $request, $id)
    {
        \Log::info("Document upload attempt for App ID: $id by User ID: " . $request->user()->id);
        
        $application = $request->user()->applications()->where('id', $id)->first();

        if (!$application) {
            \Log::warning("Application $id not found for user " . $request->user()->id);
            return response()->json(['message' => 'Resource Protocol Not Found'], 404);
        }

        $request->validate([
            'document' => 'required|file|max:10240', // 10MB max
            'category' => 'required|string'
        ]);

        $file = $request->file('document');
        
        // Securely store the file using Laravel storage
        $path = $file->store('documents/' . $application->id, 'public');

        $document = $request->user()->documents()->create([
            'application_id' => $application->id,
            'name' => $file->getClientOriginalName(),
            'path' => $path,
            'category' => $request->category,
            'type' => $file->getClientOriginalExtension(),
            'size' => round($file->getSize() / 1024 / 1024, 2) . ' MB',
        ]);

        return response()->json($document);
    }

    /**
     * Get all referrals for the user.
     */
    public function referrals(Request $request)
    {
        $user = $request->user();
        $referrals = $user->referrals()->latest()->get();
        $totalEarned = $user->referrals()->where('status', 'successful')->sum('bonus_amount');
        $successRate = $referrals->count() > 0 
            ? round(($user->referrals()->where('status', 'successful')->count() / $referrals->count()) * 100) 
            : 0;

        return response()->json([
            'referrals' => $referrals,
            'stats' => [
                'total_count' => $referrals->count(),
                'total_earned' => '$' . number_format((float)$totalEarned, 2),
                'success_rate' => $successRate . '%',
            ]
        ]);
    }

    /**
     * Request tracking and approval codes.
     */
    public function requestCodes(Request $request, $id)
    {
        $user = $request->user();
        if (method_exists($user, 'applications')) {
            $application = $user->applications()->where('id', $id)->first()
                           ?? Application::findOrFail($id);
        } else {
            $application = Application::findOrFail($id);
        }

        if ($application->approval_code && $application->tracking_code) {
            return response()->json(['message' => 'Codes are already generated'], 400);
        }

        $application->codes_requested = true;
        $application->save();

        return response()->json(['message' => 'Codes requested successfully']);
    }

    /**
     * Update bank details for withdrawal.
     */
    public function updateBankDetails(Request $request, $id)
    {
        $request->validate([
            'bank_name'      => 'required|string|max:255',
            'account_name'   => 'required|string|max:255',
            'account_number' => 'required|string|max:255',
        ]);

        // Look up by ID directly — the route is already auth-protected.
        // Scope to the authenticated user's applications where possible.
        $user = $request->user();
        if (method_exists($user, 'applications')) {
            $application = $user->applications()->where('id', $id)->first()
                           ?? Application::findOrFail($id);
        } else {
            $application = Application::findOrFail($id);
        }

        $application->update($request->only(['bank_name', 'account_name', 'account_number']));

        return response()->json([
            'message'     => 'Bank details updated successfully',
            'application' => $application,
        ]);
    }

    /**
     * Verify approval and tracking codes submitted by the user.
     */
    public function verifyStageCodes(Request $request, $id)
    {
        $request->validate([
            'approval_code'  => 'required|string',
            'tracking_code'  => 'required|string',
        ]);

        $application = $request->user()->applications()->findOrFail($id);

        $approvalMatch  = $application->approval_code  === $request->approval_code;
        $trackingMatch  = $application->tracking_code  === $request->tracking_code;

        if ($approvalMatch && $trackingMatch) {
            return response()->json([
                'verified' => true,
                'message'  => 'Codes verified successfully. Disbursement authorized.',
            ]);
        }

        return response()->json([
            'verified' => false,
            'message'  => 'One or both codes are invalid. Please check and retry.',
        ], 422);
    }

    /**
     * Request account activation from admin.
     */
    public function requestActivation(Request $request)
    {
        $user = $request->user();

        if ($user->is_active) {
            return response()->json(['message' => 'Your account is already active.'], 400);
        }

        if ($user->activation_requested) {
            return response()->json(['message' => 'You have already submitted an activation request. Please wait for admin approval.'], 400);
        }

        $user->activation_requested = true;
        $user->activation_requested_at = now();
        $user->save();

        return response()->json(['message' => 'Activation request submitted successfully. Admin will review shortly.']);
    }
}
