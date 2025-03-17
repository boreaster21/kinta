@extends('layouts.app')

@section('content')
<div class="attendance-list">
    <h2 class="attendance-list__title">{{ Carbon\Carbon::parse($date)->format('Y年n月j日') }}の勤怠</h2>

    <div class="attendance-list__date-nav">
        <a href="{{ request()->fullUrlWithQuery(['date' => Carbon\Carbon::parse($date)->subDay()->format('Y-m-d')]) }}" class="attendance-list__nav-button">← 前日</a>
        <span class="attendance-list__current-date">{{ Carbon\Carbon::parse($date)->format('Y/m/d') }}</span>
        <a href="{{ request()->fullUrlWithQuery(['date' => Carbon\Carbon::parse($date)->addDay()->format('Y-m-d')]) }}" class="attendance-list__nav-button">翌日 →</a>
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
                @foreach($attendances as $attendance)
                <tr>
                    <td>{{ $attendance->user->name }}</td>
                    <td>{{ $attendance->clock_in ? Carbon\Carbon::parse($attendance->clock_in)->format('H:i') : '-' }}</td>
                    <td>{{ $attendance->clock_out ? Carbon\Carbon::parse($attendance->clock_out)->format('H:i') : '-' }}</td>
                    <td>{{ $attendance->total_break_time ? $attendance->total_break_time . ':00' : '-' }}</td>
                    <td>{{ $attendance->total_work_time ? $attendance->total_work_time . ':00' : '-' }}</td>
                    <td>
                        <a href="{{ route('admin.attendance.show', ['id' => $attendance->id]) }}" class="attendance-list__button">詳細</a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection