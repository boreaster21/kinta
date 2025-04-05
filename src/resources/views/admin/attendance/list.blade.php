@extends('layouts.app')

@section('content')
<div class="attendance-list">
    <h2 class="attendance-list__title">{{ $date->format('Y年m月d日') }}の勤怠</h2>

    <div class="attendance-list__date-nav">
        <a href="{{ request()->fullUrlWithQuery(['date' => $date->copy()->subDay()->format('Y-m-d')]) }}" class="attendance-list__nav-button">← 前日</a>
        <span class="attendance-list__current-date">{{ $date->format('Y/m/d') }}</span>
        <a href="{{ request()->fullUrlWithQuery(['date' => $date->copy()->addDay()->format('Y-m-d')]) }}" class="attendance-list__nav-button">翌日 →</a>
    </div>

    <div class="attendance-list__container">
        <table class="attendance-list__table">
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
                @foreach($users as $user)
                    @php
                        $attendance = $attendances->where('user_id', $user->id)->first();
                    @endphp
                    <tr>
                        <td>{{ $user->name }}</td>
                        <td>{{ $attendance ? $attendance->clock_in?->format('H:i') : '-' }}</td>
                        <td>{{ $attendance ? $attendance->clock_out?->format('H:i') : '-' }}</td>
                        <td>{{ $attendance ? ($attendance->total_break_time ?: '00:00') : '00:00' }}</td>
                        <td>{{ $attendance ? ($attendance->total_work_time ?: '00:00') : '00:00' }}</td>
                        <td>
                            @if($attendance)
                            <a href="{{ route('attendance.show', ['id' => $attendance->id]) }}" class="attendance-list__detail">詳細</a>
                            @else
                            -
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<style>
.attendance-list {
    padding: 30px;
    max-width: 1200px;
    margin: 0 auto;
}

.attendance-list__title {
    font-size: 24px;
    margin-bottom: 30px;
    text-align: center;
}

.attendance-list__date-nav {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 20px;
    margin-bottom: 30px;
}

.attendance-list__nav-button {
    padding: 8px 16px;
    border: 1px solid #ddd;
    border-radius: 4px;
    text-decoration: none;
    color: #666;
    transition: all 0.3s ease;
}

.attendance-list__nav-button:hover {
    background-color: #f5f5f5;
    color: #333;
}

.attendance-list__current-date {
    font-size: 18px;
    font-weight: bold;
}

.attendance-list__container {
    background: #fff;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.attendance-list__table {
    width: 100%;
    border-collapse: collapse;
}

.attendance-list__table th,
.attendance-list__table td {
    padding: 15px;
    text-align: center;
    border-bottom: 1px solid #eee;
}

.attendance-list__table th {
    background: #f8f9fa;
    font-weight: bold;
    color: #333;
}

.attendance-list__detail {
    display: inline-block;
    padding: 4px 12px;
    background-color: #4A90E2;
    color: white;
    text-decoration: none;
    border-radius: 4px;
    font-size: 14px;
    transition: background-color 0.3s ease;
}

.attendance-list__detail:hover {
    background-color: #357ABD;
}
</style>

@endsection