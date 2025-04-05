@extends('layouts.app')

@section('content')
<div class="attendance-detail">
    <h2 class="page-title">勤怠詳細</h2>

    @if (session('message'))
    <div class="alert alert-success">
        {{ session('message') }}
    </div>
    @endif

    @if ($errors->any())
    <div class="alert alert-danger">
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    @if (!$isAdmin && $pendingRequest)
    <div class="alert alert-warning">
        承認待ちのため修正はできません。
    </div>
    @endif

    @if ($isAdmin)
    <form method="POST" action="{{ route('admin.attendance.update', ['id' => $attendance->id]) }}" class="attendance-detail__form">
        @csrf
        @method('PUT')
    @else
    <form method="POST" action="{{ route('attendance.request', ['id' => $attendance->id]) }}" class="attendance-detail__form">
        @csrf
    @endif
        <table class="attendance-detail__table">
            <tr>
                <th>名前</th>
                <td>{{ $attendance->user->name }}</td>
            </tr>
            <tr>
                <th>日付</th>
                <td>{{ $attendance->date->format('Y年m月d日') }}</td>
            </tr>
            <tr>
                <th>出勤・退勤</th>
                <td>
                    <input type="time" name="clock_in" value="{{ old('clock_in', $displayData['clock_in']?->format('H:i')) }}" {{ !$isAdmin && $pendingRequest ? 'disabled' : '' }} required>
                    〜
                    <input type="time" name="clock_out" value="{{ old('clock_out', $displayData['clock_out']?->format('H:i')) }}" {{ !$isAdmin && $pendingRequest ? 'disabled' : '' }} required>
                </td>
            </tr>
            @if ($displayData['breaks']->isEmpty())
            <tr>
                <th>休憩</th>
                <td>
                    <div class="break-inputs">
                        <input type="time" @if($isAdmin) name="breaks[0][start_time]" @endif value="{{ old('breaks.0.start_time') }}" {{ !$isAdmin && $pendingRequest ? 'disabled' : '' }}>
                        〜
                        <input type="time" @if($isAdmin) name="breaks[0][end_time]" @endif value="{{ old('breaks.0.end_time') }}" {{ !$isAdmin && $pendingRequest ? 'disabled' : '' }}>
                    </div>
                </td>
            </tr>
            @else
            @if($isAdmin)
                @foreach ($displayData['breaks'] as $break)
                <tr>
                    <th>休憩{{ $loop->index + 1 }}</th>
                    <td>
                        <div class="break-inputs">
                            <input type="time" name="breaks[{{ $loop->index }}][start_time]" value="{{ old('breaks.' . $loop->index . '.start_time', $break->start_time ? \Carbon\Carbon::parse($break->start_time)->format('H:i') : null) }}" {{ !$isAdmin && $pendingRequest ? 'disabled' : '' }}>
                            〜
                            <input type="time" name="breaks[{{ $loop->index }}][end_time]" value="{{ old('breaks.' . $loop->index . '.end_time', $break->end_time ? \Carbon\Carbon::parse($break->end_time)->format('H:i') : null) }}" {{ !$isAdmin && $pendingRequest ? 'disabled' : '' }}>
                        </div>
                    </td>
                </tr>
                @endforeach
            @else
                @foreach ($displayData['breaks'] as $index => $break)
                <tr>
                    <th>休憩{{ $index + 1 }}</th>
                    <td>
                        <div class="break-inputs">
                            <input type="time" name="break_start[]" value="{{ old('break_start.' . $index, $break->start_time ? \Carbon\Carbon::parse($break->start_time)->format('H:i') : null) }}" {{ !$isAdmin && $pendingRequest ? 'disabled' : '' }}>
                            〜
                            <input type="time" name="break_end[]" value="{{ old('break_end.' . $index, $break->end_time ? \Carbon\Carbon::parse($break->end_time)->format('H:i') : null) }}" {{ !$isAdmin && $pendingRequest ? 'disabled' : '' }}>
                        </div>
                    </td>
                </tr>
                @endforeach
            @endif
            @endif
            <tr>
                <th>備考</th>
                <td>
                    <textarea name="reason" {{ !$isAdmin && $pendingRequest ? 'disabled' : '' }} >{{ old('reason', $displayData['reason']) }}</textarea>
                </td>
            </tr>
        </table>

        <div class="attendance-detail__button-container">
            @if ($isAdmin)
            <button type="submit" class="btn btn-primary">修正</button>
            @elseif (!$pendingRequest)
            <button type="submit" class="btn btn-primary">修正申請</button>
            @endif
        </div>
    </form>

    @if($correctionHistory->isNotEmpty())
    <div class="correction-history">
        <h3>修正履歴</h3>
        <table class="correction-history__table">
            <thead>
                <tr>
                    <th>申請日時</th>
                    <th>出勤時間</th>
                    <th>退勤時間</th>
                    <th>休憩時間</th>
                    <th>備考</th>
                    <th>承認日時</th>
                    <th>承認者</th>
                </tr>
            </thead>
            <tbody>
                @foreach($correctionHistory as $history)
                <tr>
                    <td>{{ $history['created_at']->format('Y/m/d H:i') }}</td>
                    <td>{{ Carbon\Carbon::parse($history['clock_in'])->format('H:i') }}</td>
                    <td>{{ Carbon\Carbon::parse($history['clock_out'])->format('H:i') }}</td>
                    <td>
                        @foreach($history['break_start'] as $index => $start)
                            {{ $start }} 〜 {{ $history['break_end'][$index] }}<br>
                        @endforeach
                    </td>
                    <td>{{ $history['reason'] }}</td>
                    <td>{{ $history['approved_at']->format('Y/m/d H:i') }}</td>
                    <td>{{ $history['approved_by'] }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
</div>

<style>
.alert {
    padding: 15px;
    margin-bottom: 20px;
    border: 1px solid transparent;
    border-radius: 4px;
}

.alert-success {
    color: #155724;
    background-color: #d4edda;
    border-color: #c3e6cb;
}

.alert-danger {
    color: #721c24;
    background-color: #f8d7da;
    border-color: #f5c6cb;
}

.alert-warning {
    color: #856404;
    background-color: #fff3cd;
    border-color: #ffeeba;
    text-align: center;
    font-weight: bold;
}

.correction-history {
    margin-top: 30px;
    padding: 20px;
    background-color: #f8f9fa;
    border-radius: 4px;
}

.correction-history h3 {
    margin-bottom: 15px;
    color: #495057;
}

.correction-history__table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 10px;
}

.correction-history__table th,
.correction-history__table td {
    padding: 8px;
    border: 1px solid #dee2e6;
    text-align: left;
}

.correction-history__table th {
    background-color: #e9ecef;
    font-weight: bold;
}

.correction-history__table tr:nth-child(even) {
    background-color: #f8f9fa;
}

.correction-history__table tr:hover {
    background-color: #e9ecef;
}
</style>

@endsection