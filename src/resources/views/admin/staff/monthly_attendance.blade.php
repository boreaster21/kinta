@extends('layouts.admin')

@section('content')
<div class="l-container p-admin-monthly-attendance">
    <h2 class="c-title">{{ $user->name }}さんの勤怠</h2>

    <div class="c-list-controls">
        <a href="{{ route('admin.staff.monthly_attendance', ['id' => $user->id, 'month' => $previousMonth]) }}" class="c-list-controls__link">← 前月</a>
        <div class="c-list-controls__label-wrapper">
            <img src="{{ asset('img/calendar.png') }}" alt="カレンダー" class="c-icon p-admin-monthly-attendance__icon">
            <span class="c-list-controls__label">{{ Carbon\Carbon::parse($month)->format('Y年m月') }}</span>
        </div>
        <a href="{{ route('admin.staff.monthly_attendance', ['id' => $user->id, 'month' => $nextMonth]) }}" class="c-list-controls__link">翌月 →</a>
    </div>

    <div class="c-card p-admin-monthly-attendance__container">
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
                    <td>{{ $attendance['date'] ?? '-' }}</td>
                    <td>{{ $attendance['clock_in'] ?? '-' }}</td>
                    <td>{{ $attendance['clock_out'] ?? '-' }}</td>
                    <td>{{ $attendance['total_break_time'] ?? $attendance['break_time'] ?? '0:00' }}</td>
                    <td>{{ $attendance['total_work_time'] ?? $attendance['total_time'] ?? '0:00' }}</td>
                    <td>
                        @if($attendance['id'] ?? null)
                        <x-button as="a" :href="route('attendance.show', ['id' => $attendance['id']])" variant="secondary" size="sm">詳細</x-button>
                        @else
                        -
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="p-admin-monthly-attendance__export">
        <x-button as="a" :href="route('admin.staff.monthly_attendance.export', ['id' => $user->id, 'month' => $month])" variant="secondary" class="p-admin-monthly-attendance__export-button">CSV出力</x-button>
    </div>
</div>
@endsection