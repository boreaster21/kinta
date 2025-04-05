@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">修正履歴</div>

                <div class="card-body">
                    @if($histories->isEmpty())
                        <p>修正履歴はありません。</p>
                    @else
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>日付</th>
                                        <th>修正者</th>
                                        <th>修正内容</th>
                                        <th>理由</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($histories as $history)
                                        <tr>
                                            <td>{{ $history->created_at->format('Y/m/d H:i') }}</td>
                                            <td>{{ $history->modifiedBy->name }}</td>
                                            <td>
                                                @if($history->clock_in !== $history->attendance->clock_in)
                                                    <div>出勤: {{ $history->clock_in->format('H:i') }} → {{ $history->attendance->clock_in->format('H:i') }}</div>
                                                @endif
                                                @if($history->clock_out !== $history->attendance->clock_out)
                                                    <div>退勤: {{ $history->clock_out->format('H:i') }} → {{ $history->attendance->clock_out->format('H:i') }}</div>
                                                @endif
                                                @if($history->total_break_time !== $history->attendance->total_break_time)
                                                    <div>休憩時間: {{ $history->total_break_time }} → {{ $history->attendance->total_break_time }}</div>
                                                @endif
                                                @if($history->total_work_time !== $history->attendance->total_work_time)
                                                    <div>勤務時間: {{ $history->total_work_time }} → {{ $history->attendance->total_work_time }}</div>
                                                @endif
                                            </td>
                                            <td>{{ $history->reason }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection 