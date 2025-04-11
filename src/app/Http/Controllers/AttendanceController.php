<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Attendance;
use App\Models\BreakTime;
use App\Models\StampCorrectionRequest;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Log;
use App\Http\Requests\AttendanceRequest;
use Illuminate\Support\Facades\DB;

class AttendanceController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $today = CarbonImmutable::today();
        $attendance = Attendance::where('user_id', $user->id)
            ->whereDate('date', $today)
            ->with('breaks')
            ->first();

        $dateFormatted = $today->translatedFormat('Y年m月d日 (D)');
        $dateFormatted = str_replace(
            ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'],
            ['日', '月', '火', '水', '木', '金', '土'],
            $dateFormatted
        );

        $workInfo = [
            'clock_in' => null,
            'clock_out' => null,
            'total_break_time' => '00:00',
            'total_work_time' => '00:00',
            'current_break_start' => null,
        ];

        if (!$attendance) {
            $status = '勤務外';
        } elseif ($attendance->clock_in && !$attendance->clock_out) {
            if ($attendance->breaks()->whereNull('end_time')->exists()) {
                $status = '休憩中';
                $currentBreak = $attendance->breaks()->whereNull('end_time')->first();
                $workInfo['current_break_start'] = Carbon::parse($currentBreak->start_time)->format('H:i');
            } else {
                $status = '出勤中';
            }
            $workInfo['clock_in'] = Carbon::parse($attendance->clock_in)->format('H:i');
        } elseif ($attendance->clock_out) {
            $status = '退勤済';
            $workInfo['clock_in'] = Carbon::parse($attendance->clock_in)->format('H:i');
            $workInfo['clock_out'] = Carbon::parse($attendance->clock_out)->format('H:i');
            $workInfo['total_break_time'] = $attendance->total_break_time;
            $workInfo['total_work_time'] = $attendance->total_work_time;
        } else {
            $status = '勤務外';
        }

        return view('attendance.index', [
            'attendance' => $attendance,
            'currentTime' => Carbon::now()->format('H:i'),
            'date' => $dateFormatted,
            'status' => $status,
            'workInfo' => $workInfo,
        ]);
    }

    public function clockIn()
    {
        try {
            $user = Auth::user();
            $today = Carbon::today();
            $now = Carbon::now();

            if (Attendance::where('user_id', $user->id)
                ->whereDate('date', $today)
                ->exists()) {
                return redirect()
                    ->route('attendance.index')
                    ->with('error', '本日の出勤打刻は既に行われています。');
            }

            $startTime = Carbon::today()->setHour(4)->setMinute(0);
            $endTime = Carbon::today()->setHour(22)->setMinute(0);

            if ($now->lt($startTime) || $now->gt($endTime)) {
                return redirect()
                    ->route('attendance.index')
                    ->with('error', '打刻可能時間外です（4:00-22:00）');
            }

            Attendance::create([
                'user_id' => $user->id,
                'date' => $today,
                'clock_in' => $now,
            ]);

            return redirect()
                ->route('attendance.index')
                ->with('success', '出勤を記録しました。');
        } catch (\Exception $e) {
            Log::error('Clock in failed', ['error' => $e->getMessage()]);
            return redirect()
                ->route('attendance.index')
                ->with('error', '打刻に失敗しました。');
        }
    }

    public function clockOut()
    {
        try {
            $user = Auth::user();
            $today = Carbon::today()->toDateString();
            $attendance = Attendance::where('user_id', $user->id)
                ->whereDate('date', $today)
                ->first();

            if ($attendance && !$attendance->clock_out) {
                DB::beginTransaction();
                try {
                    Log::info('Clock out process started', [
                        'attendance_id' => $attendance->id,
                        'current_clock_out' => $attendance->clock_out,
                        'current_total_break_time' => $attendance->total_break_time,
                        'current_total_work_time' => $attendance->total_work_time
                    ]);

                    $attendance->clock_out = Carbon::now();
                    
                    $breakMinutes = $attendance->calculateTotalBreakTime();
                    Log::info('Break time calculated', [
                        'attendance_id' => $attendance->id,
                        'break_minutes' => $breakMinutes,
                        'total_break_time' => $attendance->total_break_time
                    ]);

                    $attendance->calculateTotalWorkTime();
                    
                    $attendance->save();
                    
                    DB::commit();
                    
                    Log::info('Clock out completed', [
                        'attendance_id' => $attendance->id,
                        'clock_in' => $attendance->clock_in,
                        'clock_out' => $attendance->clock_out,
                        'total_break_time' => $attendance->total_break_time,
                        'total_work_time' => $attendance->total_work_time
                    ]);
                } catch (\Exception $e) {
                    DB::rollBack();
                    Log::error('Clock out process failed', [
                        'attendance_id' => $attendance->id,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                    throw $e;
                }
            }

            return redirect()
                ->route('attendance.index')
                ->with('success', '退勤を記録しました。');
        } catch (\Exception $e) {
            Log::error('Clock out failed', ['error' => $e->getMessage()]);
            return redirect()
                ->route('attendance.index')
                ->with('error', '退勤の記録に失敗しました。');
        }
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

                $totalBreakMinutes = $attendance->breaks()
                    ->whereNotNull('start_time')
                    ->whereNotNull('end_time')
                    ->get()
                    ->sum(function ($break) {
                        $startTime = Carbon::parse($break->start_time);
                        $endTime = Carbon::parse($break->end_time);
                        return max(0, $endTime->diffInMinutes($startTime));
                    });

                $formattedBreakTime = sprintf('%02d:%02d', 
                    intdiv($totalBreakMinutes, 60), 
                    $totalBreakMinutes % 60
                );
                $attendance->total_break_time = $formattedBreakTime;

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
        $previousMonth = $startDate->copy()->subMonth()->format('Y-m');
        $nextMonth = $startDate->copy()->addMonth()->format('Y-m');

        $attendances = Attendance::where('user_id', $user->id)
            ->whereBetween('date', [$startDate, $endDate])
            ->with(['breaks', 'correctionRequests' => function ($query) {
                $query->where('status', 'approved');
            }])
            ->orderBy('date', 'desc')
            ->get();

        $formattedAttendances = $attendances->map(function ($attendance) {
            $date = Carbon::parse($attendance->date);
            $days = ['日', '月', '火', '水', '木', '金', '土'];
            $dayOfWeekJp = $days[$date->dayOfWeek];
            $formattedDate = $date->format('m/d') . ' (' . $dayOfWeekJp . ')';

            $formatTime = function($timeString) {
                if (empty($timeString) || $timeString === '00:00') {
                    return '0:00';
                }
                if (str_starts_with($timeString, '0')) {
                    return substr($timeString, 1);
                }
                return $timeString;
            };

            return [
                'date' => $formattedDate,
                'clock_in' => $attendance->clock_in ? Carbon::parse($attendance->clock_in)->format('H:i') : '-',
                'clock_out' => $attendance->clock_out ? Carbon::parse($attendance->clock_out)->format('H:i') : '-',
                'break_time' => $formatTime($attendance->total_break_time),
                'total_time' => $formatTime($attendance->total_work_time),
                'id' => $attendance->id,
            ];
        });

        return view('attendance.list', [
            'attendances' => $formattedAttendances,
            'month' => $month,
            'previousMonth' => $previousMonth,
            'nextMonth' => $nextMonth,
        ]);
    }

    public function show($id)
    {
        $attendance = Attendance::with(['user', 'breaks', 'correctionRequests' => function ($query) {
            $query->where('status', 'approved')->orderBy('approved_at', 'desc');
        }])->findOrFail($id);
        $user = Auth::user();
        $isAdmin = $user->isAdmin();

        if (!$isAdmin && $attendance->user_id !== $user->id) {
            abort(403, '他のユーザーの勤怠情報は閲覧できません。');
        }

        $pendingRequest = $isAdmin ? false : $attendance->correctionRequests()
            ->where('status', 'pending')
            ->latest()
            ->first();

        $correctionHistory = $attendance->correctionRequests()
            ->where('status', 'approved')
            ->with('approvedBy')
            ->orderBy('approved_at', 'desc')
            ->get()
            ->map(function ($request) {
                return [
                    'id' => $request->id,
                    'created_at' => Carbon::parse($request->created_at),
                    'clock_in' => $request->clock_in,
                    'clock_out' => $request->clock_out,
                    'break_start' => $request->break_start,
                    'break_end' => $request->break_end,
                    'reason' => $request->reason,
                    'approved_at' => Carbon::parse($request->approved_at),
                    'approved_by' => $request->approvedBy?->name
                ];
            });

        $displayData = [];
        $latestApprovedRequest = $attendance->correctionRequests->first();

        if ($latestApprovedRequest) {
            $displayData['clock_in'] = Carbon::parse($latestApprovedRequest->clock_in);
            $displayData['clock_out'] = Carbon::parse($latestApprovedRequest->clock_out);
            $displayData['breaks'] = collect($latestApprovedRequest->break_start ?? [])->map(function ($start, $index) use ($latestApprovedRequest) {
                return (object)[
                    'start_time' => isset($latestApprovedRequest->break_end[$index]) ? Carbon::parse($start) : null,
                    'end_time' => isset($latestApprovedRequest->break_end[$index]) ? Carbon::parse($latestApprovedRequest->break_end[$index]) : null,
                ];
            })->filter(function ($break) {
                return $break->start_time && $break->end_time;
            });
            $displayData['reason'] = $latestApprovedRequest->reason;
        } else {
            $displayData['clock_in'] = $attendance->clock_in ? Carbon::parse($attendance->clock_in) : null;
            $displayData['clock_out'] = $attendance->clock_out ? Carbon::parse($attendance->clock_out) : null;
            $displayData['breaks'] = $attendance->breaks;
            $displayData['reason'] = $attendance->reason;
        }

        return view('attendance.detail', compact('attendance', 'pendingRequest', 'isAdmin', 'correctionHistory', 'displayData'));
    }

    public function request(AttendanceRequest $request, $id)
    {
        $attendance = Attendance::findOrFail($id);
        $user = Auth::user();

        if ($attendance->correctionRequests()->where('status', 'pending')->exists()) {
            return back()->withErrors(['error' => '既に承認待ちの申請があります']);
        }

        try {
            $breakStartsInput = $request->input('break_start', []);
            $breakEndsInput = $request->input('break_end', []);

            $breakStarts = array_values(array_filter($breakStartsInput, function($value) {
                return $value !== '' && $value !== null;
            }));
            $breakEnds = array_values(array_filter($breakEndsInput, function($value) {
                return $value !== '' && $value !== null;
            }));

            if (count($breakStarts) !== count($breakEnds)) {
                throw new \Exception('休憩開始時間と終了時間の数が一致しません');
            }

            foreach ($breakStarts as $index => $start) {
                if (!isset($breakEnds[$index]) || empty($start) || empty($breakEnds[$index])) {
                    continue;
                }

                if (!preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $start) ||
                    !preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $breakEnds[$index])) {
                    throw new \Exception('休憩時間の形式が正しくありません');
                }
            }

            $originalClockIn = $attendance->clock_in;
            $originalClockOut = $attendance->clock_out;
            $originalBreaks = $attendance->breaks;
            $originalBreakStart = $originalBreaks->pluck('start_time')->map(fn($time) => Carbon::parse($time)->format('H:i'))->toArray();
            $originalBreakEnd = $originalBreaks->pluck('end_time')->map(fn($time) => Carbon::parse($time)->format('H:i'))->toArray();
            $originalReason = $attendance->reason;
            $originalDate = $attendance->date;

            $correctionRequest = StampCorrectionRequest::create([
                'attendance_id' => $attendance->id,
                'user_id' => $user->id,
                'date' => $request->date,
                'clock_in' => $request->clock_in,
                'clock_out' => $request->clock_out,
                'break_start' => $breakStarts,
                'break_end' => $breakEnds,
                'reason' => $request->reason,
                'status' => 'pending',
                'original_date' => $originalDate,
                'original_clock_in' => $originalClockIn,
                'original_clock_out' => $originalClockOut,
                'original_break_start' => $originalBreakStart,
                'original_break_end' => $originalBreakEnd,
                'original_reason' => $originalReason,
            ]);

            Log::info('Correction request created', [
                'correction_request_id' => $correctionRequest->id,
                'attendance_id' => $attendance->id,
                'user_id' => $user->id,
                'break_starts' => $breakStarts,
                'break_ends' => $breakEnds
            ]);

            return redirect()->route('attendance.list')
                ->with('message', '修正申請を送信しました');

        } catch (\Exception $e) {
            Log::error('Error creating correction request', [
                'error' => $e->getMessage(),
                'attendance_id' => $attendance->id,
                'user_id' => $user->id,
                'trace' => $e->getTraceAsString()
            ]);

            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }
}
