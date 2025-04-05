@extends('layouts.app')

@section('content')
<div class="attendance-detail stamp-correction-request-show">
    <h2 class="page-title">勤怠詳細</h2>

    @if (session('message'))
    <div class="alert alert-success">
        {{ session('message') }}
    </div>
    @endif

    <div class="approval-info">
        <div class="approval-info__item">
            <span class="approval-info__label">申請日時:</span>
            <span class="approval-info__value">{{ $request->created_at->format('Y年m月d日 H:i') }}</span>
        </div>
        <div class="approval-info__item">
            <span class="approval-info__label">承認日時:</span>
            <span class="approval-info__value">{{ $request->approved_at->format('Y年m月d日 H:i') }}</span>
        </div>
        <div class="approval-info__item">
            <span class="approval-info__label">承認者:</span>
            <span class="approval-info__value">{{ $request->approvedBy->name ?? '不明' }}</span>
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
                        <td>{{ \Carbon\Carbon::parse($request->attendance->date)->format('Y年m月d日') }}</td>
                    </tr>
                    <tr>
                        <th>出勤時間</th>
                        <td>{{ \Carbon\Carbon::parse($request->original_clock_in)->format('H:i') }}</td>
                    </tr>
                    <tr>
                        <th>退勤時間</th>
                        <td>{{ \Carbon\Carbon::parse($request->original_clock_out)->format('H:i') }}</td>
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

                    {{-- 修正後の休憩時間表示 --}}
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
        <a href="{{ route('admin.stamp_correction_request.list', ['status' => 'processed']) }}" class="approval-button approval-button--back">一覧に戻る</a>
    </div>
</div>

<style>
.approval-info {
    background-color: #f5f5f5;
    padding: 15px;
    border-radius: 8px;
    margin-bottom: 20px;
    display: flex;
    flex-wrap: wrap;
    gap: 20px;
}

.approval-info__item {
    margin-right: 20px;
}

.approval-info__label {
    font-weight: bold;
    margin-right: 5px;
}

.comparison-container {
    margin-bottom: 30px;
}

.comparison {
    display: flex;
    gap: 30px;
}

.comparison__section {
    flex: 1;
    padding: 20px;
    background-color: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.comparison__title {
    font-size: 18px;
    margin-bottom: 20px;
    text-align: center;
    padding-bottom: 10px;
    border-bottom: 1px solid #e0e0e0;
}

.approval-actions {
    display: flex;
    justify-content: center;
    gap: 20px;
    margin-top: 30px;
}

.approval-button {
    padding: 10px 20px;
    border-radius: 4px;
    cursor: pointer;
    text-decoration: none;
    text-align: center;
    font-weight: bold;
    display: inline-block;
}

.approval-button--back {
    background-color: #9E9E9E;
    color: white;
}

.approval-button--back:hover {
    background-color: #757575;
}

/* レスポンシブ対応 */
@media screen and (max-width: 768px) {
    .approval-info {
        flex-direction: column;
        gap: 10px;
    }

    .comparison {
        flex-direction: column;
    }
    
    .comparison__section {
        margin-bottom: 20px;
    }
    
    .approval-actions {
        flex-direction: column;
        align-items: center;
    }
    
    .approval-button {
        width: 100%;
        margin-bottom: 10px;
    }
}
</style>
@endsection 