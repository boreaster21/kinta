@extends('layouts.app')

@section('content')
<div class="attendance-detail">
    <h2 class="attendance-detail__title">勤怠詳細</h2>

    @if ($pendingRequest)
    <div class="alert alert-warning">
        ※ 承認待ちのため修正はできません。
    </div>
    @endif

    <form method="POST" action="{{ route('attendance.request', $attendance->id) }}">
        @csrf
        <table class="attendance-detail__table">
            <tr>
                <th>名前</th>
                <td>{{ $attendance->user->name }}</td>
            </tr>
            <tr>
                <th>日付</th>
                <td>{{ \Carbon\Carbon::parse($attendance->date)->format('Y年m月d日') }}</td>
            </tr>
            <tr>
                <th>出勤・退勤</th>
                <td>
                    <input type="time" name="clock_in" value="{{ \Carbon\Carbon::parse($attendance->clock_in)->format('H:i') }}" {{ $pendingRequest ? 'disabled' : '' }} required>
                    〜
                    <input type="time" name="clock_out" value="{{ \Carbon\Carbon::parse($attendance->clock_out)->format('H:i') }}" {{ $pendingRequest ? 'disabled' : '' }} required>
                </td>
            </tr>
            @if ($attendance->breaks->isEmpty())
            <tr>
                <th>休憩</th>
                <td>休憩なし</td>
            </tr>
            @else
            @foreach ($attendance->breaks as $index => $break)
            <tr>
                <th>休憩{{ $index + 1 }}</th>
                <td>
                    {{ \Carbon\Carbon::parse($break->start_time)->format('H:i') }}
                    〜
                    {{ $break->end_time ? \Carbon\Carbon::parse($break->end_time)->format('H:i') : '進行中' }}
                </td>
            </tr>
            @endforeach
            @endif
            <tr>
                <th>備考</th>
                <td>
                    <textarea name="reason" {{ $pendingRequest ? 'disabled' : '' }} required>{{ old('reason') }}</textarea>
                </td>
            </tr>
        </table>

        @if (!$pendingRequest)
        <button type="submit" class="btn btn-primary">修正申請</button>
        @endif
    </form>
</div>
@endsection