@extends('layouts.admin')

@section('content')
<div class="l-container p-admin-attendance-list">
    <h2 class="c-title">日次勤怠管理</h2>

    <div class="c-list-controls">
        <a href="{{ route('admin.attendance.list', ['date' => $date->copy()->subDay()->format('Y-m-d')]) }}" class="c-list-controls__link">← 前日</a>
        <div class="c-list-controls__label-wrapper">
            <img src="{{ asset('img/calendar.png') }}" alt="カレンダー" class="c-icon p-admin-attendance-list__icon">
            <span class="c-list-controls__label">{{ $date->format('Y/m/d') }}</span>
        </div>
        <a href="{{ route('admin.attendance.list', ['date' => $date->copy()->addDay()->format('Y-m-d')]) }}" class="c-list-controls__link">翌日 →</a>
    </div>

    <div class="c-card p-admin-attendance-list__container">
        <table class="c-table">
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
                        // Format time for admin view (similar to user view)
                        $formatTime = function($timeString) {
                            if (empty($timeString) || $timeString === '00:00') {
                                return '0:00';
                            }
                            if (str_starts_with($timeString, '0')) {
                                return substr($timeString, 1);
                            }
                            return $timeString;
                        };
                        $breakTime = $attendance ? $formatTime($attendance->total_break_time ?: '00:00') : '0:00';
                        $workTime = $attendance ? $formatTime($attendance->total_work_time ?: '00:00') : '0:00';
                    @endphp
                    <tr>
                        <td>{{ $user->name }}</td>
                        <td>{{ $attendance?->clock_in?->format('H:i') ?? '-' }}</td>
                        <td>{{ $attendance?->clock_out?->format('H:i') ?? '-' }}</td>
                        <td>{{ $breakTime }}</td>
                        <td>{{ $workTime }}</td>
                        <td>
                            @if($attendance)
                            <x-button as="a" :href="route('attendance.show', ['id' => $attendance->id])" variant="secondary" size="sm" class="p-admin-attendance-list__detail-button">詳細</x-button>
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

@endsection
