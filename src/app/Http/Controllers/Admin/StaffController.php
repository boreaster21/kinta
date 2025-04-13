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

        $currentMonth = Carbon::parse($month . '-01');
        $previousMonth = $currentMonth->copy()->subMonth()->format('Y-m');
        $nextMonth = $currentMonth->copy()->addMonth()->format('Y-m');

        $attendances = Attendance::where('user_id', $id)
            ->whereYear('date', $currentMonth->year)
            ->whereMonth('date', $currentMonth->month)
            ->orderBy('date')
            ->get()
            ->map(function ($attendance) {
                $dateObj = Carbon::parse($attendance->date);

                $formattedDate = $dateObj->format('m/d');
                $dayOfWeek = ['日', '月', '火', '水', '木', '金', '土'][$dateObj->dayOfWeek];
                $displayDate = $formattedDate . ' (' . $dayOfWeek . ')';

                $clockIn = $attendance->clock_in ? Carbon::parse($attendance->clock_in)->format('H:i') : '-';
                $clockOut = $attendance->clock_out ? Carbon::parse($attendance->clock_out)->format('H:i') : '-';

                $breakTime = $attendance->total_break_time ?: '00:00';
                $totalTime = $attendance->total_work_time ?: '00:00';

                $formatTime = function($timeString) {
                    if (empty($timeString) || $timeString === '00:00') return '0:00';
                    return ltrim($timeString, '0');
                };

                return [
                    'id' => $attendance->id,
                    'date' => $displayDate,
                    'clock_in' => $clockIn,
                    'clock_out' => $clockOut,
                    'break_time' => $formatTime($breakTime),
                    'total_time' => $formatTime($totalTime),
                ];
            });

        return view('admin.staff.monthly_attendance', [
            'user' => $user,
            'attendances' => $attendances,
            'month' => $month,
            'previousMonth' => $previousMonth,
            'nextMonth' => $nextMonth,
        ]);
    }
}