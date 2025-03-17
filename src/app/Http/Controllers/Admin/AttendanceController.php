<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Attendance;
use App\Models\User;
use Carbon\Carbon;

class AttendanceController extends Controller
{
    public function index(Request $request)
    {
        $date = $request->input('date', Carbon::today()->format('Y-m-d'));
        $attendances = Attendance::with('user')
            ->whereDate('date', $date)
            ->orderBy('user_id')
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
            ->orderBy('date')
            ->get();

        return view('admin.attendance.staff', [
            'user' => $user,
            'attendances' => $attendances,
            'month' => $month
        ]);
    }

    public function show($id)
    {
        $attendance = Attendance::with(['user', 'breaks'])->findOrFail($id);
        
        // 日付と時刻データをCarbonインスタンスに変換
        $attendance->date = Carbon::parse($attendance->date);
        $attendance->clock_in = $attendance->clock_in ? Carbon::parse($attendance->clock_in) : null;
        $attendance->clock_out = $attendance->clock_out ? Carbon::parse($attendance->clock_out) : null;
        
        return view('attendance.detail', [
            'attendance' => $attendance,
            'isAdmin' => true,
            'pendingRequest' => false
        ]);
    }

    public function update(Request $request, $id)
    {
        $attendance = Attendance::findOrFail($id);
        
        $validated = $request->validate([
            'clock_in' => 'required|date_format:H:i',
            'clock_out' => 'required|date_format:H:i',
            'reason' => 'nullable|string|max:255',
            'break_start' => 'array',
            'break_start.*' => 'nullable|date_format:H:i',
            'break_end' => 'array',
            'break_end.*' => 'nullable|date_format:H:i'
        ]);

        // 日付文字列を作成
        $dateStr = Carbon::parse($attendance->date)->format('Y-m-d');
        
        $attendance->update([
            'clock_in' => $dateStr . ' ' . $validated['clock_in'],
            'clock_out' => $dateStr . ' ' . $validated['clock_out'],
            'reason' => $validated['reason']
        ]);

        // 既存の休憩を削除
        $attendance->breaks()->delete();

        // 新しい休憩を追加
        $breakStarts = $request->input('break_start', []);
        $breakEnds = $request->input('break_end', []);

        foreach ($breakStarts as $index => $start) {
            if (!empty($start) && !empty($breakEnds[$index])) {
                $attendance->breaks()->create([
                    'start_time' => $dateStr . ' ' . $start,
                    'end_time' => $dateStr . ' ' . $breakEnds[$index]
                ]);
            }
        }

        return redirect()->route('admin.attendance.show', ['id' => $id])
            ->with('message', '勤怠情報を更新しました。');
    }
}
