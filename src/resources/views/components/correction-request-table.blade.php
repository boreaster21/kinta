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

<style>
.correction-request-info {
    padding: 20px;
}

.info-section {
    margin-bottom: 25px;
}

.section-title {
    font-size: 1.2rem;
    margin-bottom: 15px;
    border-bottom: 2px solid #f0f0f0;
    padding-bottom: 8px;
}

.info-table {
    width: 100%;
    border-collapse: collapse;
}

.info-table th,
.info-table td {
    padding: 10px;
    border-bottom: 1px solid #eee;
}

.info-table th {
    width: 30%;
    text-align: left;
    font-weight: 600;
    color: #555;
}

.request-actions {
    margin-top: 20px;
    text-align: right;
}

.btn {
    display: inline-block;
    padding: 8px 16px;
    border-radius: 4px;
    text-decoration: none;
    font-weight: 500;
    cursor: pointer;
    transition: background-color 0.2s;
    border: none;
    color: white;
}

.btn-success {
    background-color: #28a745;
}

.btn-danger {
    background-color: #dc3545;
}

.btn-success:hover {
    background-color: #218838;
}

.btn-danger:hover {
    background-color: #c82333;
}
</style> 