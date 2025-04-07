@extends('layouts.admin')

@section('content')
<div class="l-container p-admin-request-approve">
    <h2 class="c-title">申請詳細</h2>

    <x-alert type="success" class="p-admin-request-approve__message" :message="session('message')" />

    @if ($errors->any())
    <x-alert type="danger">
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </x-alert>
    @endif

    {{-- Basic Request Info --}}
    <div class="p-admin-request-approve__info c-card u-mb-20">
        <table class="c-table c-table--detail">
            <tr>
                <th>申請者</th>
                <td>{{ $request->user->name }}</td>
                <th>対象日付</th>
                <td>{{ \Carbon\Carbon::parse($request->attendance->date)->format('Y年m月d日') }}</td>
            </tr>
            <tr>
                <th>申請日時</th>
                <td>{{ $request->created_at->format('Y/m/d H:i') }}</td>
                <th>ステータス</th>
                <td>
                    @php
                        $statusModifier = match($request->status) {
                            'pending' => '--pending',
                            'approved' => '--approved',
                            'rejected' => '--rejected',
                            default => ''
                        };
                    @endphp
                    <span class="p-request-list__status p-request-list__status{{ $statusModifier }}">
                        {{ match($request->status) {
                            'pending' => '承認待ち',
                            'approved' => '承認済み',
                            'rejected' => '却下',
                            default => '不明'
                        } }}
                    </span>
                </td>
            </tr>
        </table>
    </div>

    {{-- Comparison Table --}}
    <div class="p-admin-request-approve__comparison-container c-card">
        <h3 class="p-admin-request-approve__comparison-title">修正内容比較</h3>
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

    <div class="p-admin-request-approve__actions">
        <form action="{{ route('admin.stamp_correction_request.approve', $request->id) }}" method="POST" class="p-admin-request-approve__form">
            @csrf
            <x-button type="submit" variant="primary" class="p-admin-request-approve__button p-admin-request-approve__button--approve" onclick="return confirm('この申請を承認してもよろしいですか？')">承認する</x-button>
        </form>

        <form action="{{ route('admin.stamp_correction_request.reject', $request->id) }}" method="POST" class="p-admin-request-approve__form">
            @csrf
            <x-button type="submit" variant="danger" class="p-admin-request-approve__button p-admin-request-approve__button--reject" onclick="return confirm('この申請を却下してもよろしいですか？')">却下する</x-button>
        </form>

        <x-button as="a" :href="route('stamp_correction_request.list')" variant="secondary" class="p-admin-request-approve__button p-admin-request-approve__button--back">一覧に戻る</x-button>
    </div>
</div>

@endsection 