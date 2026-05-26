<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Guarantor extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'phone',
        'address',
    ];

    public function loans()
    {
        return $this->hasMany(Loan::class);
    }

    public function applications()
    {
        return $this->hasMany(LoanApplication::class);
    }
}
