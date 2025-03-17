<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\StampCorrectionRequest;
use Carbon\Carbon;

class StampCorrectionRequestController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('status', 'pending');
        
        $query = StampCorrectionRequest::with(['user', 'attendance'])
            ->orderBy('created_at', 'desc');

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

        return view('admin.stamp_correction_request.list', [
            'requests' => $requests,
            'currentStatus' => $status
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

        return redirect()->route('admin.stamp_correction_request.list')
            ->with('message', '申請を承認しました。');
    }

    public function reject($id)
    {
        $correctionRequest = StampCorrectionRequest::findOrFail($id);
        
        $correctionRequest->update([
            'status' => 'rejected',
            'rejected_at' => now()
        ]);

        return redirect()->route('admin.stamp_correction_request.list')
            ->with('message', '申請を却下しました。');
    }

    public function showApproveForm($id)
    {
        $request = StampCorrectionRequest::with(['user', 'attendance'])->findOrFail($id);
        
        return view('admin.stamp_correction_request.approve', [
            'request' => $request
        ]);
    }
} 