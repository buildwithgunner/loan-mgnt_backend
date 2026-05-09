<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'company',
        'address',
        'city',
        'state',
        'zip_code',
        'dob',
        'ssn',
        'role',
        'password',
        'marital_status',
        'occupation',
        'self_employed',
        'estimated_fico',
        'estimated_net_worth',
        'referral_source',
        'working_with_consultant',
        'loan_intent',
        'repaid_amount',
        'available_balance',
        'withdrawn_amount',
        'avatar',
        'is_active',
        'activation_requested',
        'activation_requested_at',
        'activated_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at'        => 'datetime',
        'activation_requested_at'  => 'datetime',
        'activated_at'             => 'datetime',
        'is_active'                => 'boolean',
        'activation_requested'     => 'boolean',
        'password'                 => 'hashed',
    ];

    /**
     * Get the applications for the user.
     */
    public function applications()
    {
        return $this->hasMany(Application::class);
    }

    /**
     * Get the documents for the user.
     */
    public function documents()
    {
        return $this->hasMany(Document::class);
    }

    /**
     * Get the notifications for the user.
     */
    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }

    /**
     * Get the referrals for the user.
     */
    public function referrals()
    {
        return $this->hasMany(Referral::class, 'referrer_id');
    }
}
