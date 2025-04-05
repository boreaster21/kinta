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
                    <th>出勤</th>
                    <th>退勤時</th>
                    <th>休憩</th>
                    <th>合計</th>
                    <th>詳細</th>
                </tr>
            </thead>
            <tbody>
                @foreach($attendances as $attendance)
                <tr>
                    <td>{{ $attendance->date->format('Y/m/d') }} ({{ ['日', '月', '火', '水', '木', '金', '土'][$attendance->date->dayOfWeek] }})</td>
                    <td>{{ $attendance->clock_in ? Carbon\Carbon::parse($attendance->clock_in)->format('H:i') : '--:--' }}</td>
                    <td>{{ $attendance->clock_out ? Carbon\Carbon::parse($attendance->clock_out)->format('H:i') : '--:--' }}</td>
                    <td>{{ $attendance->total_break_time ?? '00:00' }}</td>
                    <td>{{ $attendance->total_work_time ?? '00:00' }}</td>
                    <td>
                        <a href="{{ route('attendance.show', ['id' => $attendance->id]) }}" class="monthly-attendance__button">
                            詳細
                        </a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- CSV出力ボタンを追加 --}}
    <div class="monthly-attendance__export">
        <a href="{{ route('admin.staff.monthly_attendance.export', ['id' => $user->id, 'month' => $currentMonth]) }}" class="monthly-attendance__export-button">
            CSV出力
        </a>
    </div>
</div>
@endsection