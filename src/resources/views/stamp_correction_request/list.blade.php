@extends('layouts.app')

@section('content')
<div class="request-list">
    <h2 class="request-list__title">修正申請一覧</h2>

    @if(session('success'))
    <div class="alert alert-success">
        {{ session('success') }}
    </div>
    @endif

    <div class="request-list__tabs">
        <button class="request-list__tab active" data-target="pending-requests">承認待ち</button>
        <button class="request-list__tab" data-target="approved-requests">承認済み</button>
    </div>

    <!-- 承認待ちリスト -->
    <div id="pending-requests">
        <h3 class="request-list__subtitle">承認待ち</h3>
        <table class="attendance-list__table">
            <thead>
                <tr>
                    <th>状態</th>
                    <th>名前</th>
                    <th>対象日付</th>
                    <th>申請理由</th>
                    <th>申請日時</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($pendingRequests as $request)
                <tr>
                    <td class="request-list__status pending">承認待ち</td>
                    <td>{{ $request->user->name }}</td>
                    <td>{{ \Carbon\Carbon::parse($request->attendance->date)->format('Y年m月d日') }}</td>
                    <td>{{ $request->reason }}</td>
                    <td>{{ \Carbon\Carbon::parse($request->created_at)->format('Y年m月d日 H:i') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <!-- 承認済みリスト -->
    <div id="approved-requests">
        <h3 class="request-list__subtitle">承認済み</h3>
        <table class="attendance-list__table">
            <thead>
                <tr>
                    <th>状態</th>
                    <th>名前</th>
                    <th>対象日付</th>
                    <th>申請理由</th>
                    <th>申請日時</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($approvedRequests as $request)
                <tr>
                    <td class="request-list__status approved">承認済み</td>
                    <td>{{ $request->user->name }}</td>
                    <td>{{ \Carbon\Carbon::parse($request->attendance->date)->format('Y年m月d日') }}</td>
                    <td>{{ $request->reason }}</td>
                    <td>{{ \Carbon\Carbon::parse($request->created_at)->format('Y年m月d日 H:i') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection

@vite(['resources/js/stamp_correction_request.js'])