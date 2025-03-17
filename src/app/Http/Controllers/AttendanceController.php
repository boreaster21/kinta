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
        $attendance = Attendance::where('user_id', $user->id)
            ->whereDate('date', $today)
            ->first();

        if ($attendance && !$attendance->clock_out) {
            $attendance->clock_out = Carbon::now();
            
            // 休憩時間の計算
            $totalBreakMinutes = $attendance->breaks()
                ->whereNotNull('start_time')
                ->whereNotNull('end_time')
                ->get()
                ->sum(function ($break) {
                    $startTime = Carbon::parse($break->start_time);
                    $endTime = Carbon::parse($break->end_time);
                    return max(0, $endTime->diffInMinutes($startTime));
                });

            // 休憩時間を保存
            $formattedBreakTime = sprintf('%02d:%02d', 
                intdiv($totalBreakMinutes, 60), 
                $totalBreakMinutes % 60
            );
            $attendance->total_break_time = $formattedBreakTime;

            // 合計勤務時間の計算
            $clockIn = Carbon::parse($attendance->clock_in);
            $clockOut = Carbon::parse($attendance->clock_out);
            $totalWorkMinutes = $clockOut->diffInMinutes($clockIn);
            $actualWorkMinutes = max(0, $totalWorkMinutes - $totalBreakMinutes);

            $formattedWorkTime = sprintf('%02d:%02d', 
                intdiv($actualWorkMinutes, 60), 
                $actualWorkMinutes % 60
            );
            $attendance->total_work_time = $formattedWorkTime;

            $attendance->save();
            Log::info('Clock out completed', [
                'attendance_id' => $attendance->id,
                'clock_in' => $attendance->clock_in,
                'clock_out' => $attendance->clock_out,
                'total_break_time' => $attendance->total_break_time,
                'total_work_time' => $attendance->total_work_time
            ]);
            session()->flash('clocked_out', true);
        }

        return redirect()->route('attendance.index');
    }

    public function breakStart()
    {
        $user = Auth::user();
        $today = Carbon::today()->toDateString();
        $attendance = Attendance::where('user_id', $user->id)
            ->whereDate('date', $today)
            ->first();

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
        $attendance = Attendance::where('user_id', $user->id)
            ->whereDate('date', $today)
            ->first();

        if ($attendance) {
            $break = $attendance->breaks()->whereNull('end_time')->first();
            if ($break) {
                $break->end_time = Carbon::now();
                $break->duration = Carbon::parse($break->start_time)
                    ->diffInMinutes(Carbon::parse($break->end_time));
                $break->save();

                // 休憩時間の計算
                $totalBreakMinutes = $attendance->breaks()
                    ->whereNotNull('start_time')
                    ->whereNotNull('end_time')
                    ->get()
                    ->sum(function ($break) {
                        $startTime = Carbon::parse($break->start_time);
                        $endTime = Carbon::parse($break->end_time);
                        return max(0, $endTime->diffInMinutes($startTime));
                    });

                // 休憩時間を保存
                $formattedBreakTime = sprintf('%02d:%02d', 
                    intdiv($totalBreakMinutes, 60), 
                    $totalBreakMinutes % 60
                );
                $attendance->total_break_time = $formattedBreakTime;

                // 合計勤務時間の計算（退勤済みの場合のみ）
                if ($attendance->clock_out) {
                    $clockIn = Carbon::parse($attendance->clock_in);
                    $clockOut = Carbon::parse($attendance->clock_out);
                    $totalWorkMinutes = $clockOut->diffInMinutes($clockIn);
                    $actualWorkMinutes = max(0, $totalWorkMinutes - $totalBreakMinutes);

                    $formattedWorkTime = sprintf('%02d:%02d', 
                        intdiv($actualWorkMinutes, 60), 
                        $actualWorkMinutes % 60
                    );
                    $attendance->total_work_time = $formattedWorkTime;
                }

                $attendance->save();
                Log::info('Break time updated', [
                    'attendance_id' => $attendance->id,
                    'break_id' => $break->id,
                    'start_time' => $break->start_time,
                    'end_time' => $break->end_time,
                    'duration' => $break->duration,
                    'total_break_time' => $attendance->total_break_time,
                    'total_work_time' => $attendance->total_work_time
                ]);
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
            ->whereNotNull('clock_out')
            ->orderBy('date', 'asc')
            ->get();

        foreach ($attendances as $attendance) {
            try {
                // 休憩時間の計算
                $breaks = $attendance->breaks()
                    ->whereNotNull('start_time')
                    ->whereNotNull('end_time')
                    ->get();

                $totalBreakMinutes = 0;
                foreach ($breaks as $break) {
                    $startTime = Carbon::parse($break->start_time);
                    $endTime = Carbon::parse($break->end_time);
                    if ($startTime->lt($endTime)) {
                        $breakDuration = $startTime->diffInMinutes($endTime);
                        $totalBreakMinutes += $breakDuration;
                    }
                }

                // 勤務時間の計算
                $clockIn = Carbon::parse($attendance->clock_in);
                $clockOut = Carbon::parse($attendance->clock_out);
                
                if ($clockIn->lt($clockOut)) {
                    $totalWorkMinutes = $clockIn->diffInMinutes($clockOut);
                    $actualWorkMinutes = max(0, $totalWorkMinutes - $totalBreakMinutes);

                    // 休憩時間をフォーマット
                    $formattedBreakTime = sprintf('%02d:%02d', 
                        intdiv($totalBreakMinutes, 60), 
                        $totalBreakMinutes % 60
                    );

                    // 勤務時間をフォーマット
                    $formattedWorkTime = sprintf('%02d:%02d', 
                        intdiv($actualWorkMinutes, 60), 
                        $actualWorkMinutes % 60
                    );

                    // 値を更新
                    $attendance->update([
                        'total_break_time' => $formattedBreakTime,
                        'total_work_time' => $formattedWorkTime
                    ]);

                    Log::info('Attendance times updated', [
                        'attendance_id' => $attendance->id,
                        'date' => $attendance->date,
                        'clock_in' => $clockIn->format('Y-m-d H:i:s'),
                        'clock_out' => $clockOut->format('Y-m-d H:i:s'),
                        'total_break_time' => $formattedBreakTime,
                        'total_work_time' => $formattedWorkTime,
                        'break_minutes' => $totalBreakMinutes,
                        'work_minutes' => $actualWorkMinutes
                    ]);
                } else {
                    Log::warning('Invalid time order detected', [
                        'attendance_id' => $attendance->id,
                        'clock_in' => $attendance->clock_in,
                        'clock_out' => $attendance->clock_out
                    ]);
                }
            } catch (\Exception $e) {
                Log::error('Error calculating attendance times', [
                    'attendance_id' => $attendance->id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return view('attendance.list', [
            'attendances' => $attendances,
            'currentMonth' => $startDate,
            'previousMonth' => $startDate->copy()->subMonth()->format('Y-m'),
            'nextMonth' => $startDate->copy()->addMonth()->format('Y-m')
        ]);
    }

    public function show($id)
    {
        $attendance = Attendance::with(['user', 'correctionRequests'])->findOrFail($id);

        $pendingRequest = $attendance->correctionRequests()->where('status', 'pending')->first();

        return view('attendance.detail', compact('attendance', 'pendingRequest'));

    }
}
