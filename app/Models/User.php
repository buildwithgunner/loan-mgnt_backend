<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'is_admin',
        'phone',
        'status',
        'password',
        'is_blacklisted',
        'is_suspended',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_admin' => 'boolean',
        'is_blacklisted' => 'boolean',
        'is_suspended' => 'boolean',
    ];

    public function loans()
    {
        return $this->hasMany(Loan::class);
    }

    public function applications()
    {
        return $this->hasMany(LoanApplication::class);
    }

    public function documents()
    {
        return $this->hasMany(BorrowerDocument::class);
    }
}
