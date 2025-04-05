@extends('layouts.app')

@section('content')
<div class="attendance-detail stamp-correction-request-show">
    <h2 class="page-title">承認待ち修正申請詳細</h2>

    <div class="approval-info">
        <div class="approval-info__item">
            <span class="approval-info__label">申請日時:</span>
            <span class="approval-info__value">{{ $request->created_at->format('Y年m月d日 H:i') }}</span>
        </div>
    </div>

    <div class="comparison-container">
        <div class="comparison">
            <div class="comparison__section">
                <h3 class="comparison__title">修正前の情報</h3>
                <table class="attendance-detail__table">
                    <tr>
                        <th>名前</th>
                        <td>{{ $request->user->name }}</td>
                    </tr>
                    <tr>
                        <th>日付</th>
                        <td>{{ \Carbon\Carbon::parse($attendance->date)->format('Y年m月d日') }}</td>
                    </tr>
                    <tr>
                        <th>出勤時間</th>
                        <td>{{ $request->original_clock_in ? \Carbon\Carbon::parse($request->original_clock_in)->format('H:i') : '-' }}</td>
                    </tr>
                    <tr>
                        <th>退勤時間</th>
                        <td>{{ $request->original_clock_out ? \Carbon\Carbon::parse($request->original_clock_out)->format('H:i') : '-' }}</td>
                    </tr>
                    @if (!empty($request->original_break_start) && is_array($request->original_break_start))
                        @foreach($request->original_break_start as $index => $start)
                            @if(isset($request->original_break_end[$index]))
                            <tr>
                                <th>休憩{{ $index + 1 }}</th>
                                <td>
                                    {{ $start }} 〜 {{ $request->original_break_end[$index] }}
                                </td>
                            </tr>
                            @endif
                        @endforeach
                    @else
                        <tr>
                            <th>休憩</th>
                            <td>休憩なし</td>
                        </tr>
                    @endif
                    <tr>
                        <th>備考</th>
                        <td>{{ $request->original_reason ?? '特になし' }}</td>
                    </tr>
                </table>
            </div>

            <div class="comparison__section">
                <h3 class="comparison__title">修正後の情報</h3>
                <table class="attendance-detail__table">
                    <tr>
                        <th>名前</th>
                        <td>{{ $request->user->name }}</td>
                    </tr>
                    <tr>
                        <th>日付</th>
                        <td>{{ \Carbon\Carbon::parse($request->attendance->date)->format('Y年m月d日') }}</td>
                    </tr>
                    <tr>
                        <th>出勤時間</th>
                        <td>{{ \Carbon\Carbon::parse($request->clock_in)->format('H:i') }}</td>
                    </tr>
                    <tr>
                        <th>退勤時間</th>
                        <td>{{ \Carbon\Carbon::parse($request->clock_out)->format('H:i') }}</td>
                    </tr>
                    @if (!empty($request->break_start) && is_array($request->break_start))
                        @foreach($request->break_start as $index => $start)
                            @if(isset($request->break_end[$index]))
                            <tr>
                                <th>休憩{{ $index + 1 }}</th>
                                <td>
                                    {{ \Carbon\Carbon::parse($start)->format('H:i') }} 〜
                                    {{ \Carbon\Carbon::parse($request->break_end[$index])->format('H:i') }}
                                </td>
                            </tr>
                            @endif
                        @endforeach
                    @else
                        <tr>
                            <th>休憩</th>
                            <td>休憩なし</td>
                        </tr>
                    @endif
                    <tr>
                        <th>備考</th>
                        <td>{{ $request->reason ?? '特になし' }}</td>
                    </tr>
                </table>
            </div>
        </div>
    </div>

    <div class="approval-actions">
        <a href="{{ route('stamp_correction_request.list', ['status' => 'pending']) }}" class="approval-button approval-button--back">一覧に戻る</a>
    </div>
</div>

<style>
.attendance-detail.stamp-correction-request-show { padding: 20px; max-width: 1000px; margin: 20px auto; background-color: #f9f9f9; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
.page-title { text-align: center; margin-bottom: 20px; color: #333; }
.approval-info { background-color: #e9ecef; padding: 15px; border-radius: 8px; margin-bottom: 20px; display: flex; flex-wrap: wrap; gap: 15px; justify-content: center; }
.approval-info__item { margin-right: 15px; font-size: 0.9em; color: #555; }
.approval-info__label { font-weight: bold; margin-right: 5px; }
.comparison-container { margin-bottom: 30px; }
.comparison { display: flex; gap: 20px; }
.comparison__section { flex: 1; padding: 20px; background-color: #fff; border-radius: 8px; box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05); }
.comparison__title { font-size: 1.1em; font-weight: bold; margin-bottom: 15px; text-align: center; padding-bottom: 10px; border-bottom: 1px solid #eee; color: #444; }
.attendance-detail__table { width: 100%; border-collapse: collapse; margin-top: 15px; font-size: 0.9em; }
.attendance-detail__table th, .attendance-detail__table td { border: 1px solid #dee2e6; padding: 10px; text-align: left; }
.attendance-detail__table th { background-color: #f8f9fa; font-weight: bold; width: 120px; }
.approval-actions { display: flex; justify-content: center; gap: 20px; margin-top: 30px; }
.approval-button { padding: 10px 25px; border-radius: 4px; cursor: pointer; text-decoration: none; text-align: center; font-weight: bold; display: inline-block; border: none; transition: background-color 0.3s ease; font-size: 0.9em;}
.approval-button--back { background-color: #6c757d; color: white; }
.approval-button--back:hover { background-color: #5a6268; }
@media screen and (max-width: 768px) {
    .comparison { flex-direction: column; }
    .comparison__section { margin-bottom: 20px; }
    .approval-actions { flex-direction: column; align-items: center; }
    .approval-button { width: 80%; margin-bottom: 10px; }
    .attendance-detail__table th { width: auto; }
}
</style>
@endsection