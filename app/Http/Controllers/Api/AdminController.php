<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BorrowerDocument;
use App\Models\Guarantor;
use App\Models\Loan;
use App\Models\LoanApplication;
use App\Models\NotificationLog;
use App\Models\Repayment;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AdminController extends Controller
{
    public function overview()
    {
        $this->seedIfEmpty();

        $totalBorrowers = User::count();
        $activeLoans = Loan::whereIn('status', ['Active', 'Overdue'])->count();
        $pendingApplications = LoanApplication::where('status', 'Pending')->count();
        $overduePayments = Repayment::where('status', 'Unpaid')->count();
        $totalDisbursed = (float) Loan::sum('amount');
        $totalRepayments = (float) Repayment::sum('paid_amount');

        return response()->json([
            'cards' => [
                'totalBorrowers' => $totalBorrowers,
                'activeLoans' => $activeLoans,
                'pendingApplications' => $pendingApplications,
                'overduePayments' => $overduePayments,
                'totalDisbursed' => $totalDisbursed,
                'totalRepayments' => $totalRepayments,
                'monthlyProfit' => round($totalRepayments * 0.18, 2),
            ],
        ]);
    }

    public function borrowers()
    {
        $this->seedIfEmpty();

        $borrowers = User::with(['documents', 'loans.guarantor'])->get()->map(function ($user) {
            $latestLoan = $user->loans->first();
            return [
                'id' => 'B-' . (1000 + $user->id),
                'rawId' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'blacklisted' => (bool) $user->is_blacklisted,
                'suspended' => (bool) $user->is_suspended,
                'documents' => $user->documents->pluck('type')->values(),
                'loanHistory' => $user->loans->map(fn($loan) => [
                    'id' => 'LN-' . (700 + $loan->id),
                    'principal' => (float) $loan->amount,
                    'status' => $loan->status,
                ])->values(),
                'guarantor' => $latestLoan?->guarantor ? [
                    'name' => $latestLoan->guarantor->name,
                    'phone' => $latestLoan->guarantor->phone,
                ] : null,
            ];
        });

        return response()->json($borrowers);
    }

    public function updateBorrower(Request $request, int $id)
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'is_blacklisted' => 'sometimes|boolean',
            'is_suspended' => 'sometimes|boolean',
        ]);

        $user->update($validated);

        return response()->json(['message' => 'Borrower updated successfully']);
    }

    public function applications()
    {
        $this->seedIfEmpty();

        $applications = LoanApplication::with(['user', 'guarantor', 'user.documents'])->latest()->get()->map(function ($app) {
            return [
                'id' => 'APP-' . (100 + $app->id),
                'rawId' => $app->id,
                'borrower' => $app->user?->name,
                'amount' => (float) $app->amount,
                'duration' => $app->duration,
                'purpose' => $app->purpose,
                'status' => $app->status,
                'guarantor' => $app->guarantor?->name,
                'documents' => $app->user?->documents?->pluck('type')->values() ?? [],
            ];
        });

        return response()->json($applications);
    }

    public function updateApplication(Request $request, int $id)
    {
        $application = LoanApplication::findOrFail($id);

        $validated = $request->validate([
            'status' => 'required|string|in:Pending,Approved,Rejected,More Info Requested,Edited Terms',
            'duration' => 'nullable|string',
            'amount' => 'nullable|numeric|min:1',
        ]);

        $application->update($validated);

        return response()->json(['message' => 'Application updated']);
    }

    public function activeLoans()
    {
        $this->seedIfEmpty();

        $loans = Loan::with(['user', 'guarantor'])->get()->map(function ($loan) {
            return [
                'id' => 'LN-' . (700 + $loan->id),
                'rawId' => $loan->id,
                'borrower' => $loan->user?->name,
                'principal' => (float) $loan->amount,
                'outstanding' => (float) $loan->outstanding_balance,
                'nextDue' => optional($loan->next_payment_date)->format('Y-m-d'),
                'status' => $loan->status,
                'disbursed' => (bool) $loan->is_disbursed,
                'disbursedDate' => optional($loan->disbursed_at)->format('Y-m-d'),
                'disbursementMethod' => $loan->disbursement_method,
                'transferRef' => $loan->transfer_reference,
                'progressStage' => $loan->progress_stage,
                'progressLevel' => (int) $loan->progress_level,
                'approvalCode' => $loan->approval_code,
                'trackingCode' => $loan->tracking_code,
                'codesRequested' => (bool) $loan->codes_requested,
            ];
        });

        return response()->json($loans);
    }

    public function updateLoanProgress(Request $request, int $id)
    {
        $loan = Loan::findOrFail($id);

        $validated = $request->validate([
            'progress_stage' => 'required|string|max:255',
            'progress_level' => 'required|integer|min:0|max:100',
        ]);

        $loan->update([
            'progress_stage' => $validated['progress_stage'],
            'progress_level' => $validated['progress_level'],
        ]);

        return response()->json(['message' => 'Loan progress updated successfully']);
    }

    public function generateCodes(Request $request, int $id)
    {
        $loan = Loan::findOrFail($id);

        $loan->update([
            'approval_code' => 'BWA-' . strtoupper(bin2hex(random_bytes(3))),
            'tracking_code' => 'TRK-' . strtoupper(bin2hex(random_bytes(3))),
            'codes_requested' => false,
        ]);

        return response()->json(['message' => 'Codes generated successfully']);
    }

    public function disburseLoan(Request $request, int $id)
    {
        $loan = Loan::findOrFail($id);

        $validated = $request->validate([
            'disbursement_method' => 'required|string',
            'transfer_reference' => 'required|string',
            'disbursed_at' => 'nullable|date',
        ]);

        $loan->update([
            'is_disbursed' => true,
            'status' => 'Active',
            'disbursement_method' => $validated['disbursement_method'],
            'transfer_reference' => $validated['transfer_reference'],
            'disbursed_at' => $validated['disbursed_at'] ?? now(),
            'start_date' => $loan->start_date ?? now(),
            'next_payment_date' => $loan->next_payment_date ?? now()->addMonth(),
        ]);

        return response()->json(['message' => 'Loan disbursed']);
    }

    public function repayments()
    {
        $this->seedIfEmpty();

        $repayments = Repayment::with('loan')->get()->map(function ($repayment) {
            return [
                'id' => 'PAY-' . (10 + $repayment->id),
                'rawId' => $repayment->id,
                'loanId' => 'LN-' . (700 + $repayment->loan_id),
                'dueDate' => $repayment->due_date,
                'paidDate' => $repayment->paid_date,
                'amountDue' => (float) $repayment->amount_due,
                'paidAmount' => (float) $repayment->paid_amount,
                'status' => $repayment->status,
            ];
        });

        return response()->json($repayments);
    }

    public function addRepayment(Request $request, int $id)
    {
        $repayment = Repayment::findOrFail($id);
        $validated = $request->validate([
            'amount' => 'required|numeric|min:1',
        ]);

        $newPaid = $repayment->paid_amount + $validated['amount'];
        $status = $newPaid >= $repayment->amount_due ? 'Paid' : 'Partial';

        $repayment->update([
            'paid_amount' => $newPaid,
            'status' => $status,
            'paid_date' => $status === 'Paid' ? now()->toDateString() : $repayment->paid_date,
        ]);

        return response()->json(['message' => 'Repayment recorded']);
    }

    public function guarantors()
    {
        $this->seedIfEmpty();

        $items = Guarantor::with('loans')->get()->map(fn($g) => [
            'id' => 'G-' . (300 + $g->id),
            'name' => $g->name,
            'phone' => $g->phone,
            'address' => $g->address,
            'linkedLoans' => $g->loans->map(fn($loan) => 'LN-' . (700 + $loan->id))->values(),
        ]);

        return response()->json($items);
    }

    public function documents()
    {
        $this->seedIfEmpty();

        $docs = BorrowerDocument::with('user')->get()->map(fn($doc) => [
            'id' => 'DOC-' . $doc->id,
            'type' => $doc->type,
            'fileName' => $doc->file_name,
            'borrower' => $doc->user?->name,
        ]);

        return response()->json($docs);
    }

    public function notifications()
    {
        return response()->json(NotificationLog::latest()->take(20)->get(['id', 'channel', 'message', 'created_at']));
    }

    public function sendNotification(Request $request)
    {
        $validated = $request->validate([
            'channel' => 'required|string|in:SMS,Email,WhatsApp',
            'message' => 'required|string',
        ]);

        $log = NotificationLog::create($validated);

        return response()->json($log, 201);
    }

    public function reports()
    {
        $this->seedIfEmpty();

        $topBorrowers = User::withSum('loans', 'amount')->orderByDesc('loans_sum_amount')->take(5)->get()->map(fn($u) => $u->name);
        $defaulters = User::whereHas('loans', fn($q) => $q->where('status', 'Overdue'))->pluck('name');

        return response()->json([
            'topBorrowers' => $topBorrowers,
            'defaulters' => $defaulters,
        ]);
    }

    public function settings()
    {
        $defaults = [
            'businessName' => 'SwiftLoan',
            'defaultInterestRate' => '14',
            'durationPresets' => '3, 6, 12',
            'smsProvider' => 'Twilio',
            'emailFrom' => 'support@swiftloan.io',
            'logo' => 'company-logo.png',
        ];

        foreach ($defaults as $key => $value) {
            SystemSetting::firstOrCreate(['key' => $key], ['value' => $value]);
        }

        return response()->json(SystemSetting::pluck('value', 'key'));
    }

    public function updateSettings(Request $request)
    {
        foreach ($request->all() as $key => $value) {
            SystemSetting::updateOrCreate(['key' => $key], ['value' => (string) $value]);
        }

        return response()->json(['message' => 'Settings updated']);
    }

    public function security()
    {
        $staff = User::where('is_admin', true)->get()->map(fn($u) => [
            'name' => $u->name,
            'role' => 'Admin',
            'twoFactor' => false,
            'lastLogin' => optional($u->updated_at)->format('Y-m-d'),
        ]);

        $logs = NotificationLog::latest()->take(10)->get()->map(fn($l) => [
            'time' => optional($l->created_at)->format('Y-m-d H:i'),
            'actor' => 'System',
            'action' => $l->channel . ': ' . $l->message,
        ]);

        return response()->json(['staff' => $staff, 'logs' => $logs]);
    }

    private function seedIfEmpty(): void
    {
        if (User::count() > 0) {
            return;
        }

        $u1 = User::create(['name' => 'Amara Ndlovu', 'email' => 'amara@client.com', 'phone' => '+264811234567', 'password' => Hash::make('password123')]);
        $u2 = User::create(['name' => 'Jonas Mbeha', 'email' => 'jonas@client.com', 'phone' => '+264812345678', 'password' => Hash::make('password123')]);
        $admin = User::create(['name' => 'Admin', 'email' => 'admin@swiftloan.io', 'phone' => '+264810000000', 'password' => Hash::make('password123'), 'is_admin' => true]);

        $g1 = Guarantor::create(['name' => 'Peter Ndeitunga', 'phone' => '+264814001122', 'address' => 'Windhoek West']);
        $g2 = Guarantor::create(['name' => 'Helena Kauta', 'phone' => '+264814884422', 'address' => 'Katutura']);

        $l1 = Loan::create(['user_id' => $u1->id, 'guarantor_id' => $g1->id, 'amount' => 15000, 'interest_rate' => '14', 'duration' => '12 months', 'purpose' => 'Business stock', 'status' => 'Active', 'outstanding_balance' => 9800, 'is_disbursed' => true, 'disbursement_method' => 'Bank Transfer', 'transfer_reference' => 'TRX-55101', 'disbursed_at' => now()->subWeeks(3), 'start_date' => now()->subWeeks(3), 'next_payment_date' => now()->addDays(4)]);
        $l2 = Loan::create(['user_id' => $u2->id, 'guarantor_id' => $g2->id, 'amount' => 8000, 'interest_rate' => '12', 'duration' => '6 months', 'purpose' => 'School fees', 'status' => 'Overdue', 'outstanding_balance' => 6400, 'is_disbursed' => true, 'disbursement_method' => 'Mobile Money', 'transfer_reference' => 'MOMO-228', 'disbursed_at' => now()->subMonth(), 'start_date' => now()->subMonth(), 'next_payment_date' => now()->addDay()]);

        LoanApplication::create(['user_id' => $u1->id, 'guarantor_id' => $g1->id, 'amount' => 15000, 'duration' => '12 months', 'purpose' => 'Business stock', 'status' => 'Pending']);
        LoanApplication::create(['user_id' => $u2->id, 'guarantor_id' => $g2->id, 'amount' => 8000, 'duration' => '6 months', 'purpose' => 'School fees', 'status' => 'More Info Requested']);

        Repayment::create(['loan_id' => $l1->id, 'due_date' => now()->subDays(4)->toDateString(), 'paid_date' => now()->subDays(5)->toDateString(), 'amount_due' => 1800, 'paid_amount' => 1800, 'status' => 'Paid']);
        Repayment::create(['loan_id' => $l2->id, 'due_date' => now()->subDays(9)->toDateString(), 'amount_due' => 1500, 'paid_amount' => 700, 'status' => 'Partial']);
        Repayment::create(['loan_id' => $l2->id, 'due_date' => now()->subDays(18)->toDateString(), 'amount_due' => 1500, 'paid_amount' => 0, 'status' => 'Unpaid']);

        BorrowerDocument::create(['user_id' => $u1->id, 'type' => 'ID Card', 'file_name' => 'amara_id.pdf']);
        BorrowerDocument::create(['user_id' => $u1->id, 'type' => 'Signed Agreement', 'file_name' => 'ln700_agreement.pdf']);
        BorrowerDocument::create(['user_id' => $u2->id, 'type' => 'Utility Bill', 'file_name' => 'jonas_utility.pdf']);

        NotificationLog::create(['channel' => 'SMS', 'message' => 'Due today alerts sent to 8 borrowers']);
        NotificationLog::create(['channel' => 'Email', 'message' => 'Overdue alert sent to collections team']);

        $admin->update(['status' => 'Active']);
    }
}
