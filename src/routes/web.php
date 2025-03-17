<?php

use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Http\Controllers\AuthenticatedSessionController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\Admin\AttendanceController as AdminAttendanceController;
use App\Http\Controllers\Admin\StaffController;
use App\Http\Controllers\Admin\AuthController as AdminAuthController;
use App\Http\Controllers\StampCorrectionRequestController;
use App\Http\Controllers\Admin\StampCorrectionRequestController as AdminStampCorrectionRequestController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;

Route::get('/', function () {
    if (Auth::check()) {
        return Auth::user()->is_admin ? redirect('/admin/attendance/list') : redirect('/attendance');
    }
    return redirect('/login');
});

Route::middleware(['guest'])->group(function () {
    Route::view('/login', 'auth.login')->name('login');
    Route::view('/register', 'auth.register')->name('register');
    Route::post('/login', [AuthenticatedSessionController::class, 'store']);
});

Route::prefix('admin')->middleware(['guest'])->group(function () {
    Route::get('/login', [AdminAuthController::class, 'showLoginForm'])->name('admin.login');
    Route::post('/login', [AdminAuthController::class, 'login'])->name('admin.login.store');
});

Route::middleware(['auth'])->group(function () {
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
    Route::get('/email/verify', function () {
        return view('auth.verify-email');
    })->name('verification.notice');

    Route::get('/email/verify/{id}/{hash}', function (EmailVerificationRequest $request) {
        $request->fulfill();
        return redirect('/attendance');
    })->middleware(['signed'])->name('verification.verify');

    Route::post('/email/resend', function (Request $request) {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect('/attendance');
        }

        $request->user()->sendEmailVerificationNotification();
        return back()->with('message', '認証メールを再送しました！');
    })->middleware(['throttle:6,1'])->name('verification.resend');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/attendance', [AttendanceController::class, 'index'])->name('attendance.index');
    Route::post('/attendance/clock-in', [AttendanceController::class, 'clockIn'])->name('attendance.clock-in');
    Route::post('/attendance/clock-out', [AttendanceController::class, 'clockOut'])->name('attendance.clock-out');
    Route::post('/attendance/break-start', [AttendanceController::class, 'breakStart'])->name('attendance.break-start');
    Route::post('/attendance/break-end', [AttendanceController::class, 'breakEnd'])->name('attendance.break-end');
    Route::get('/attendance/list', [AttendanceController::class, 'list'])->name('attendance.list');
    Route::get('/attendance/{id}', [AttendanceController::class, 'show'])->name('attendance.show');
    Route::post('/attendance/{id}/request', [StampCorrectionRequestController::class, 'store'])->name('attendance.request');
    Route::get('/stamp_correction_request/list', [StampCorrectionRequestController::class, 'index'])->name('stamp_correction_request.list');
    
    Route::middleware(['admin'])->group(function () {
        Route::get('/stamp_correction_request/approve/{id}', [StampCorrectionRequestController::class, 'showApproveForm'])
            ->name('stamp_correction_request.approve.form');
        Route::post('/stamp_correction_request/{id}/approve', [StampCorrectionRequestController::class, 'approve'])
            ->name('stamp_correction_request.approve');
        Route::post('/stamp_correction_request/{id}/reject', [StampCorrectionRequestController::class, 'reject'])
            ->name('stamp_correction_request.reject');
    });
});

Route::prefix('admin')->middleware(['auth', 'admin', 'verified'])->group(function () {
    Route::post('/logout', [AdminAuthController::class, 'logout'])->name('admin.logout');
    Route::get('/attendance/list', [AdminAttendanceController::class, 'index'])->name('admin.attendance.list');
    Route::get('/staff/list', [StaffController::class, 'index'])->name('admin.staff.list');
    Route::get('/attendance/staff/{id}', [StaffController::class, 'monthlyAttendance'])->name('admin.staff.monthly_attendance');
    Route::get('/attendance/{id}', [AdminAttendanceController::class, 'show'])->name('admin.attendance.show');
    Route::put('/attendance/{id}', [AdminAttendanceController::class, 'update'])->name('admin.attendance.update');
    Route::get('/stamp_correction_request/list', [AdminStampCorrectionRequestController::class, 'index'])->name('admin.stamp_correction_request.list');
    Route::get('/stamp_correction_request/{id}/approve', [AdminStampCorrectionRequestController::class, 'showApproveForm'])->name('admin.stamp_correction_request.approve.form');
    Route::post('/stamp_correction_request/{id}/approve', [AdminStampCorrectionRequestController::class, 'approve'])->name('admin.stamp_correction_request.approve');
    Route::post('/stamp_correction_request/{id}/reject', [AdminStampCorrectionRequestController::class, 'reject'])->name('admin.stamp_correction_request.reject');
});
