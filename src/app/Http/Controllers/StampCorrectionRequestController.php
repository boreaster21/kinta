<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\StampCorrectionRequest;
use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StampCorrectionRequestController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $status = $request->query('status', 'pending');

        $query = StampCorrectionRequest::with(['user', 'attendance'])
            ->orderBy('created_at', 'desc');

        if (!$user->isAdmin()) {
            $query->where('user_id', $user->id);
        }

        if ($status === 'pending') {
            $query->where('status', 'pending');
        } else {
            $query->whereIn('status', ['approved', 'rejected']);
        }

        $requests = $query->get()
            ->map(function ($request) use ($user) {
                return [
                    'id' => $request->id,
                    'date' => Carbon::parse($request->attendance->date)->format('Y/m/d'),
                    'clock_in' => Carbon::parse($request->clock_in)->format('H:i'),
                    'clock_out' => Carbon::parse($request->clock_out)->format('H:i'),
                    'break_times' => collect($request->break_start)->map(function ($start, $index) use ($request) {
                        return $start . ' - ' . $request->break_end[$index];
                    })->join('<br>'),
                    'reason' => $request->reason,
                    'status' => $request->status,
                    'created_at' => Carbon::parse($request->created_at)->format('Y/m/d'),
                    'approved_at' => $request->approved_at ? Carbon::parse($request->approved_at)->format('Y/m/d H:i') : null,
                    'detail_url' => $request->status === 'approved'
                        ? route('stamp_correction_request.approved', $request->id)
                        : route('stamp_correction_request.show', $request->id)
                ];
            });

        return view('stamp_correction_request.list', [
            'requests' => $requests,
            'status' => $status,
            'currentStatus' => $status
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
        $request = StampCorrectionRequest::findOrFail($id);

        if (!Auth::user()->isAdmin()) {
            abort(403);
        }

        if ($request->status !== 'pending') {
            abort(400, 'この申請は既に処理済みです。');
        }

        try {
            DB::beginTransaction();

            $attendance = $request->attendance;
            $request->original_clock_in = $attendance->clock_in;
            $request->original_clock_out = $attendance->clock_out;
            $request->original_break_start = $attendance->breaks->first()?->start_time;
            $request->original_break_end = $attendance->breaks->first()?->end_time;
            $request->original_reason = $attendance->reason;

            $attendance->clock_in = $request->clock_in;
            $attendance->clock_out = $request->clock_out;
            $attendance->reason = $request->reason;
            $attendance->save();

            $attendance->breaks()->delete();
            foreach ($request->break_times as $break) {
                $attendance->breaks()->create([
                    'start_time' => $break['start'],
                    'end_time' => $break['end']
                ]);
            }

            $request->status = 'approved';
            $request->approved_at = now();
            $request->approved_by = Auth::id();
            $request->save();

            DB::commit();

            return redirect()->route('stamp_correction_request.show', $request->id)
                ->with('success', '申請を承認しました。');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('勤怠修正申請の承認に失敗しました', [
                'request_id' => $request->id,
                'error' => $e->getMessage()
            ]);

            return back()->with('error', '申請の承認に失敗しました。');
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

    public function history($attendanceId)
    {
        $histories = DB::table('attendance_modification_history')
            ->where('attendance_id', $attendanceId)
            ->join('users', 'attendance_modification_history.modified_by', '=', 'users.id')
            ->join('attendances', 'attendance_modification_history.attendance_id', '=', 'attendances.id')
            ->select(
                'attendance_modification_history.*',
                'users.name as modified_by_name',
                'attendances.clock_in as current_clock_in',
                'attendances.clock_out as current_clock_out',
                'attendances.total_break_time as current_total_break_time',
                'attendances.total_work_time as current_total_work_time'
            )
            ->orderBy('attendance_modification_history.created_at', 'desc')
            ->get();

        return view('stamp_correction_request.history', compact('histories'));
    }

    public function list(Request $request)
    {
        $status = $request->query('status', 'pending');
        $user = Auth::user();
        $isAdmin = $user->isAdmin();

        $query = StampCorrectionRequest::with(['user', 'attendance'])
            ->orderBy('created_at', 'desc');

        if (!$isAdmin) {
            $query->where('user_id', $user->id);
        }

        if (in_array($status, ['pending', 'approved', 'rejected'])) {
            $query->where('status', $status);
        } else {
            $query->where('status', 'pending');
            $status = 'pending';
        }

        $requests = $query->simplePaginate(15);

        $requestsCollection = $requests->getCollection()->map(function ($request) use ($isAdmin) {
            $detailUrl = '#';
            $buttonVariant = 'secondary';
            if ($isAdmin) {
                if ($request->status === 'pending') {
                    $detailUrl = route('admin.stamp_correction_request.show', $request->id);
                    $buttonVariant = 'primary';
                } elseif ($request->status === 'approved') {
                    $detailUrl = route('admin.stamp_correction_request.approved', $request->id);
                }
            } else {
                if ($request->status === 'pending') {
                    $detailUrl = route('stamp_correction_request.pending', $request->id);
                    $buttonVariant = 'primary';
                } elseif ($request->status === 'approved') {
                    $detailUrl = route('stamp_correction_request.approved', $request->id);
                }
            }

            return [
                'id' => $request->id,
                'user_name' => $request->user->name,
                'date' => Carbon::parse($request->attendance->date)->format('Y/m/d'),
                'reason' => $request->reason,
                'status' => $request->status,
                'created_at' => Carbon::parse($request->created_at)->format('Y/m/d'),
                'detail_url' => $detailUrl,
                'clock_in' => Carbon::parse($request->clock_in)->format('H:i'),
                'clock_out' => Carbon::parse($request->clock_out)->format('H:i'),
                'break_times' => collect($request->break_start)->map(function ($start, $index) use ($request) {
                    return ($start ?? '-') . ' - ' . ($request->break_end[$index] ?? '-');
                })->join('<br>'),
                'approved_at' => $request->approved_at ? Carbon::parse($request->approved_at)->format('Y/m/d H:i') : null,
            ];
        });

        $requests->setCollection($requestsCollection);

        return view('stamp_correction_request.list', [
            'requests' => $requests,
            'status' => $status,
            'isAdmin' => $isAdmin
        ]);
    }

    /**
     * @param int $id 修正申請ID
     * @return \Illuminate\View\View
     */
    public function show($id)
    {
        $request = StampCorrectionRequest::findOrFail($id);

        if (!Auth::user()->isAdmin() && $request->user_id !== Auth::id()) {
            abort(403);
        }

        if ($request->status === 'approved') {
            return $this->showApproved($request);
        }

        return $this->showPending($request);
    }

    public function showPending(StampCorrectionRequest $request)
    {
        if ($request->user_id !== Auth::id()) {
            abort(403);
        }

        $data = [
            'request' => $request,
            'attendance' => $request->attendance,
        ];

        return view('stamp_correction_request.pending', $data);
    }

    public function showApproved(StampCorrectionRequest $request)
    {
        if (!Auth::user()->isAdmin() && $request->user_id !== Auth::id()) {
            abort(403);
        }

        if ($request->status !== 'approved') {
             abort(404, '承認済みの申請ではありません。');
        }

        $data = [
            'request' => $request,
        ];

        return view('stamp_correction_request.approved', $data);
    }
}
