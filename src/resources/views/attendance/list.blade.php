@extends('layouts.app')

@section('content')
<div class="l-container p-attendance-list">
    <h2 class="c-title">勤怠一覧</h2>

    <div class="c-list-controls">
        <a href="{{ route('attendance.list', ['month' => $previousMonth]) }}" class="c-list-controls__link">← 前月</a>
        <div class="c-list-controls__label-wrapper">
            <img src="{{ asset('img/calendar.png') }}" alt="カレンダー" class="c-icon p-attendance-list__icon">
            <span class="c-list-controls__label">{{ \Carbon\Carbon::parse($month)->format('Y/m') }}</span>
        </div>
        <a href="{{ route('attendance.list', ['month' => $nextMonth]) }}" class="c-list-controls__link">翌月 →</a>
    </div>

    <table class="c-table">
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
                    <x-button as="a" :href="route('attendance.show', ['id' => $attendance['id']])" variant="secondary" size="sm" class="p-attendance-list__detail-button">詳細</x-button>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection

