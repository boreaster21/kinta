<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\StampCorrectionRequest;
use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class StampCorrectionRequestController extends Controller
{
    public function approve(StampCorrectionRequest $request)
    {
        try {
            DB::beginTransaction();

            $attendance = $request->attendance;
            $date = Carbon::parse($attendance->date)->format('Y-m-d');

            $request->original_clock_in = $attendance->clock_in;
            $request->original_clock_out = $attendance->clock_out;
            $request->original_break_start = $attendance->breaks->map(function ($break) {
                return Carbon::parse($break->start_time)->format('H:i');
            })->toArray();
            $request->original_break_end = $attendance->breaks->map(function ($break) {
                return Carbon::parse($break->end_time)->format('H:i');
            })->toArray();
            $request->original_reason = $attendance->reason;
            $request->original_date = $attendance->date;

            $attendance->date = Carbon::parse($request->date)->format('Y-m-d');
            $newDateStr = $attendance->date;

            $clockInTimeStr = $request->clock_in->format('H:i');
            $clockOutTimeStr = $request->clock_out->format('H:i');

            $newDate = Carbon::parse($newDateStr)->startOfDay();
            $attendance->clock_in = $newDate->copy()->setTimeFromTimeString($clockInTimeStr);
            $attendance->clock_out = $newDate->copy()->setTimeFromTimeString($clockOutTimeStr);

            if (!empty($request->reason)) {
                $attendance->reason = $request->reason;
            }

            $attendance->save();

            $attendance->breaks()->delete();

            $breakStarts = $request->break_start ?? [];
            $breakEnds = $request->break_end ?? [];

            foreach ($breakStarts as $index => $start) {
                if (!isset($breakEnds[$index]) || empty($start) || empty($breakEnds[$index])) {
                    continue;
                }

                $breakStartTimeStr = Carbon::parse($start)->format('H:i');
                $breakEndTimeStr = Carbon::parse($breakEnds[$index])->format('H:i');

                $breakStartDateTime = $newDate->copy()->setTimeFromTimeString($breakStartTimeStr);
                $breakEndDateTime = $newDate->copy()->setTimeFromTimeString($breakEndTimeStr);

                $break = $attendance->breaks()->create([
                    'start_time' => $breakStartDateTime,
                    'end_time' => $breakEndDateTime,
                    'duration' => $breakEndDateTime->diffInMinutes($breakStartDateTime)
                ]);
            }

            $attendance->calculateTotalBreakTime();
            $attendance->calculateTotalWorkTime();
            $attendance->save();

            $totalBreakTime = $attendance->total_break_time;
            $totalWorkTime = $attendance->total_work_time;

            $request->status = 'approved';
            $request->approved_by = Auth::id();
            $request->approved_at = now();
            $request->save();

            DB::commit();

            return redirect()->route('stamp_correction_request.list')
                ->with('message', '修正申請を承認しました');

        } catch (\Exception $e) {
            DB::rollBack();

            return back()->withErrors(['error' => '修正申請の承認中にエラーが発生しました']);
        }
    }

    public function reject($id)
    {
        $correctionRequest = StampCorrectionRequest::findOrFail($id);

        $correctionRequest->update([
            'status' => 'rejected',
            'rejected_at' => now()
        ]);

        return redirect()->route('stamp_correction_request.list')
            ->with('message', '申請を却下しました。');
    }

    public function showApproveForm($id)
    {
        $request = StampCorrectionRequest::with(['user', 'attendance.breaks'])->findOrFail($id);

        $attendance = $request->attendance;
        $request->original_clock_in_display = $attendance->clock_in ? $attendance->clock_in->format('H:i') : '-';
        $request->original_clock_out_display = $attendance->clock_out ? $attendance->clock_out->format('H:i') : '-';
        $request->original_breaks_display = $attendance->breaks->map(function ($break) {
            return ($break->start_time ? Carbon::parse($break->start_time)->format('H:i') : '-') . ' 〜 ' . ($break->end_time ? Carbon::parse($break->end_time)->format('H:i') : '-');
        })->toArray();
        $request->original_reason_display = $attendance->reason ?? '-';

        return view('admin.stamp_correction_request.approve', [
            'request' => $request
        ]);
    }
}