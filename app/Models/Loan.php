<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Loan extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'guarantor_id',
        'amount',
        'interest_rate',
        'duration',
        'purpose',
        'status',
        'start_date',
        'next_payment_date',
        'outstanding_balance',
        'is_disbursed',
        'disbursement_method',
        'transfer_reference',
        'disbursed_at',
        'progress_stage',
        'progress_level',
        'approval_code',
        'tracking_code',
        'codes_requested',
    ];

    protected $casts = [
        'is_disbursed' => 'boolean',
        'start_date' => 'date',
        'next_payment_date' => 'date',
        'disbursed_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function guarantor()
    {
        return $this->belongsTo(Guarantor::class);
    }

    public function repayments()
    {
        return $this->hasMany(Repayment::class);
    }
}
