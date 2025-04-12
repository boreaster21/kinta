@extends('layouts.app')

@section('content')
<div class="l-container l-container--narrow p-attendance-detail">
    <h2 class="c-title">勤怠詳細</h2>

    <x-alert type="success" :message="session('message')" />

    @if ($errors->any())
    <x-alert type="danger">
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </x-alert>
    @endif

    @if ($isAdmin)
    <form method="POST" action="{{ route('admin.attendance.update', ['id' => $attendance->id]) }}" class="p-attendance-detail__form">
        @csrf
        @method('PUT')
    @else
    <form method="POST" action="{{ route('attendance.request', ['id' => $attendance->id]) }}" class="p-attendance-detail__form">
        @csrf
    @endif
        <table class="c-table c-table--detail p-attendance-detail__table">
            <tr>
                <th>名前</th>
                <td>{{ $attendance->user->name }}</td>
            </tr>
            <tr>
                <th>日付</th>
                <td>
                    <x-forms.input type="date" name="date" :value="$attendance->date->format('Y-m-d')" :disabled="!$isAdmin && $pendingRequest" required />
                    <x-forms.error field="date" />
                </td>
            </tr>
            <tr>
                <th>出勤・退勤</th>
                <td>
                    <x-forms.input type="time" name="clock_in" :value="$displayData['clock_in']?->format('H:i')" :disabled="!$isAdmin && $pendingRequest" required />
                    <x-forms.error field="clock_in" />
                    〜
                    <x-forms.input type="time" name="clock_out" :value="$displayData['clock_out']?->format('H:i')" :disabled="!$isAdmin && $pendingRequest" required />
                    <x-forms.error field="clock_out" />
                </td>
            </tr>
            @if ($displayData['breaks']->isEmpty())
            <tr>
                <th>休憩</th>
                <td>
                    <div class="p-attendance-detail__break-inputs">
                        @if($isAdmin)
                        <x-forms.input type="time" name="breaks[0][start_time]" id="breaks_0_start_time" :disabled="!$isAdmin && $pendingRequest" />
                        <x-forms.error field="breaks.0.start_time" />
                        〜
                        <x-forms.input type="time" name="breaks[0][end_time]" id="breaks_0_end_time" :disabled="!$isAdmin && $pendingRequest" />
                        <x-forms.error field="breaks.0.end_time" />
                        @else
                        <x-forms.input type="time" name="break_start[]" id="break_start_0" :disabled="!$isAdmin && $pendingRequest" />
                        <x-forms.error field="break_start.0" />
                        〜
                        <x-forms.input type="time" name="break_end[]" id="break_end_0" :disabled="!$isAdmin && $pendingRequest" />
                        <x-forms.error field="break_end.0" />
                        @endif
                    </div>
                </td>
            </tr>
            @else
                @if($isAdmin)
                    @foreach ($displayData['breaks'] as $index => $break)
                    <tr>
                        <th>休憩{{ $loop->index + 1 }}</th>
                        <td>
                            <div class="p-attendance-detail__break-inputs">
                                <x-forms.input type="time" name="breaks[{{ $index }}][start_time]" id="breaks_{{ $index }}_start_time" :value="$break->start_time ? \Carbon\Carbon::parse($break->start_time)->format('H:i') : null" :disabled="!$isAdmin && $pendingRequest" />
                                〜
                                <x-forms.input type="time" name="breaks[{{ $index }}][end_time]" id="breaks_{{ $index }}_end_time" :value="$break->end_time ? \Carbon\Carbon::parse($break->end_time)->format('H:i') : null" :disabled="!$isAdmin && $pendingRequest" />
                            </div>
                        </td>
                    </tr>
                    @endforeach
                @else
                    @foreach ($displayData['breaks'] as $index => $break)
                    <tr>
                        <th>休憩{{ $index + 1 }}</th>
                        <td>
                            <div class="p-attendance-detail__break-inputs">
                                <x-forms.input type="time" name="break_start[]" id="break_start_{{ $index }}" :value="$break->start_time ? \Carbon\Carbon::parse($break->start_time)->format('H:i') : null" :disabled="!$isAdmin && $pendingRequest" />
                                <x-forms.error field="break_start.{{ $index }}" />
                                〜
                                <x-forms.input type="time" name="break_end[]" id="break_end_{{ $index }}" :value="$break->end_time ? \Carbon\Carbon::parse($break->end_time)->format('H:i') : null" :disabled="!$isAdmin && $pendingRequest" />
                                <x-forms.error field="break_end.{{ $index }}" />
                            </div>
                        </td>
                    </tr>
                    @endforeach
                @endif
            @endif
            <tr>
                <th>備考</th>
                <td>
                    <x-forms.textarea name="reason" :value="$displayData['reason']" :disabled="!$isAdmin && $pendingRequest" />
                    <x-forms.error field="reason" />
                </td>
            </tr>
        </table>

        <div class="p-attendance-detail__button-container">
            @if ($isAdmin || !$pendingRequest)
                <x-button type="submit" variant="primary">修正</x-button>
            @endif
        </div>
    </form>

    @if (!$isAdmin && $pendingRequest)
    <x-alert type="warning">
        承認待ちのため修正はできません。
    </x-alert>
    @endif

    @if($correctionHistory->isNotEmpty())
    <div class="p-attendance-detail__history">
        <h3 class="p-attendance-detail__history-title">修正履歴</h3>
        <table class="c-table p-attendance-detail__history-table">
            <thead>
                <tr>
                    <th>申請日時</th>
                    <th>出勤時間</th>
                    <th>退勤時間</th>
                    <th>休憩時間</th>
                    <th>備考</th>
                    <th>承認日時</th>
                    <th>承認者</th>
                </tr>
            </thead>
            <tbody>
                @foreach($correctionHistory as $history)
                <tr>
                    <td>{{ $history['created_at']->format('Y/m/d H:i') }}</td>
                    <td>{{ Carbon\Carbon::parse($history['clock_in'])->format('H:i') }}</td>
                    <td>{{ Carbon\Carbon::parse($history['clock_out'])->format('H:i') }}</td>
                    <td>
                        @foreach($history['break_start'] as $index => $start)
                            {{ $start }} 〜 {{ $history['break_end'][$index] }}<br>
                        @endforeach
                    </td>
                    <td>{{ $history['reason'] }}</td>
                    <td>{{ $history['approved_at']->format('Y/m/d H:i') }}</td>
                    <td>{{ $history['approved_by'] }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
</div>

@endsection