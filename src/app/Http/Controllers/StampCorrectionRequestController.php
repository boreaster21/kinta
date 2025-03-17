<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\StampCorrectionRequest;
use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class StampCorrectionRequestController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('status', 'pending');
        $query = StampCorrectionRequest::with(['user', 'attendance'])
            ->orderBy('created_at', 'desc');

        // 管理者でない場合は自分の申請のみ表示
        if (!Auth::user()->isAdmin()) {
            $query->where('user_id', Auth::id());
        }

        // ステータスに応じてフィルタリング
        if ($status === 'pending') {
            $query->where('status', 'pending');
        } else {
            $query->whereIn('status', ['approved', 'rejected']);
        }

        $requests = $query->get()
            ->map(function ($request) {
                $request->created_at = Carbon::parse($request->created_at);
                $request->attendance->date = Carbon::parse($request->attendance->date);
                return $request;
            });

        return view('stamp_correction_request.list', [
            'requests' => $requests,
            'currentStatus' => $status,
            'isAdmin' => Auth::user()->isAdmin()
        ]);
    }

    public function showApproveForm($id)
    {
        $request = StampCorrectionRequest::with(['user', 'attendance'])->findOrFail($id);
        
        return view('stamp_correction_request.approve', [
            'request' => $request
        ]);
    }

    public function approve($id)
    {
        $correctionRequest = StampCorrectionRequest::findOrFail($id);
        $attendance = $correctionRequest->attendance;

        // 申請内容を反映
        $attendance->update([
            $correctionRequest->correction_type => $correctionRequest->requested_value
        ]);

        // 申請のステータスを更新
        $correctionRequest->update([
            'status' => 'approved',
            'approved_at' => now()
        ]);

        return redirect()->route('stamp_correction_request.list')
            ->with('message', '申請を承認しました。');
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

    public function store(Request $request, $id)
    {
        $request->validate([
            'clock_in' => 'required|date_format:H:i',
            'clock_out' => 'required|date_format:H:i|after:clock_in',
            'break_start' => 'nullable|date_format:H:i',
            'break_end' => 'nullable|date_format:H:i|after:break_start',
            'reason' => 'required|string|max:255',
        ]);

        StampCorrectionRequest::create([
            'user_id' => Auth::id(),
            'attendance_id' => $id,
            'clock_in' => $request->clock_in,
            'clock_out' => $request->clock_out,
            'break_start' => $request->break_start,
            'break_end' => $request->break_end,
            'reason' => $request->reason,
            'status' => 'pending',
        ]);

        return redirect()->route('stamp_correction_request.list')->with('success', '修正申請が送信されました');
    }
}
