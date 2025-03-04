@extends('layouts.app')

@section('content')
<div class="auth">
    <div class="auth__box">
        <h2 class="auth__title">ログイン</h2>

        @if ($errors->has('general'))
        <div class="auth__error-message auth__error-message--general">
            {{ $errors->first('general') }}
        </div>
        @endif

        <form method="POST" action="{{ route('login') }}" class="auth__form" novalidate>
            @csrf
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

            <button type="submit" class="auth__button">ログイン</button>
        </form>

        <div class="auth__link">
            アカウントをお持ちでない方は <a href="{{ route('register') }}">会員登録はこちら</a>
        </div>
    </div>
</div>
@endsection