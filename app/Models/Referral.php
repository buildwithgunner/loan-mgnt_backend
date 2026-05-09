<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Referral extends Model
{
    use HasFactory;

    protected $fillable = [
        'referrer_id',
        'referred_name',
        'referred_email',
        'status',
        'bonus_amount',
    ];

    public function referrer()
    {
        return $this->belongsTo(User::class, 'referrer_id');
    }
}
