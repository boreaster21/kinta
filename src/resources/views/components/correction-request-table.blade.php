@props(['request', 'showActions' => false])

<div class="correction-request-info">
    <div class="info-section">
        <h3 class="section-title">申請情報</h3>
        <table class="info-table">
            <tr>
                <th>申請者</th>
                <td>{{ $request->user->name }}</td>
            </tr>
            <tr>
                <th>申請日時</th>
                <td>{{ $request->created_at->format('Y/m/d H:i') }}</td>
            </tr>
            @if($request->approved_at)
            <tr>
                <th>承認日時</th>
                <td>{{ $request->approved_at->format('Y/m/d H:i') }}</td>
            </tr>
            @endif
        </table>
    </div>

    <div class="info-section">
        <h3 class="section-title">修正前の情報</h3>
        <table class="info-table">
            <tr>
                <th>出勤時間</th>
                <td>{{ $request->original_clock_in->format('H:i') }}</td>
            </tr>
            <tr>
                <th>退勤時間</th>
                <td>{{ $request->original_clock_out->format('H:i') }}</td>
            </tr>
            <tr>
                <th>休憩時間</th>
                <td>
                    @foreach($request->original_break_start as $index => $start)
                        {{ $start }} - {{ $request->original_break_end[$index] }}<br>
                    @endforeach
                </td>
            </tr>
            <tr>
                <th>備考</th>
                <td>{{ $request->original_reason }}</td>
            </tr>
        </table>
    </div>

    <div class="info-section">
        <h3 class="section-title">修正後の情報</h3>
        <table class="info-table">
            <tr>
                <th>出勤時間</th>
                <td>{{ $request->clock_in->format('H:i') }}</td>
            </tr>
            <tr>
                <th>退勤時間</th>
                <td>{{ $request->clock_out->format('H:i') }}</td>
            </tr>
            <tr>
                <th>休憩時間</th>
                <td>
                    @foreach($request->break_start as $index => $start)
                        {{ $start }} - {{ $request->break_end[$index] }}<br>
                    @endforeach
                </td>
            </tr>
            <tr>
                <th>備考</th>
                <td>{{ $request->reason }}</td>
            </tr>
        </table>
    </div>

    @if($showActions)
    <div class="request-actions">
        <form action="{{ route('stamp_correction_request.approve', ['attendance_correct_request' => $request->id]) }}" method="POST" class="d-inline">
            @csrf
            <button type="submit" class="btn btn-success">承認</button>
        </form>
        <form action="{{ route('stamp_correction_request.reject', ['attendance_correct_request' => $request->id]) }}" method="POST" class="d-inline">
            @csrf
            <button type="submit" class="btn btn-danger">却下</button>
        </form>
    </div>
    @endif
</div>