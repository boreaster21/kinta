@extends('layouts.app')

@section('content')
<div class="l-container p-admin-request-approve">
    <h2 class="c-title">申請詳細</h2>

    <div class="p-admin-request-approve__info c-card u-mb-20">
        <table class="c-table c-table--detail">
            <tr>
                <th>名前</th>
                <td>{{ $request->user->name }}</td>
                <th>対象日付</th>
                <td>{{ \Carbon\Carbon::parse($request->attendance->date)->format('Y年m月d日') }}</td>
            </tr>
            <tr>
                <th>申請日時</th>
                <td>{{ $request->created_at->format('Y/m/d H:i') }}</td>
                <th>処理日時</th>
                <td>
                    @if($request->approved_at)
                        {{ \Carbon\Carbon::parse($request->approved_at)->format('Y/m/d H:i') }}
                    @elseif($request->rejected_at)
                        {{ \Carbon\Carbon::parse($request->rejected_at)->format('Y/m/d H:i') }}
                    @else
                        -
                    @endif
                </td>
            </tr>
            <tr>
                <th>ステータス</th>
                <td colspan="3">
                    @php
                        $statusModifier = '' ;
                        $statusText = '不明';
                        if ($request->status === 'approved') {
                            $statusModifier = '--approved';
                            $statusText = '承認済み';
                        } elseif ($request->status === 'rejected') {
                            $statusModifier = '--rejected';
                            $statusText = '却下';
                        }
                    @endphp
                    <span class="p-request-list__status p-request-list__status{{ $statusModifier }}">
                        {{ $statusText }}
                    </span>
                </td>
            </tr>
        </table>
    </div>

    <div class="p-admin-request-approve__comparison-container c-card">
        <h3 class="p-admin-request-approve__comparison-title">修正内容</h3>
        <table class="c-table c-table--comparison">
            <thead>
                <tr>
                    <th>項目</th>
                    <th>修正前</th>
                    <th>修正後</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <th>出勤</th>
                    <td>{{ $request->original_clock_in ? \Carbon\Carbon::parse($request->original_clock_in)->format('H:i') : '-' }}</td>
                    <td>{{ \Carbon\Carbon::parse($request->clock_in)->format('H:i') }}</td>
                </tr>
                <tr>
                    <th>退勤</th>
                    <td>{{ $request->original_clock_out ? \Carbon\Carbon::parse($request->original_clock_out)->format('H:i') : '-' }}</td>
                    <td>{{ \Carbon\Carbon::parse($request->clock_out)->format('H:i') }}</td>
                </tr>
                {{-- Combine Breaks --}}
                @php
                    $maxBreaks = max(count($request->original_break_start ?? []), count($request->break_start ?? []));
                @endphp
                @for ($i = 0; $i < $maxBreaks; $i++)
                <tr>
                    <th>休憩{{ $i + 1 }}</th>
                    <td>
                        @if(isset($request->original_break_start[$i]) && isset($request->original_break_end[$i]))
                            {{ $request->original_break_start[$i] }} 〜 {{ $request->original_break_end[$i] }}
                        @else
                            -
                        @endif
                    </td>
                    <td>
                        @if(isset($request->break_start[$i]) && isset($request->break_end[$i]))
                            {{ \Carbon\Carbon::parse($request->break_start[$i])->format('H:i') }} 〜 {{ \Carbon\Carbon::parse($request->break_end[$i])->format('H:i') }}
                        @else
                            -
                        @endif
                    </td>
                </tr>
                @endfor
                @if($maxBreaks === 0)
                <tr>
                    <th>休憩</th>
                    <td>休憩なし</td>
                    <td>休憩なし</td>
                </tr>
                @endif
                <tr>
                    <th>備考</th>
                    <td>{{ $request->original_reason ?? '-' }}</td>
                    <td>{{ $request->reason ?? '-' }}</td>
                </tr>
            </tbody>
        </table>
    </div>

    {{-- Use admin actions class if needed --}}
    <div class="p-admin-request-approve__actions">
        @if($request->status === 'approved')
        <x-button type="button" variant="primary" class="p-admin-request-approve__button p-request-detail__button--approved-disabled" :disabled="true">承認済み</x-button>
        @elseif($request->status === 'rejected')
        <x-button type="button" variant="danger" class="p-admin-request-approve__button p-request-detail__button--rejected-disabled" :disabled="true">却下済み</x-button>
        @endif
        <x-button as="a" :href="route('stamp_correction_request.list', ['status' => 'processed']) " variant="secondary" class="p-admin-request-approve__button p-admin-request-approve__button--back">一覧に戻る</x-button>
    </div>
</div>

@endsection