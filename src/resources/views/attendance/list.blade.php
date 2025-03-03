@extends('layouts.app')

@section('content')
<div class="attendance-list">
    <h2 class="attendance-list__title">勤怠一覧</h2>

    <div class="attendance-list__controls">
        <a href="{{ route('attendance.list', ['month' => $previousMonth]) }}" class="attendance-list__nav">← 前月</a>
        <span class="attendance-list__month">{{ \Carbon\Carbon::parse($currentMonth)->format('Y年m月') }}</span>
        <a href="{{ route('attendance.list', ['month' => $nextMonth]) }}" class="attendance-list__nav">翌月 →</a>
    </div>

    <table class="attendance-list__table">
        <thead>
            <tr>
                <th>日付</th>
                <th>出勤</th>
                <th>退勤</th>
                <th>休憩</th>
                <th>合計</th>
                <th>詳細</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($attendances as $attendance)
            <tr>
                <td>{{ \Carbon\Carbon::parse($attendance->date)->format('m/d (D)') }}</td>
                <td>{{ $attendance->clock_in ? \Carbon\Carbon::parse($attendance->clock_in)->format('H:i') : '-' }}</td>
                <td>{{ $attendance->clock_out ? \Carbon\Carbon::parse($attendance->clock_out)->format('H:i') : '-' }}</td>
                <td>{{ $attendance->total_break_time ?: '00:00' }}</td>
                <td>{{ ltrim($attendance->total_work_time, '-') ?: '00:00' }}</td>
                <td>
                    <a href="{{ route('attendance.show', ['id' => $attendance->id]) }}" class="attendance-list__detail">詳細</a>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection