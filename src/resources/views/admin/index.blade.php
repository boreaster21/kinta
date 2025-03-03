@extends('layouts.app')

@section('content')
<div class="attendance">
    <h2 class="attendance__title">{{ \Carbon\Carbon::parse($date)->format('Y年m月d日') }}の勤怠</h2>

    <div class="attendance__controls">
        <a href="{{ route('attendance.index', ['date' => \Carbon\Carbon::parse($date)->subDay()->format('Y-m-d')]) }}" class="attendance__prev">← 前日</a>
        <span class="attendance__date">{{ $date }}</span>
    </div>

    <table class="attendance__table">
        <thead>
            <tr>
                <th>名前</th>
                <th>出勤</th>
                <th>退勤</th>
                <th>休憩</th>
                <th>合計</th>
                <th>詳細</th>
            </tr>
        </thead>
        <tbody>
            @foreach($attendances as $attendance)
            <tr>
                <td>{{ $attendance->user->name }}</td>
                <td>{{ $attendance->clock_in }}</td>
                <td>{{ $attendance->clock_out }}</td>
                <td>{{ $attendance->break_time }}</td>
                <td>{{ $attendance->total_hours }}</td>
                <td><a href="#">詳細</a></td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection