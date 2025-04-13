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
use Illuminate\Support\Facades\App;

class AttendanceController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $originalLocale = App::getLocale();
        App::setLocale('ja');
        Carbon::setLocale('ja');

        $now = Carbon::now();
        $date = $now->translatedFormat('Y年n月j日 (D)');
        $date = str_replace(
            ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'],
            ['日', '月', '火', '水', '木', '金', '土'],
            $date
        );

        $currentTime = $now->format('H:i');

        App::setLocale($originalLocale);
        Carbon::setLocale(config('app.locale'));

        $attendance = Attendance::where('user_id', $user->id)
            ->whereDate('date', $now->toDateString())
            ->first();

        $status = '勤務外';
        if ($attendance) {
            if ($attendance->clock_out) {
                $status = '退勤済';
            } else {
                $latestBreak = $attendance->breaks()->latest('start_time')->first();
                if ($latestBreak && !$latestBreak->end_time) {
                    $status = '休憩中';
                } else {
                    $status = '出勤中';
                }
            }
        }

        return view('attendance.index', [
            'date' => $date,
            'currentTime' => $currentTime,
            'status' => $status,
        ]);
    }

    public function clockIn()
    {
        try {
            $user = Auth::user();
            $now = Carbon::now();

            if ($now->hour < 4 || $now->hour >= 22) {
                return redirect()
                    ->route('attendance.index')
                    ->with('error', '打刻可能時間外です（4:00-22:00）');
            }

            if (Attendance::where('user_id', $user->id)
                ->whereDate('date', $now->toDateString())
                ->exists()) {
                return redirect()
                    ->route('attendance.index')
                    ->with('error', '本日の出勤打刻は既に行われています。');
            }

            Attendance::create([
                'user_id' => $user->id,
                'date' => $now->toDateString(),
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
            $now = Carbon::now();

            $attendance = Attendance::where('user_id', $user->id)
                ->whereDate('date', $now->toDateString())
                ->first();

            if ($attendance && !$attendance->clock_out) {
                DB::beginTransaction();
                try {
                    $latestBreak = $attendance->breaks()->latest('start_time')->first();
                    if ($latestBreak && !$latestBreak->end_time) {
                        $latestBreak->end_time = $now;
                        $latestBreak->duration = $latestBreak->start_time->diffInMinutes($now);
                        $latestBreak->save();

                        $attendance->calculateTotalBreakTime();

                    }

                    $attendance->clock_out = $now;
                    $attendance->calculateTotalWorkTime();
                    $attendance->save();
                    DB::commit();
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
        $now = Carbon::now();

        $attendance = Attendance::where('user_id', $user->id)
            ->whereDate('date', $now->toDateString())
            ->whereNotNull('clock_in')
            ->whereNull('clock_out')
            ->first();

        if (!$attendance) {
            return redirect()->route('attendance.index')->with('error', '出勤中ではないため休憩を開始できません。');
        }

        $latestBreak = $attendance->breaks()->latest('start_time')->first();
        if ($latestBreak && !$latestBreak->end_time) {
            return redirect()->route('attendance.index')->with('warning', '既に休憩中です。');
        }

        $attendance->breaks()->create([
            'start_time' => $now,
        ]);

        return redirect()->route('attendance.index')->with('success', '休憩を開始しました。');
    }

    public function breakEnd()
    {
        $user = Auth::user();
        $now = Carbon::now();

        $attendance = Attendance::where('user_id', $user->id)
            ->whereDate('date', $now->toDateString())
            ->whereNotNull('clock_in')
            ->whereNull('clock_out')
            ->first();

        if (!$attendance) {
            return redirect()->route('attendance.index')->with('error', '出勤記録が見つかりません。');
        }

        $latestBreak = $attendance->breaks()->latest('start_time')->first();

        if (!$latestBreak || $latestBreak->end_time) {
            return redirect()->route('attendance.index')->with('error', '現在休憩中ではありません。');
        }

        $latestBreak->end_time = $now;
        $startTime = Carbon::parse($latestBreak->start_time);
        $latestBreak->duration = $startTime->diffInMinutes($now);
        $latestBreak->save();

        $attendance->calculateTotalBreakTime();
        $attendance->save();

        return redirect()->route('attendance.index')->with('success', '休憩を終了しました。');
    }

    public function list(Request $request)
    {
        $user = Auth::user();
        $monthInput = $request->input('month', Carbon::now()->format('Y-m'));
        $month = Carbon::parse($monthInput . '-01');

        $attendances = Attendance::where('user_id', $user->id)
            ->whereYear('date', $month->year)
            ->whereMonth('date', $month->month)
            ->orderBy('date', 'asc')
            ->get();

        $formattedAttendances = $attendances->map(function ($attendance) {
            $date = Carbon::parse($attendance->date);
            $days = ['日', '月', '火', '水', '木', '金', '土'];
            $dayOfWeek = $days[$date->dayOfWeek];

            $formatTime = function($timeString) {
                if (empty($timeString) || $timeString === '00:00') {
                    return '0:00';
                }
                if ($timeString === '0:00') return '0:00';
                if (str_starts_with($timeString, '0') && strlen($timeString) > 4) {
                    return substr($timeString, 1);
                }
                return $timeString;
            };

            return [
                'id' => $attendance->id,
                'date' => $date->format('m/d') . ' (' . $dayOfWeek . ')',
                'clock_in' => $attendance->clock_in ? Carbon::parse($attendance->clock_in)->format('H:i') : '-',
                'clock_out' => $attendance->clock_out ? Carbon::parse($attendance->clock_out)->format('H:i') : '-',
                'break_time' => $formatTime($attendance->total_break_time),
                'total_time' => $formatTime($attendance->total_work_time),
            ];
        });

        return view('attendance.list', [
            'attendances' => $formattedAttendances,
            'month' => $month->format('Y-m'),
            'previousMonth' => $month->copy()->subMonth()->format('Y-m'),
            'nextMonth' => $month->copy()->addMonth()->format('Y-m'),
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