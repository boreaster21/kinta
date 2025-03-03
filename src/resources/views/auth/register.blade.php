@extends('layouts.app')

@section('content')
<div class="auth">
    <div class="auth__box">
        <h2 class="auth__title">会員登録</h2>

        @if ($errors->has('general'))
        <div class="auth__error">
            <ul>
                <li>{{ $errors->first('general') }}</li>
            </ul>
        </div>
        @endif

        <form method="POST" action="{{ route('register') }}" class="auth__form" novalidate>
            @csrf
            <div class="auth__group">
                <label class="auth__label">お名前</label>
                <input type="text" name="name" class="auth__input" value="{{ old('name') }}">
                @error('name')
                <div class="auth__error-message">{{ $message }}</div>
                @enderror
            </div>

            <div class="auth__group">
                <label class="auth__label">メールアドレス</label>
                <input type="email" name="email" class="auth__input" value="{{ old('email') }}">
                @error('email')
                <div class="auth__error-message">{{ $message }}</div>
                @enderror
            </div>

            <div class="auth__group">
                <label class="auth__label">パスワード</label>
                <input type="password" name="password" class="auth__input">
                @error('password')
                <div class="auth__error-message">{{ $message }}</div>
                @enderror
            </div>

            <div class="auth__group">
                <label class="auth__label">パスワード（確認用）</label>
                <input type="password" name="password_confirmation" class="auth__input">
                @error('password_confirmation')
                <div class="auth__error-message">{{ $message }}</div>
                @enderror
            </div>

            <button type="submit" class="auth__button">登録</button>
        </form>

        <div class="auth__link">
            既にアカウントをお持ちですか？ <a href="{{ route('login') }}">ログインはこちら</a>
        </div>
    </div>
</div>
@endsection