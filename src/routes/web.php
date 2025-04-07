<?php
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Http\Controllers\AuthenticatedSessionController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\StampCorrectionRequestController;
use App\Http\Controllers\Admin\StaffController;
use App\Http\Controllers\Admin\AuthController as AdminAuthController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;

Route::get('/', function () {
    if (Auth::check()) {
        return Auth::user()->isAdmin() ?
            redirect('/admin/attendance/list') :
            redirect('/attendance');
    }
    return redirect('/login');
});

Route::middleware(['guest'])->group(function () {
    Route::view('/login', 'auth.login')->name('login');
    Route::view('/register', 'auth.register')->name('register');
    Route::post('/login', [AuthenticatedSessionController::class, 'store']);
});

Route::prefix('admin')->name('admin.')->group(function () {
    Route::middleware(['guest'])->group(function () {
        Route::get('/login', [AdminAuthController::class, 'showLoginForm'])->name('login');
        Route::post('/login', [AdminAuthController::class, 'login'])->name('login.store');
    });

    Route::middleware(['auth', 'admin'])->group(function () {
        Route::post('/logout', [AdminAuthController::class, 'logout'])->name('logout');
        Route::get('/staff/list', [StaffController::class, 'index'])->name('staff.list');
        Route::get('/attendance/staff/{id}', [StaffController::class, 'monthlyAttendance'])->name('staff.monthly_attendance');
        Route::get('/attendance/list', [App\Http\Controllers\Admin\AttendanceController::class, 'list'])->name('attendance.list');
        Route::put('/attendance/{id}/update', [App\Http\Controllers\Admin\AttendanceController::class, 'update'])
            ->name('attendance.update')
            ->withoutMiddleware(\Illuminate\Auth\Middleware\Authorize::class);
        Route::get('/attendance/staff/{id}/export/{month}', [App\Http\Controllers\Admin\AttendanceController::class, 'exportMonthlyCsv'])->name('staff.monthly_attendance.export');

        Route::prefix('stamp_correction_request')->name('stamp_correction_request.')->group(function() {
            Route::get('/approve/{id}', [\App\Http\Controllers\Admin\StampCorrectionRequestController::class, 'showApproveForm'])->name('show');
            Route::post('/approve/{request}', [\App\Http\Controllers\Admin\StampCorrectionRequestController::class, 'approve'])->name('approve');
            Route::post('/reject/{id}', [\App\Http\Controllers\Admin\StampCorrectionRequestController::class, 'reject'])->name('reject');
            Route::get('/approved/{id}', [\App\Http\Controllers\Admin\StampCorrectionRequestController::class, 'showApproved'])->name('approved');
        });
    });
});

Route::middleware(['auth'])->group(function () {
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
    Route::post('/admin/logout', [AdminAuthController::class, 'logout'])->name('admin.logout');
    Route::get('/email/verify', function () {
        return view('auth.verify-email');
    })->name('verification.notice');

    Route::get('/email/verify/{id}/{hash}', function (EmailVerificationRequest $request) {
        $request->fulfill();
        return redirect('/attendance');
    })->middleware(['signed'])->name('verification.verify');

    Route::post('/email/verification-notification', function (Request $request) {
        $request->user()->sendEmailVerificationNotification();
        return back()->with('status', 'verification-link-sent');
    })->middleware(['throttle:6,1'])->name('verification.send');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::prefix('attendance')->name('attendance.')->group(function () {
        Route::get('/', [AttendanceController::class, 'index'])->name('index');
        Route::post('/clock-in', [AttendanceController::class, 'clockIn'])->name('clock_in');
        Route::post('/clock-out', [AttendanceController::class, 'clockOut'])->name('clock_out');
        Route::post('/break-start', [AttendanceController::class, 'breakStart'])->name('break_start');
        Route::post('/break-end', [AttendanceController::class, 'breakEnd'])->name('break_end');
        Route::get('/list', [AttendanceController::class, 'list'])->name('list');
        Route::get('/{id}', [AttendanceController::class, 'show'])->name('show');
        Route::post('/{id}/request', [AttendanceController::class, 'request'])->name('request');
    });

    Route::prefix('stamp_correction_request')->name('stamp_correction_request.')->group(function () {
        Route::get('/list', [\App\Http\Controllers\StampCorrectionRequestController::class, 'list'])->name('list');
        Route::get('/pending/{request}', [\App\Http\Controllers\StampCorrectionRequestController::class, 'showPending'])->name('pending');
        Route::get('/approved/{request}', [\App\Http\Controllers\StampCorrectionRequestController::class, 'showApproved'])->name('approved');
    });
});