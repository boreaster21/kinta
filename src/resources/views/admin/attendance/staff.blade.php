@extends('layouts.app')

@section('content')
<div class="staff-attendance">
    <h2 class="staff-attendance__title">{{ $user->name }}さんの勤怠一覧</h2>
    
    <div class="staff-attendance__nav">
        <a href="{{ route('admin.attendance.staff', ['id' => $user->id, 'month' => Carbon\Carbon::parse($month)->subMonth()->format('Y-m')]) }}" class="staff-attendance__nav-button">
            前月
        </a>
        <span class="staff-attendance__current-month">
            {{ Carbon\Carbon::parse($month)->format('Y年n月') }}
        </span>
        <a href="{{ route('admin.attendance.staff', ['id' => $user->id, 'month' => Carbon\Carbon::parse($month)->addMonth()->format('Y-m')]) }}" class="staff-attendance__nav-button">
            翌月
        </a>
    </div>

    <div class="staff-attendance__container">
        <table class="staff-attendance__table">
            <thead>
                <tr>
                    <th>日付</th>
                    <th>勤務状態</th>
                    <th>出勤時刻</th>
                    <th>退勤時刻</th>
                    <th>休憩時間</th>
                    <th>勤務時間</th>
                    <th>操作</th>
                </tr>
            </thead>
            <tbody>
                @foreach($attendances as $attendance)
                <tr>
                    <td>{{ $attendance->date->format('Y/m/d (D)') }}</td>
                    <td>{{ $attendance->work_status }}</td>
                    <td>{{ $attendance->clock_in?->format('H:i') }}</td>
                    <td>{{ $attendance->clock_out?->format('H:i') }}</td>
                    <td>{{ $attendance->break_time }}</td>
                    <td>{{ $attendance->work_time }}</td>
                    <td>
                        <a href="{{ route('admin.attendance.show', ['id' => $attendance->id]) }}" class="staff-attendance__button">
                            詳細
                        </a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<style>
.staff-attendance {
    padding: 20px;
    max-width: 1200px;
    margin: 0 auto;
}

.staff-attendance__title {
    font-size: 24px;
    margin-bottom: 30px;
    text-align: center;
}

.staff-attendance__nav {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 20px;
    margin-bottom: 30px;
}

.staff-attendance__nav-button {
    padding: 8px 16px;
    background-color: #4A90E2;
    color: white;
    text-decoration: none;
    border-radius: 4px;
}

.staff-attendance__nav-button:hover {
    background-color: #357ABD;
}

.staff-attendance__current-month {
    font-size: 18px;
    font-weight: bold;
}

.staff-attendance__container {
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    overflow-x: auto;
}

.staff-attendance__table {
    width: 100%;
    border-collapse: collapse;
    min-width: 800px;
}

.staff-attendance__table th,
.staff-attendance__table td {
    padding: 15px;
    text-align: center;
    border-bottom: 1px solid #ddd;
}

.staff-attendance__table th {
    background-color: #f8f9fa;
    font-weight: bold;
}

.staff-attendance__button {
    display: inline-block;
    padding: 5px 15px;
    background-color: #4CAF50;
    color: white;
    text-decoration: none;
    border-radius: 4px;
}

.staff-attendance__button:hover {
    background-color: #388E3C;
}
</style>
@endsection 