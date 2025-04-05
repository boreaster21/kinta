<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\StampCorrectionRequest;
use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class StampCorrectionRequestController extends Controller
{
    public function list(Request $request)
    {
        $status = $request->query('status', 'pending');
        $query = StampCorrectionRequest::with(['user', 'attendance'])
            ->orderBy('created_at', 'desc');

        if ($status === 'pending') {
            $query->where('status', 'pending');
        } elseif ($status === 'processed') {
            $query->whereIn('status', ['approved', 'rejected']);
        }

        $requests = $query->get()->map(function ($request) {
            return [
                'id' => $request->id,
                'date' => Carbon::parse($request->attendance->date)->format('Y/m/d'),
                'clock_in' => Carbon::parse($request->clock_in)->format('H:i'),
                'clock_out' => Carbon::parse($request->clock_out)->format('H:i'),
                'break_times' => collect($request->break_times)->map(function ($break) {
                    return [
                        'start' => Carbon::parse($break['start'])->format('H:i'),
                        'end' => Carbon::parse($break['end'])->format('H:i')
                    ];
                }),
                'reason' => $request->reason,
                'status' => $request->status,
                'created_at' => Carbon::parse($request->created_at)->format('Y/m/d H:i'),
                'approved_at' => $request->approved_at ? Carbon::parse($request->approved_at)->format('Y/m/d H:i') : null,
                'user_name' => $request->user->name,
                'detail_url' => $request->status === 'approved'
                    ? route('admin.stamp_correction_request.approved', $request->id)
                    : ($request->status === 'pending' 
                        ? route('admin.stamp_correction_request.show', $request->id)
                        : route('admin.stamp_correction_request.approved', $request->id))
            ];
        });

        return view('admin.stamp_correction_request.list', compact('requests', 'status'));
    }

    public function approve(StampCorrectionRequest $request)
    {
        Log::info('Starting approval process', [
            'request_id' => $request->id,
            'attendance_id' => $request->attendance_id,
            'request_data' => $request->toArray(),
            'current_attendance' => $request->attendance->toArray()
        ]);

        try {
            DB::beginTransaction();

            $attendance = $request->attendance;
            $date = Carbon::parse($attendance->date)->format('Y-m-d');

            // 修正前の情報を保存
            $request->original_clock_in = $attendance->clock_in;
            $request->original_clock_out = $attendance->clock_out;
            $request->original_break_start = $attendance->breaks->map(function ($break) {
                return Carbon::parse($break->start_time)->format('H:i');
            })->toArray();
            $request->original_break_end = $attendance->breaks->map(function ($break) {
                return Carbon::parse($break->end_time)->format('H:i');
            })->toArray();
            $request->original_reason = $attendance->reason;
            $request->approved_by = Auth::id();
            $request->approved_at = now();

            // 出退勤時間の更新
            $attendance->clock_in = Carbon::parse($request->clock_in)->format('Y-m-d H:i:s');
            $attendance->clock_out = Carbon::parse($request->clock_out)->format('Y-m-d H:i:s');
            
            // 備考の更新
            if (!empty($request->reason)) {
                $attendance->reason = $request->reason;
            }
            
            $attendance->save();

            Log::info('Updated attendance times', [
                'attendance_id' => $attendance->id,
                'date' => $date,
                'clock_in' => $attendance->clock_in,
                'clock_out' => $attendance->clock_out,
                'reason' => $attendance->reason
            ]);

            // 既存の休憩時間を削除
            $attendance->breaks()->delete();

            // 休憩時間の作成
            $breakStarts = $request->break_start;
            $breakEnds = $request->break_end;

            foreach ($breakStarts as $index => $start) {
                if (!isset($breakEnds[$index]) || empty($start) || empty($breakEnds[$index])) {
                    continue;
                }

                $breakStart = Carbon::parse($date . ' ' . $start)->format('Y-m-d H:i:s');
                $breakEnd = Carbon::parse($date . ' ' . $breakEnds[$index])->format('Y-m-d H:i:s');

                $break = $attendance->breaks()->create([
                    'start_time' => $breakStart,
                    'end_time' => $breakEnd,
                    'duration' => Carbon::parse($breakEnd)->diffInMinutes(Carbon::parse($breakStart))
                ]);

                Log::info('Created break time', [
                    'attendance_id' => $attendance->id,
                    'break_index' => $index,
                    'break_start' => $breakStart,
                    'break_end' => $breakEnd,
                    'duration' => $break->duration
                ]);
            }

            // 休憩時間と勤務時間の再計算と Attendance モデルへの保存
            $attendance->calculateTotalBreakTime(); // メソッド内で $this->total_break_time が更新される
            $attendance->calculateTotalWorkTime();  // メソッド内で $this->total_work_time が更新される
            $attendance->save(); // 更新された合計時間を含む Attendance モデルを保存

            $totalBreakTime = $attendance->total_break_time; // ログ用に保存された値を取得
            $totalWorkTime = $attendance->total_work_time;   // ログ用に保存された値を取得

            Log::info('Final attendance state', [
                'attendance_id' => $attendance->id,
                'clock_in' => $attendance->clock_in,
                'clock_out' => $attendance->clock_out,
                'reason' => $attendance->reason,
                'total_break_time' => $totalBreakTime,
                'total_work_time' => $totalWorkTime,
                'breaks' => $attendance->breaks
            ]);

            // 修正申請のステータスを更新
            $request->status = 'approved';
            $request->save();

            DB::commit();

            return redirect()->route('stamp_correction_request.list')
                ->with('message', '修正申請を承認しました');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error during approval process', [
                'error' => $e->getMessage(),
                'request_id' => $request->id,
                'trace' => $e->getTraceAsString()
            ]);

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
        $request = StampCorrectionRequest::with(['user', 'attendance'])->findOrFail($id);
        
        return view('admin.stamp_correction_request.approve', [
            'request' => $request
        ]);
    }

    public function showApproved($id)
    {
        $request = StampCorrectionRequest::with(['user', 'attendance'])
            ->findOrFail($id);

        if ($request->status !== 'approved') {
            abort(404, '承認済みの修正申請のみ表示できます。');
        }

        $request->created_at = Carbon::parse($request->created_at);
        $request->approved_at = Carbon::parse($request->approved_at);
        $request->clock_in = Carbon::parse($request->clock_in);
        $request->clock_out = Carbon::parse($request->clock_out);
        $request->original_clock_in = Carbon::parse($request->original_clock_in);
        $request->original_clock_out = Carbon::parse($request->original_clock_out);

        return view('admin.stamp_correction_request.approved', compact('request'));
    }
} 