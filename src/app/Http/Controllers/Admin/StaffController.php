<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Role;
use App\Models\Attendance;
use Carbon\Carbon;

class StaffController extends Controller
{
    public function index()
    {
        $staffs = User::whereHas('role', function($query) {
            $query->where('name', 'user');
        })->orderBy('id')->get();

        return view('admin.staff.list', [
            'staffs' => $staffs
        ]);
    }

    public function monthlyAttendance($id, Request $request)
    {
        $user = User::findOrFail($id);
        $month = $request->get('month', now()->format('Y-m'));
        
        $attendances = Attendance::where('user_id', $id)
            ->whereYear('date', substr($month, 0, 4))
            ->whereMonth('date', substr($month, 5, 2))
            ->orderBy('date')
            ->get()
            ->map(function ($attendance) {
                $attendance->date = Carbon::parse($attendance->date);
                
                // 勤務状態の判定
                if (!$attendance->clock_in) {
                    $attendance->work_status = '未出勤';
                } elseif ($attendance->clock_in && !$attendance->clock_out) {
                    $attendance->work_status = '勤務中';
                } else {
                    $attendance->work_status = '勤務済';
                }

                // 休憩時間の計算
                $totalBreakMinutes = $attendance->breaks()
                    ->whereNotNull('start_time')
                    ->whereNotNull('end_time')
                    ->get()
                    ->sum(function ($break) {
                        return Carbon::parse($break->end_time)
                            ->diffInMinutes(Carbon::parse($break->start_time));
                    });
                $attendance->break_time = sprintf('%02d:%02d', 
                    intdiv($totalBreakMinutes, 60),
                    $totalBreakMinutes % 60
                );

                // 勤務時間の計算
                if ($attendance->clock_in && $attendance->clock_out) {
                    $totalWorkMinutes = Carbon::parse($attendance->clock_out)
                        ->diffInMinutes(Carbon::parse($attendance->clock_in));
                    $actualWorkMinutes = max(0, $totalWorkMinutes - $totalBreakMinutes);
                    $attendance->work_time = sprintf('%02d:%02d',
                        intdiv($actualWorkMinutes, 60),
                        $actualWorkMinutes % 60
                    );
                } else {
                    $attendance->work_time = '--:--';
                }

                return $attendance;
            });

        return view('admin.staff.monthly_attendance', [
            'user' => $user,
            'attendances' => $attendances,
            'currentMonth' => $month
        ]);
    }
}