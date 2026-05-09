<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Application extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'type',
        'property',
        'amount',
        'status',
        'ltv',
        'processing_stage',
        'processing_level',
        'requirement_steps',
        'current_requirement_index',
        'approval_code',
        'guarantor_name',
        'guarantor_phone',
        'guarantor_email',
        'guarantor_address',
        'form_data',
        'tracking_code',
        'codes_requested',
        'bank_name',
        'account_name',
        'account_number',
    ];

    protected $casts = [
        'form_data' => 'array',
        'requirement_steps' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function documents()
    {
        return $this->hasMany(Document::class);
    }

    public function disbursements()
    {
        return $this->hasMany(LoanDisbursement::class);
    }
}
