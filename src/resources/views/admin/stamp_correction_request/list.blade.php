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
        <a href="{{ route('admin.stamp_correction_request.list', ['status' => 'pending']) }}" 
           class="request-list__tab {{ $currentStatus === 'pending' ? 'request-list__tab--active' : '' }}">
            承認待ち
        </a>
        <a href="{{ route('admin.stamp_correction_request.list', ['status' => 'processed']) }}" 
           class="request-list__tab {{ $currentStatus === 'processed' ? 'request-list__tab--active' : '' }}">
            承認済み・却下
        </a>
    </div>

    <div class="request-list__container">
        <table class="request-list__table">
            <thead>
                <tr>
                    <th>状態</th>
                    <th>名前</th>
                    <th>対象日時</th>
                    <th>申請理由</th>
                    <th>申請日時</th>
                    <th>詳細</th>
                </tr>
            </thead>
            <tbody>
                @foreach($requests as $request)
                <tr>
                    <td>
                        @if($request->status === 'pending')
                        <span class="request-list__status request-list__status--pending">承認待ち</span>
                        @elseif($request->status === 'approved')
                        <span class="request-list__status request-list__status--approved">承認済み</span>
                        @else
                        <span class="request-list__status request-list__status--rejected">却下</span>
                        @endif
                    </td>
                    <td>{{ $request->user->name }}</td>
                    <td>{{ $request->attendance->date->format('Y/m/d') }}</td>
                    <td>{{ $request->reason }}</td>
                    <td>{{ $request->created_at->format('Y/m/d H:i') }}</td>
                    <td>
                        <a href="{{ route('admin.stamp_correction_request.approve.form', ['id' => $request->id]) }}" 
                           class="request-list__button request-list__button--detail">
                            詳細
                        </a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

@endsection

@vite(['resources/js/stamp_correction_request.js'])