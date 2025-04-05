@extends('layouts.app')

@section('content')
<div class="attendance-detail stamp-correction-request-show">
    <h2 class="page-title">修正申請詳細</h2>

    @if (session('message'))
    <div class="alert alert-success">
        {{ session('message') }}
    </div>
    @endif

    @if ($errors->any())
    <div class="alert alert-danger">
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

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

                    @if (!empty($request->break_start) && is_array($request->break_start) && !empty($request->break_end) && is_array($request->break_end) && count($request->break_start) > 0)
                        {{-- break_start配列をループ --}}
                        @foreach($request->break_start as $index => $start)
                            {{-- 対応するbreak_endが存在することを確認 --}}
                            @if(isset($request->break_end[$index]))
                            <tr>
                                <th>休憩{{ $index + 1 }}</th>
                                <td>
                                    {{-- Carbonを使って時間をフォーマット --}}
                                    {{ \Carbon\Carbon::parse($start)->format('H:i') }} 〜
                                    {{ \Carbon\Carbon::parse($request->break_end[$index])->format('H:i') }}
                                </td>
                            </tr>
                            @endif
                        @endforeach
                    @else
                        {{-- 休憩がない場合の表示 --}}
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
        <form action="{{ route('admin.stamp_correction_request.approve', $request->id) }}" method="POST" class="approval-form">
            @csrf
            <button type="submit" class="approval-button approval-button--approve" onclick="return confirm('この申請を承認してもよろしいですか？')">承認する</button>
        </form>
        
        <form action="{{ route('admin.stamp_correction_request.reject', $request->id) }}" method="POST" class="approval-form">
            @csrf
            <button type="submit" class="approval-button approval-button--reject" onclick="return confirm('この申請を却下してもよろしいですか？')">却下する</button>
        </form>
        
        <a href="{{ route('admin.stamp_correction_request.list') }}" class="approval-button approval-button--back">一覧に戻る</a>
    </div>
</div>

<style>
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

.approval-form {
    display: inline;
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

.approval-button--approve {
    background-color: #4CAF50;
    color: white;
    border: none;
}

.approval-button--approve:hover {
    background-color: #388E3C;
}

.approval-button--reject {
    background-color: #F44336;
    color: white;
    border: none;
}

.approval-button--reject:hover {
    background-color: #D32F2F;
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