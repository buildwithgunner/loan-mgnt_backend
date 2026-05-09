<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LoanDisbursement extends Model
{
    use HasFactory;

    protected $fillable = [
        'application_id',
        'amount',
    ];

    public function application()
    {
        return $this->belongsTo(Application::class);
    }
}
