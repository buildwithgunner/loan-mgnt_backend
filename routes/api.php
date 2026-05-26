<?php

use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\LoanController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/admin/register', [AuthController::class, 'registerAdmin']);
Route::post('/auth/admin/login', [AuthController::class, 'loginAdmin']);
Route::get('/loans', [LoanController::class, 'index']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);

    Route::post('/loans', [LoanController::class, 'store']);
    Route::patch('/loans/{id}', [LoanController::class, 'update']);
    Route::post('/loans/{id}/request-codes', [LoanController::class, 'requestCodes']);
    Route::get('/users', [UserController::class, 'index']);
});

Route::prefix('admin')->middleware(['auth:sanctum', 'admin'])->group(function () {
    Route::get('/overview', [AdminController::class, 'overview']);
    Route::get('/borrowers', [AdminController::class, 'borrowers']);
    Route::patch('/borrowers/{id}', [AdminController::class, 'updateBorrower']);
    Route::get('/applications', [AdminController::class, 'applications']);
    Route::patch('/applications/{id}', [AdminController::class, 'updateApplication']);
    Route::get('/active-loans', [AdminController::class, 'activeLoans']);
    Route::patch('/active-loans/{id}/disburse', [AdminController::class, 'disburseLoan']);
    Route::patch('/active-loans/{id}/progress', [AdminController::class, 'updateLoanProgress']);
    Route::post('/active-loans/{id}/generate-codes', [AdminController::class, 'generateCodes']);
    Route::get('/repayments', [AdminController::class, 'repayments']);
    Route::patch('/repayments/{id}/pay', [AdminController::class, 'addRepayment']);
    Route::get('/guarantors', [AdminController::class, 'guarantors']);
    Route::get('/documents', [AdminController::class, 'documents']);
    Route::get('/notifications', [AdminController::class, 'notifications']);
    Route::post('/notifications', [AdminController::class, 'sendNotification']);
    Route::get('/reports', [AdminController::class, 'reports']);
    Route::get('/settings', [AdminController::class, 'settings']);
    Route::patch('/settings', [AdminController::class, 'updateSettings']);
    Route::get('/security', [AdminController::class, 'security']);
});
