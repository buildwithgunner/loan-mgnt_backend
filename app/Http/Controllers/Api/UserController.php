<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;

class UserController extends Controller
{
    public function index()
    {
        $users = User::withCount('loans')
            ->withSum('loans as totalBorrowed', 'amount')
            ->get()
            ->map(function ($user) {
                return [
                    'id' => "U-" . (1000 + $user->id),
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone ?? '+1 234-567-8900',
                    'status' => $user->status ?? 'Active',
                    'loanCount' => $user->loans_count,
                    'totalBorrowed' => $user->totalBorrowed ?? 0,
                    'joinDate' => $user->created_at->format('Y-m-d')
                ];
            });

        return response()->json($users);
    }
}
