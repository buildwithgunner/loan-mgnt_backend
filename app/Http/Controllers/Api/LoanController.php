<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Loan;

class LoanController extends Controller
{
    public function index()
    {
        $loans = Loan::with('user')->get()->map(function ($loan) {
            return [
                'id' => "L-" . (1000 + $loan->id),
                'user' => $loan->user ? $loan->user->name : 'Unknown User',
                'email' => $loan->user ? $loan->user->email : '',
                'amount' => $loan->amount,
                'interestRate' => $loan->interest_rate,
                'duration' => $loan->duration,
                'status' => $loan->status,
                'startDate' => $loan->start_date ? \Carbon\Carbon::parse($loan->start_date)->format('Y-m-d') : '-',
                'nextPaymentDate' => $loan->next_payment_date ? \Carbon\Carbon::parse($loan->next_payment_date)->format('Y-m-d') : '-',
                'progressStage' => $loan->progress_stage,
                'progressLevel' => (int) $loan->progress_level,
                'approvalCode' => $loan->approval_code,
                'trackingCode' => $loan->tracking_code,
                'codesRequested' => (bool) $loan->codes_requested,
            ];
        });
        
        return response()->json($loans);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'amount' => 'required|numeric|min:1',
            'interest_rate' => 'required|string',
            'duration' => 'required|string',
        ]);

        $loan = Loan::create(array_merge($validated, ['status' => 'Pending']));

        return response()->json($loan, 201);
    }

    public function update(Request $request, string $id)
    {
        // Extract numeric ID from L-XXXX format
        $numericId = (int) str_replace('L-', '', $id) - 1000;
        $loan = Loan::findOrFail($numericId);

        $validated = $request->validate([
            'status' => 'required|in:Active,Pending,Repaid,Defaulted,Rejected',
        ]);

        $loan->update($validated);

        if ($validated['status'] === 'Active') {
            $loan->update([
                'start_date' => now(),
                'next_payment_date' => now()->addMonth(),
            ]);
        }

        return response()->json($loan);
    }

    public function requestCodes(Request $request, string $id)
    {
        // Extract numeric ID from L-XXXX format
        $numericId = (int) str_replace('L-', '', $id) - 1000;
        $loan = Loan::findOrFail($numericId);

        $loan->update([
            'codes_requested' => true,
        ]);

        return response()->json(['message' => 'Codes requested successfully']);
    }
}
