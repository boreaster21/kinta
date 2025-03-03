<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\StampCorrectionRequest;
use Illuminate\Support\Facades\Auth;

class StampCorrectionRequestController extends Controller
{
    public function index()
    {
        $pendingRequests = StampCorrectionRequest::where('user_id', auth()->id())
            ->where('status', 'pending')
            ->latest()
            ->get();

        $approvedRequests = StampCorrectionRequest::where('user_id', auth()->id())
            ->where('status', 'approved')
            ->latest()
            ->get();

        return view('stamp_correction_request.list', compact('pendingRequests', 'approvedRequests'));
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
