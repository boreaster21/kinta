@extends('layouts.app')

@section('content')
<div class="request-list">
    <h2 class="request-list__title">修正申請一覧</h2>

    @if (session('message'))
    <div class="request-list__message">
        {{ session('message') }}
    </div>
    @endif

    <div class="request-list__tabs">
        <a href="{{ route('stamp_correction_request.list', ['status' => 'pending']) }}" 
           class="request-list__tab {{ $currentStatus === 'pending' ? 'request-list__tab--active' : '' }}">
            承認待ち
        </a>
        <a href="{{ route('stamp_correction_request.list', ['status' => 'processed']) }}" 
           class="request-list__tab {{ $currentStatus === 'processed' ? 'request-list__tab--active' : '' }}">
            承認済み・却下
        </a>
    </div>

    <div class="request-list__container">
        <table class="request-list__table">
            <thead>
                <tr>
                    <th>申請日</th>
                    @if($isAdmin)
                    <th>申請者</th>
                    @endif
                    <th>対象日</th>
                    <th>修正項目</th>
                    <th>修正前</th>
                    <th>修正後</th>
                    <th>状態</th>
                    <th>操作</th>
                </tr>
            </thead>
            <tbody>
                @foreach($requests as $request)
                <tr>
                    <td>{{ $request->created_at->format('Y/m/d') }}</td>
                    @if($isAdmin)
                    <td>{{ $request->user->name }}</td>
                    @endif
                    <td>{{ $request->attendance->date->format('Y/m/d') }}</td>
                    <td>{{ $request->correction_type }}</td>
                    <td>{{ $request->original_value }}</td>
                    <td>{{ $request->requested_value }}</td>
                    <td>
                        @if($request->status === 'pending')
                        <span class="request-list__status request-list__status--pending">承認待ち</span>
                        @elseif($request->status === 'approved')
                        <span class="request-list__status request-list__status--approved">承認済み</span>
                        @else
                        <span class="request-list__status request-list__status--rejected">却下</span>
                        @endif
                    </td>
                    <td>
                        @if($isAdmin)
                            @if($request->status === 'pending')
                            <a href="{{ route('stamp_correction_request.approve.form', ['id' => $request->id]) }}" 
                               class="request-list__button request-list__button--detail">
                                詳細
                            </a>
                            @endif
                        @else
                            <a href="{{ route('attendance.show', ['id' => $request->attendance_id]) }}" 
                               class="request-list__button request-list__button--detail">
                                詳細
                            </a>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<style>
.request-list__button--detail {
    background-color: #4A90E2;
}

.request-list__button--detail:hover {
    background-color: #357ABD;
}
</style>
@endsection

@vite(['resources/js/stamp_correction_request.js'])