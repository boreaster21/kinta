<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Attendance;
use App\Models\BreakTime;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Log;

class AttendanceController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $today = CarbonImmutable::today(); 
        $attendance = Attendance::where('user_id', $user->id)
            ->whereDate('date', $today)
            ->first();

        $dateFormatted = $today->translatedFormat('Y年m月d日 (D)');
        $dateFormatted = str_replace(
            ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'],
            ['日', '月', '火', '水', '木', '金', '土'],
            $dateFormatted
        );

        if (!$attendance) {
            $status = '勤務外';
        } elseif ($attendance->clock_in && !$attendance->clock_out) {
            if ($attendance->breaks()->whereNull('end_time')->exists()) {
                $status = '休憩中';
            } else {
                $status = '出勤中';
            }
        } elseif ($attendance->clock_out) {
            $status = '退勤済';
        } else {
            $status = '勤務外';
        }

        return view('attendance.index', [
            'attendance' => $attendance,
            'currentTime' => Carbon::now()->format('H:i'),
            'date' => $dateFormatted,
            'status' => $status,
        ]);
    }

    public function clockIn()
    {
        $user = Auth::user();
        $today = Carbon::today()->toDateString();

        if (!Attendance::where('user_id', $user->id)->whereDate('date', $today)->exists()) {
            Attendance::create([
                'user_id' => $user->id,
                'date' => $today, 
                'clock_in' => Carbon::now(),
            ]);
        }

        return redirect()->route('attendance.index');
    }

    public function clockOut()
    {
        $user = Auth::user();
        $today = Carbon::today()->toDateString();
        $attendance = Attendance::where('user_id', $user->id)->whereDate('date', $today)->first();

        if ($attendance && !$attendance->clock_out) {
            $attendance->update(['clock_out' => Carbon::now()]);
            $attendance->calculateTotalWorkTime();

            session()->flash('clocked_out', true);
        }

        return redirect()->route('attendance.index');
    }

    public function breakStart()
    {
        $user = Auth::user();
        $today = Carbon::today()->toDateString();
        $attendance = Attendance::where('user_id', $user->id)->whereDate('created_at', $today)->first();

        if ($attendance) {
            BreakTime::create([
                'attendance_id' => $attendance->id,
                'start_time' => Carbon::now(),
            ]);
        }

        return redirect()->route('attendance.index');
    }

    public function breakEnd()
    {
        $user = Auth::user();
        $today = Carbon::today()->toDateString();
        $attendance = Attendance::where('user_id', $user->id)->whereDate('created_at', $today)->first();

        if ($attendance) {
            $break = $attendance->breaks()->whereNull('end_time')->first();
            if ($break) {
                $break->endBreak();
            }
        }

        return redirect()->route('attendance.index');
    }

    public function list(Request $request)
    {
        $user = Auth::user();
        $month = $request->query('month', Carbon::now()->format('Y-m')); 
        $startDate = Carbon::parse($month . '-01')->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();

        $attendances = Attendance::where('user_id', $user->id)
            ->whereBetween('date', [$startDate, $endDate])
            ->orderBy('date', 'asc')
            ->get();

        return view('attendance.list', [
            'attendances' => $attendances,
            'currentMonth' => $month,
            'previousMonth' => $startDate->copy()->subMonth()->format('Y-m'),
            'nextMonth' => $startDate->copy()->addMonth()->format('Y-m'),
        ]);
    }

    public function show($id)
    {
        $attendance = Attendance::with(['user', 'correctionRequests'])->findOrFail($id);

        $pendingRequest = $attendance->correctionRequests()->where('status', 'pending')->first();

        return view('attendance.detail', compact('attendance', 'pendingRequest'));

    }
}
