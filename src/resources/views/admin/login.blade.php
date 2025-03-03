@extends('layouts.app')

@section('content')
<div class="form">
    <h2 class="form__title">管理者ログイン</h2>

    @if ($errors->any())
    <div class="form__error-message">
        <ul>
            @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <form method="POST" action="{{ route('admin.login') }}">
        @csrf
        <div class="form__group">
            <label class="form__label">メールアドレス</label>
            <input type="email" name="email" class="form__input" required autofocus>
        </div>

        <div class="form__group">
            <label class="form__label">パスワード</label>
            <input type="password" name="password" class="form__input" required>
        </div>

        <button type="submit" class="form__button">ログイン</button>
    </form>
</div>
@endsection