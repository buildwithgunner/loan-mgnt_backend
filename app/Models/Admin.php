<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use App\Models\Application;
use App\Models\Document;
use App\Models\Notification;
use App\Models\Referral;

class Admin extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = ['role'];

    /**
     * Get the role attribute for the admin.
     *
     * @return string
     */
    public function getRoleAttribute()
    {
        return 'admin';
    }

    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Stubs for user-facing relationships to prevent crashes if admin accesses user dashboard.
     */
    public function applications() { return $this->hasMany(Application::class, 'user_id', 'id')->whereRaw('1 = 0'); }
    public function documents() { return $this->hasMany(Document::class, 'user_id', 'id')->whereRaw('1 = 0'); }
    public function notifications() { return $this->hasMany(Notification::class, 'user_id', 'id')->whereRaw('1 = 0'); }
    public function referrals() { return $this->hasMany(Referral::class, 'user_id', 'id')->whereRaw('1 = 0'); }

    public function getRepaidAmountAttribute() { return 0; }
    public function getAvailableBalanceAttribute() { return '$0.00'; }
}
