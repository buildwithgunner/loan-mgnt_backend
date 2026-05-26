<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Repayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'loan_id',
        'due_date',
        'paid_date',
        'amount_due',
        'paid_amount',
        'status',
    ];

    public function loan()
    {
        return $this->belongsTo(Loan::class);
    }
}
