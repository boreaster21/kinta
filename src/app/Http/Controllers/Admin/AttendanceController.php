<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Attendance;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttendanceController extends Controller
{
    public function list(Request $request)
    {
        $date = $request->input('date') ? Carbon::parse($request->input('date')) : Carbon::today();
        $users = User::where('role_id', 2)->get(); // 一般ユーザーのみ取得
        $attendances = Attendance::whereDate('date', $date)
            ->with(['user', 'correctionRequests' => function ($query) {
                $query->where('status', 'approved');
            }])
            ->get();

        return view('admin.attendance.list', [
            'users' => $users,
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
                // 修正申請が承認されている場合、その値を反映
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
            'breaks' => 'array',
            'breaks.*.start_time' => 'nullable|date_format:H:i',
            'breaks.*.end_time' => 'nullable|date_format:H:i',
            'reason' => 'nullable|string|max:255',
        ]);

        try {
            DB::beginTransaction();

            // 出退勤時間の更新
            $date = $attendance->date->format('Y-m-d');
            $attendance->clock_in = Carbon::parse($date . ' ' . $validated['clock_in']);
            $attendance->clock_out = Carbon::parse($date . ' ' . $validated['clock_out']);
            $attendance->reason = $validated['reason'] ?? null;

            // 休憩時間の更新
            if (isset($validated['breaks'])) {
                $attendance->breaks()->delete();
                foreach ($validated['breaks'] as $break) {
                    if ($break['start_time'] && $break['end_time']) {
                        $attendance->breaks()->create([
                            'start_time' => Carbon::parse($date . ' ' . $break['start_time']),
                            'end_time' => Carbon::parse($date . ' ' . $break['end_time']),
                        ]);
                    }
                }
            }

            // 合計時間の計算
            $attendance->calculateTotalBreakTime();
            $attendance->calculateTotalWorkTime();
            $attendance->save();

            DB::commit();

            return redirect()->route('admin.attendance.list')
                ->with('success', '勤怠情報を更新しました。');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating attendance', [
                'error' => $e->getMessage(),
                'attendance_id' => $id
            ]);

            return back()->withErrors(['error' => '勤怠情報の更新中にエラーが発生しました。']);
        }
    }

    public function exportMonthlyCsv(Request $request, $id, $month)
    {
        $user = User::findOrFail($id);
        $targetMonth = Carbon::parse($month);
        $startDate = $targetMonth->copy()->startOfMonth();
        $endDate = $targetMonth->copy()->endOfMonth();

        // 対象月の勤怠データを取得 (staffAttendance と同様のロジックで良いか確認)
        $attendances = Attendance::where('user_id', $id)
            ->whereBetween('date', [$startDate, $endDate])
            // ->with(...) // CSVに必要なリレーションがあれば Eager Load
            ->orderBy('date')
            ->get();

        $csvHeader = ['日付', '出勤時刻', '退勤時刻', '休憩時間', '勤務時間'];
        $fileName = $targetMonth->format('Ym') . '_' . $user->name . '_attendance.csv';

        $response = new StreamedResponse(function() use ($attendances, $csvHeader) {
            $handle = fopen('php://output', 'w');

            // BOM を追加してExcelでの文字化けを防ぐ
            fwrite($handle, "\xEF\xBB\xBF");

            // ヘッダー行を書き込み
            fputcsv($handle, $csvHeader);

            // データ行を書き込み
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
