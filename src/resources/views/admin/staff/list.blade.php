@extends('layouts.admin')

@section('content')
<div class="l-container p-admin-staff-list">
    <h2 class="c-title p-admin-staff-list__title">スタッフ一覧</h2>

    <div class="c-card p-admin-staff-list__container">
        <table class="c-table p-admin-staff-list__table">
            <thead>
                <tr>
                    <th>名前</th>
                    <th>メールアドレス</th>
                    <th>月次勤怠</th>
                </tr>
            </thead>
            <tbody>
                @foreach($staffs as $staff)
                <tr>
                    <td>{{ $staff->name }}</td>
                    <td>{{ $staff->email }}</td>
                    <td>
                        <x-button as="a" :href="route('admin.staff.monthly_attendance', ['id' => $staff->id])" variant="secondary" size="sm" class="p-admin-staff-list__detail-button">詳細</x-button>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection