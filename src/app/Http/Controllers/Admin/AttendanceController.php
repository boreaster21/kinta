<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Attendance;
use App\Models\User;
use App\Models\Role;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttendanceController extends Controller
{
    public function list(Request $request)
    {
        $date = $request->input('date') ? Carbon::parse($request->input('date')) : Carbon::today();

        $attendances = Attendance::whereDate('date', $date)
            ->whereHas('user.role', function ($query) {
                $query->where('name', 'user');
            })
            ->with(['user', 'correctionRequests' => function ($query) {
                $query->where('status', 'approved');
            }])
            ->get();

        return view('admin.attendance.list', [
            'attendances' => $attendances,
            'date' => $date
        ]);
    }

    public function staffAttendance(Request $request, $id)
    {
        $user = User::findOrFail($id);
        $month = $request->input('month', Carbon::now()->format('Y-m'));
        $attendances = Attendance::where('user_id', $id)
            ->whereYear('date', Carbon::parse($month)->year)
            ->whereMonth('date', Carbon::parse($month)->month)
            ->with(['correctionRequests' => function ($query) {
                $query->where('status', 'approved');
            }])
            ->orderBy('date')
            ->get()
            ->map(function ($attendance) {
                if ($attendance->correctionRequests->isNotEmpty()) {
                    foreach ($attendance->correctionRequests as $request) {
                        if ($request->status === 'approved') {
                            $date = $attendance->date->format('Y-m-d');
                            $time = $request->requested_value;
                            $attendance->{$request->correction_type} = Carbon::parse($date . ' ' . $time);
                        }
                    }
                }
                return $attendance;
            });

        return view('admin.attendance.staff', [
            'user' => $user,
            'attendances' => $attendances,
            'month' => $month
        ]);
    }

    public function show($id)
    {
        $attendance = Attendance::with(['user', 'breaks', 'correctionRequests' => function ($query) {
            $query->where('status', 'approved')->orderBy('approved_at', 'desc');
        }])->findOrFail($id);

        $displayData = [];
        $latestApprovedRequest = $attendance->correctionRequests->first();
        $date = Carbon::parse($attendance->date)->format('Y-m-d');

        if ($latestApprovedRequest) {
            $displayData['clock_in'] = Carbon::parse($latestApprovedRequest->clock_in);
            $displayData['clock_out'] = Carbon::parse($latestApprovedRequest->clock_out);
            $displayData['breaks'] = collect($latestApprovedRequest->break_start ?? [])->map(function ($start, $index) use ($latestApprovedRequest, $date) {
                $endTime = $latestApprovedRequest->break_end[$index] ?? null;
                return (object)[
                    'start_time' => $start ? Carbon::parse($date . ' ' . $start) : null,
                    'end_time' => $endTime ? Carbon::parse($date . ' ' . $endTime) : null,
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

        return view('attendance.detail', [
            'attendance' => $attendance,
            'isAdmin' => true,
            'pendingRequest' => false,
            'correctionHistory' => $correctionHistory,
            'displayData' => $displayData
        ]);
    }

    public function update(Request $request, $id)
    {
        $attendance = Attendance::findOrFail($id);

        $rules = [
            'date' => 'required|date',
            'clock_in' => 'required|date_format:H:i',
            'clock_out' => 'required|date_format:H:i',
            'breaks' => 'nullable|array',
            'breaks.*.start_time' => 'nullable|date_format:H:i|required_with:breaks.*.end_time',
            'breaks.*.end_time' => 'nullable|date_format:H:i|required_with:breaks.*.start_time',
            'reason' => 'required|string|max:1000',
        ];

        $messages = [
            'date.required' => '日付は必須です。',
            'date.date' => '日付の形式が正しくありません。',
            'clock_in.required' => '出勤時間は必須です。',
            'clock_in.date_format' => '出勤時間の形式が正しくありません (HH:MM)。',
            'clock_out.required' => '退勤時間は必須です。',
            'clock_out.date_format' => '退勤時間の形式が正しくありません (HH:MM)。',
            'breaks.*.start_time.date_format' => '休憩開始時間の形式が正しくありません (HH:MM)。',
            'breaks.*.start_time.required_with' => '休憩終了時間を入力する場合、休憩開始時間も入力してください。',
            'breaks.*.end_time.date_format' => '休憩終了時間の形式が正しくありません (HH:MM)。',
            'breaks.*.end_time.required_with' => '休憩開始時間を入力する場合、休憩終了時間も入力してください。',
            'reason.required' => '備考を記入してください。',
            'reason.max' => '備考は1000文字以内で入力してください。',
        ];

        $validator = Validator::make($request->all(), $rules, $messages);

        $validator->after(function ($validator) use ($request) {
            $dateInput = $request->input('date');
            $clockInInput = $request->input('clock_in');
            $clockOutInput = $request->input('clock_out');

            if (!$dateInput || !$clockInInput || !$clockOutInput || $validator->errors()->hasAny(['date', 'clock_in', 'clock_out'])) {
                return;
            }

            try {
                $date = Carbon::parse($dateInput);
                $clockIn = $date->copy()->setTimeFromTimeString($clockInInput);
                $clockOut = $date->copy()->setTimeFromTimeString($clockOutInput);

                if ($clockIn->gt($clockOut)) {
                    $validator->errors()->add('clock_in', '出勤時間もしくは退勤時間が不適切な値です。');
                }

                $breaks = $request->input('breaks', []);
                foreach ($breaks as $index => $break) {
                    $breakStartInput = $break['start_time'] ?? null;
                    $breakEndInput = $break['end_time'] ?? null;

                    if (!empty($breakStartInput) && !empty($breakEndInput)) {
                        if (!$validator->errors()->has("breaks.{$index}.start_time") && !$validator->errors()->has("breaks.{$index}.end_time")) {
                            $breakStart = $date->copy()->setTimeFromTimeString($breakStartInput);
                            $breakEnd = $date->copy()->setTimeFromTimeString($breakEndInput);
                            if ($breakStart->lt($clockIn) || $breakEnd->gt($clockOut)) {
                                $validator->errors()->add("breaks.{$index}.start_time", '休憩時間が勤務時間外です。');
                            }
                            if ($breakStart->gt($breakEnd)) {
                                $validator->errors()->add("breaks.{$index}.end_time", '休憩終了時間は休憩開始時間より後に設定してください。');
                            }
                        }
                    } elseif (!empty($breakStartInput) || !empty($breakEndInput)) {
                        $validator->errors()->add("breaks.{$index}.start_time", '休憩開始時間と終了時間の両方を入力してください。');
                    }
                }
            } catch (\Exception $e) {
                $validator->errors()->add('date', '日付または時刻の形式が無効です。');
            }
        });

        $validated = $validator->validate();

        try {
            DB::beginTransaction();

            $newDateStr = Carbon::parse($validated['date'])->format('Y-m-d');

            $attendance->date = $newDateStr;
            $attendance->clock_in = Carbon::parse($newDateStr . ' ' . $validated['clock_in']);
            $attendance->clock_out = Carbon::parse($newDateStr . ' ' . $validated['clock_out']);
            $attendance->reason = $validated['reason'];

            $attendance->breaks()->delete();
            if (!empty($validated['breaks'])) {
                foreach ($validated['breaks'] as $break) {
                    if (!empty($break['start_time']) && !empty($break['end_time'])) {
                        $startTime = Carbon::parse($newDateStr . ' ' . $break['start_time']);
                        $endTime = Carbon::parse($newDateStr . ' ' . $break['end_time']);

                        if ($startTime->lt($endTime)) {
                            $attendance->breaks()->create([
                                'start_time' => $startTime,
                                'end_time' => $endTime,
                                'duration' => $startTime->diffInMinutes($endTime)
                            ]);
                        } else {
                        }
                    }
                }
            }

            $attendance->save();

            $attendance->calculateTotalBreakTime();
            $attendance->calculateTotalWorkTime();
            $attendance->save();

            DB::commit();

            $staffId = $attendance->user_id;
            $month = Carbon::parse($attendance->date)->format('Y-m');
            return redirect()->route('admin.staff.monthly_attendance', ['id' => $staffId, 'month' => $month])
                            ->with('success', '勤怠情報を更新しました。');

        } catch (\Exception $e) {
            DB::rollBack();

            return back()->withInput()->withErrors(['error' => '勤怠情報の更新中にエラーが発生しました。']);
        }
    }

    public function exportMonthlyCsv(Request $request, $id, $month)
    {
        $user = User::findOrFail($id);
        $targetMonth = Carbon::parse($month);
        $startDate = $targetMonth->copy()->startOfMonth();
        $endDate = $targetMonth->copy()->endOfMonth();

        $attendances = Attendance::where('user_id', $id)
            ->whereBetween('date', [$startDate, $endDate])
            ->orderBy('date')
            ->get();

        $csvHeader = ['日付', '出勤時刻', '退勤時刻', '休憩時間', '勤務時間'];
        $fileName = $targetMonth->format('Ym') . '_' . $user->name . '_attendance.csv';

        $response = new StreamedResponse(function() use ($attendances, $csvHeader) {
            $handle = fopen('php://output', 'w');

            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, $csvHeader);

            foreach ($attendances as $attendance) {
                $rowData = [
                    Carbon::parse($attendance->date)->format('Y/m/d'),
                    $attendance->clock_in ? Carbon::parse($attendance->clock_in)->format('H:i') : '',
                    $attendance->clock_out ? Carbon::parse($attendance->clock_out)->format('H:i') : '',
                    $attendance->total_break_time ?? '00:00',
                    $attendance->total_work_time ?? '00:00'
                ];
                fputcsv($handle, $rowData);
            }

            fclose($handle);
        }, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
        ]);

        return $response;
    }
}
