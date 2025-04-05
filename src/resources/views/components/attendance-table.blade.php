@props(['attendance', 'isAdmin' => false, 'pendingRequest' => null])

<table class="attendance-table">
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
            <input type="time" name="clock_in" value="{{ old('clock_in', $attendance->display_clock_in?->format('H:i')) }}" {{ !$isAdmin && $pendingRequest ? 'disabled' : '' }} required>
            〜
            <input type="time" name="clock_out" value="{{ old('clock_out', $attendance->display_clock_out?->format('H:i')) }}" {{ !$isAdmin && $pendingRequest ? 'disabled' : '' }} required>
        </td>
    </tr>
    @if ($attendance->display_breaks->isEmpty())
    <tr>
        <th>休憩</th>
        <td>
            <div class="break-inputs">
                <input type="time" name="break_start[]" value="{{ old('break_start.0') }}" {{ !$isAdmin && $pendingRequest ? 'disabled' : '' }}>
                〜
                <input type="time" name="break_end[]" value="{{ old('break_end.0') }}" {{ !$isAdmin && $pendingRequest ? 'disabled' : '' }}>
            </div>
        </td>
    </tr>
    @else
    @foreach ($attendance->display_breaks as $index => $break)
    <tr>
        <th>休憩{{ $index + 1 }}</th>
        <td>
            <div class="break-inputs">
                <input type="time" name="break_start[]" value="{{ old("break_start.$index", $break->start_time?->format('H:i')) }}" {{ !$isAdmin && $pendingRequest ? 'disabled' : '' }}>
                〜
                <input type="time" name="break_end[]" value="{{ old("break_end.$index", $break->end_time?->format('H:i')) }}" {{ !$isAdmin && $pendingRequest ? 'disabled' : '' }}>
            </div>
        </td>
    </tr>
    @endforeach
    @endif
    <tr>
        <th>備考</th>
        <td>
            <textarea name="reason" {{ !$isAdmin && $pendingRequest ? 'disabled' : '' }} >{{ old('reason', $attendance->display_reason) }}</textarea>
        </td>
    </tr>
</table> 