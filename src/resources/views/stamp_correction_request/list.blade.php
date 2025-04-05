@extends('layouts.app')

@section('content')
<div class="request-list">
    <h2 class="page-title">申請一覧</h2>

    @if (session('message'))
    <div class="request-list__message request-list__message--success">
        {{ session('message') }}
    </div>
    @endif

    <div class="request-list__tabs">
        <a href="{{ route('stamp_correction_request.list', ['status' => 'pending']) }}" 
           class="request-list__tab {{ $status === 'pending' ? 'request-list__tab--active' : '' }}">
            承認待ち
        </a>
        <a href="{{ route('stamp_correction_request.list', ['status' => 'processed']) }}" 
           class="request-list__tab {{ $status === 'processed' ? 'request-list__tab--active' : '' }}">
            承認済み
        </a>
    </div>

    <div class="request-list__container">
        <table class="request-list__table">
            <thead>
                <tr>
                    <th>状態</th>
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
                        @if($request['status'] === 'pending')
                        <span class="request-list__status request-list__status--pending">承認待ち</span>
                        @elseif($request['status'] === 'approved')
                        <span class="request-list__status request-list__status--approved">承認済み</span>
                        @else
                        <span class="request-list__status request-list__status--rejected">却下</span>
                        @endif
                    </td>
                    <td>{{ $request['date'] }}</td>
                    <td>{{ $request['reason'] }}</td>
                    <td>{{ $request['created_at'] }}</td>
                    <td>
                        <a href="{{ $request['detail_url'] }}" 
                           class="request-list__detail-link">
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