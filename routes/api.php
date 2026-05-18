<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\AdminController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Public routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/otp/send', [AuthController::class, 'sendOtp']);
Route::post('/otp/verify', [AuthController::class, 'verifyOtp']);
Route::post('/admin/register', [AuthController::class, 'adminRegister']);
Route::post('/admin/login', [AuthController::class, 'adminLogin']);
Route::get('/settings', [AdminController::class, 'getSettings']);
Route::post('/leads/submit', [AdminController::class, 'publicSubmitLead']);
Route::get('/admin/documents/{id}/view', [AdminController::class, 'viewDocument']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    Route::put('/user/profile', [AuthController::class, 'updateProfile']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // Dashboard routes
    Route::get('/dashboard/summary', [DashboardController::class, 'summary']);
    Route::get('/dashboard/applications', [DashboardController::class, 'applications']);
    Route::post('/dashboard/applications', [DashboardController::class, 'submitApplication']);
    Route::post('/dashboard/applications/upload-doc/{id}', [DashboardController::class, 'uploadDocument']);
    Route::post('/dashboard/documents/upload-id', [DashboardController::class, 'uploadIdDocument']);
    Route::get('/dashboard/documents', [DashboardController::class, 'documents']);
    Route::get('/dashboard/notifications', [DashboardController::class, 'notifications']);
    Route::post('/dashboard/notifications/{id}/read', [DashboardController::class, 'markNotificationRead']);
    Route::get('/dashboard/referrals', [DashboardController::class, 'referrals']);
    Route::post('/dashboard/applications/{id}/verify-codes', [DashboardController::class, 'verifyStageCodes']);
    Route::post('/dashboard/applications/{id}/request-codes', [DashboardController::class, 'requestCodes']);
    Route::post('/dashboard/applications/{id}/bank-details', [DashboardController::class, 'updateBankDetails']);
    Route::post('/dashboard/request-activation', [DashboardController::class, 'requestActivation']);

    // Admin routes
    Route::middleware('admin')->prefix('admin')->group(function () {
        Route::get('/stats', [AdminController::class, 'getStats']);
        Route::get('/applications', [AdminController::class, 'getApplications']);
        Route::post('/applications/{id}/status', [AdminController::class, 'updateApplicationStatus']);
        Route::post('/applications/{id}/progress', [AdminController::class, 'updateApplicationProgress']);
        Route::post('/applications/{id}/generate-code', [AdminController::class, 'generateApprovalCode']);
        Route::post('/applications/{id}/tracking-code', [AdminController::class, 'generateTrackingCode']);
        Route::post('/applications/{id}/generate-both-codes', [AdminController::class, 'generateBothCodes']);
        Route::put('/applications/{id}', [AdminController::class, 'updateApplication']);
        Route::get('/users', [AdminController::class, 'getUsers']);
        Route::get('/users/{id}/profile', [AdminController::class, 'getUserProfile']);
        Route::put('/users/{id}', [AdminController::class, 'updateUser']);
        Route::delete('/users/{id}', [AdminController::class, 'deleteUser']);
        Route::get('/leads', [AdminController::class, 'getLeads']);
        Route::post('/leads', [AdminController::class, 'createLead']);
        Route::get('/documents', [AdminController::class, 'getDocuments']);
        Route::get('/settings', [AdminController::class, 'getSettings']);
        Route::put('/settings', [AdminController::class, 'updateSettings']);
        Route::post('/users/{id}/activate', [AdminController::class, 'activateUser']);
        Route::post('/users/{id}/deactivate', [AdminController::class, 'deactivateUser']);
    });
});
