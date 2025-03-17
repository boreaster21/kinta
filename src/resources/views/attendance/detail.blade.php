@extends('layouts.app')

@section('content')
<div class="attendance-detail">
    <h2 class="attendance-detail__title">勤怠詳細</h2>

    @if (session('message'))
    <div class="alert alert-success">
        {{ session('message') }}
    </div>
    @endif

    @if (!$isAdmin && $pendingRequest)
    <div class="alert alert-warning">
        ※ 承認待ちのため修正はできません。
    </div>
    @endif

    @if ($isAdmin)
    <form method="POST" action="{{ route('admin.attendance.update', ['id' => $attendance->id]) }}" class="attendance-detail__form">
        @csrf
        @method('PUT')
    @else
    <form method="POST" action="{{ route('attendance.request', ['id' => $attendance->id]) }}" class="attendance-detail__form">
        @csrf
    @endif
        <table class="attendance-detail__table">
            <tr>
                <th>名前</th>
                <td>{{ $attendance->user->name }}</td>
            </tr>
            <tr>
                <th>日付</th>
                <td>{{ $attendance->date->format('Y年m月d日') }}</td>
            </tr>
            <tr>
                <th>出勤・退勤</th>
                <td>
                    <input type="time" name="clock_in" value="{{ $attendance->clock_in?->format('H:i') }}" {{ !$isAdmin && $pendingRequest ? 'disabled' : '' }} required>
                    〜
                    <input type="time" name="clock_out" value="{{ $attendance->clock_out?->format('H:i') }}" {{ !$isAdmin && $pendingRequest ? 'disabled' : '' }} required>
                </td>
            </tr>
            @if ($attendance->breaks->isEmpty())
            <tr>
                <th>休憩</th>
                <td>
                    <div class="break-inputs">
                        <input type="time" name="break_start[]" {{ !$isAdmin && $pendingRequest ? 'disabled' : '' }}>
                        〜
                        <input type="time" name="break_end[]" {{ !$isAdmin && $pendingRequest ? 'disabled' : '' }}>
                    </div>
                </td>
            </tr>
            @else
            @foreach ($attendance->breaks as $index => $break)
            <tr>
                <th>休憩{{ $index + 1 }}</th>
                <td>
                    <div class="break-inputs">
                        <input type="time" name="break_start[]" value="{{ Carbon\Carbon::parse($break->start_time)->format('H:i') }}" {{ !$isAdmin && $pendingRequest ? 'disabled' : '' }}>
                        〜
                        <input type="time" name="break_end[]" value="{{ $break->end_time ? Carbon\Carbon::parse($break->end_time)->format('H:i') : '' }}" {{ !$isAdmin && $pendingRequest ? 'disabled' : '' }}>
                    </div>
                </td>
            </tr>
            @endforeach
            @endif
            <tr>
                <th>備考</th>
                <td>
                    <textarea name="reason" {{ !$isAdmin && $pendingRequest ? 'disabled' : '' }}>{{ old('reason', $attendance->reason) }}</textarea>
                </td>
            </tr>
        </table>

        @if ($isAdmin)
        <button type="submit" class="btn btn-primary">修正を保存</button>
        @elseif (!$pendingRequest)
        <button type="submit" class="btn btn-primary">修正申請</button>
        @endif
    </form>
</div>
@endsection