@extends('layouts.app')

@section('content')
<div class="attendance-list">
    <h2 class="page-title">勤怠一覧</h2>

    <div class="attendance-list__controls">
        <a href="{{ route('attendance.list', ['month' => $previousMonth]) }}" class="attendance-list__nav">← 前月</a>
        <div class="attendance-list__month-wrapper">
            <img src="{{ asset('img/calendar.png') }}" alt="カレンダー" class="calendar-icon">
            <span class="attendance-list__month">{{ \Carbon\Carbon::parse($month)->format('Y/m') }}</span>
        </div>
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
                <td>{{ $attendance['date'] }}</td>
                <td>{{ $attendance['clock_in'] }}</td>
                <td>{{ $attendance['clock_out'] }}</td>
                <td>{{ $attendance['break_time'] }}</td>
                <td>{{ $attendance['total_time'] }}</td>
                <td>
                    <a href="{{ route('attendance.show', ['id' => $attendance['id']]) }}" class="attendance-list__detail">詳細</a>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection