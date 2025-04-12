@extends($isAdmin ? 'layouts.admin' : 'layouts.app')

@section('content')
<div class="l-container p-request-list {{ $isAdmin ? 'p-admin-request-list' : '' }}">
    <h2 class="c-title {{ $isAdmin ? 'c-title--admin' : '' }} p-request-list__title">
        申請一覧
    </h2>

    @if (session('message'))
        <x-alert type="success" :message="session('message')" />
    @endif

    <div class="p-request-list__tabs">
        <a href="{{ route('stamp_correction_request.list', ['status' => 'pending']) }}"
        class="p-request-list__tab {{ $status === 'pending' ? 'p-request-list__tab--active' : '' }}">
            承認待ち
        </a>
        <a href="{{ route('stamp_correction_request.list', ['status' => 'approved']) }}"
        class="p-request-list__tab {{ $status === 'approved' ? 'p-request-list__tab--active' : '' }}">
            承認済み
        </a>
        <a href="{{ route('stamp_correction_request.list', ['status' => 'rejected']) }}"
        class="p-request-list__tab {{ $status === 'rejected' ? 'p-request-list__tab--active' : '' }}">
            却下
        </a>
    </div>

    <div class="p-request-list__container {{ $isAdmin ? 'c-card c-card--admin' : '' }}">
        <table class="c-table c-table--fixed">
            <thead>
                <tr>
                    <th style="width: 10%;">状態</th>
                    @if($isAdmin)
                    <th style="width: 15%;">名前</th>
                    @endif
                    <th style="width: 15%;">対象日時</th>
                    <th style="width: 30%;">申請理由</th>
                    <th style="width: 15%;">申請日時</th>
                    <th style="width: 15%;">詳細</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($requests as $request)
                <tr>
                    <td>
                        <span @class([
                            'c-status-badge',
                            'c-status-badge--pending' => $request['status'] === 'pending',
                            'c-status-badge--approved' => $request['status'] === 'approved',
                            'c-status-badge--rejected' => $request['status'] === 'rejected',
                        ])>
                            {{ match($request['status']) {
                                'pending' => '承認待ち',
                                'approved' => '承認済',
                                'rejected' => '却下',
                                default => $request['status'],
                            } }}
                        </span>
                    </td>
                    @if($isAdmin)
                    <td>{{ $request['user_name'] }}</td>
                    @endif
                    <td>{{ $request['date'] }}</td>
                    <td class="is-reason-cell" title="{{ $request['reason'] }}">{{ $request['reason'] }}</td>
                    <td>{{ $request['created_at'] }}</td>
                    <td>
                        @php
                            $detailUrl = '#'; // Default for rejected or unknown
                            $buttonVariant = 'secondary';
                            if ($isAdmin) {
                                if ($request['status'] === 'pending') {
                                    $detailUrl = route('admin.stamp_correction_request.show', $request['id']); // Correct route for admin approval form
                                    $buttonVariant = 'primary';
                                } elseif ($request['status'] === 'approved') {
                                    $detailUrl = route('stamp_correction_request.approved', $request['id']);
                                }
                            } else {
                                if ($request['status'] === 'pending') {
                                    $detailUrl = route('stamp_correction_request.pending', $request['id']); // Correct route for user pending view
                                    $buttonVariant = 'primary';
                                } elseif ($request['status'] === 'approved') {
                                    $detailUrl = route('stamp_correction_request.approved', $request['id']); // Correct route for user approved view
                                }
                            }
                        @endphp
                        <x-button as="a" :href="$detailUrl" :variant="$buttonVariant" size="sm" :disabled="$detailUrl === '#'">詳細</x-button>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="{{ $isAdmin ? 6 : 5 }}" style="text-align: center;">該当する申請はありません。</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        @if(method_exists($requests, 'links'))
            {{ $requests->links() }}
        @endif
    </div>
</div>
@endsection
