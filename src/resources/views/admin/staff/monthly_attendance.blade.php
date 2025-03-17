@extends('layouts.app')

@section('content')
<div class="monthly-attendance">
    <h2 class="monthly-attendance__title">{{ $user->name }}さんの勤怠一覧</h2>
    
    <div class="monthly-attendance__nav">
        <a href="{{ route('admin.staff.monthly_attendance', ['id' => $user->id, 'month' => Carbon\Carbon::parse($currentMonth)->subMonth()->format('Y-m')]) }}" class="monthly-attendance__nav-button">
            前月
        </a>
        <span class="monthly-attendance__current-month">
            {{ Carbon\Carbon::parse($currentMonth)->format('Y年n月') }}
        </span>
        <a href="{{ route('admin.staff.monthly_attendance', ['id' => $user->id, 'month' => Carbon\Carbon::parse($currentMonth)->addMonth()->format('Y-m')]) }}" class="monthly-attendance__nav-button">
            翌月
        </a>
    </div>

    <div class="monthly-attendance__container">
        <table class="monthly-attendance__table">
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
                    <td>{{ $attendance->date->format('Y/m/d') }} ({{ ['日', '月', '火', '水', '木', '金', '土'][$attendance->date->dayOfWeek] }})</td>
                    <td>{{ $attendance->work_status }}</td>
                    <td>{{ $attendance->clock_in ? Carbon\Carbon::parse($attendance->clock_in)->format('H:i') : '--:--' }}</td>
                    <td>{{ $attendance->clock_out ? Carbon\Carbon::parse($attendance->clock_out)->format('H:i') : '--:--' }}</td>
                    <td>{{ $attendance->break_time }}</td>
                    <td>{{ $attendance->work_time }}</td>
                    <td>
                        <a href="{{ route('admin.attendance.show', ['id' => $attendance->id]) }}" class="monthly-attendance__button">
                            詳細
                        </a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection 