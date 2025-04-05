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
                <label for="email" class="auth__label">メールアドレス</label>
                <input 
                    type="email" 
                    id="email" 
                    name="email" 
                    class="auth__input @error('email') auth__input--error @enderror" 
                    value="{{ old('email') }}" 
                    required 
                    autofocus
                >
                @error('email')
                    <div class="auth__error-message">{{ $message }}</div>
                @enderror
            </div>

            <div class="auth__group">
                <label for="password" class="auth__label">パスワード</label>
                <input 
                    type="password" 
                    id="password" 
                    name="password" 
                    class="auth__input @error('password') auth__input--error @enderror" 
                    required
                >
                @error('password')
                    <div class="auth__error-message">{{ $message }}</div>
                @enderror
            </div>

            <button type="submit" class="auth__button">ログイン</button>
        </form>

        <div class="auth__links">
            アカウントをお持ちでない方は <a href="{{ route('register') }}" class="auth__link">会員登録はこちら</a>
        </div>
    </div>
</div>
@endsection