@extends('layouts.app')

@section('content')
<div class="staff-list">
    <h2 class="staff-list__title">スタッフ一覧</h2>

    <div class="staff-list__container">
        <table class="staff-list__table">
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
                        <a href="{{ route('admin.staff.monthly_attendance', ['id' => $staff->id]) }}" class="staff-list__button">勤怠詳細</a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection 