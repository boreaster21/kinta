@extends('layouts.app')

@section('content')
<div class="auth">
    <div class="auth__box">
        <h2 class="auth__title">管理者ログイン</h2>

        @if (session('message'))
        <div class="auth__message">
            {{ session('message') }}
        </div>
        @endif

        @if ($errors->has('general'))
        <div class="auth__error-message auth__error-message--general">
            {{ $errors->first('general') }}
        </div>
        @endif

        <form method="POST" action="{{ route('admin.login.store') }}" class="auth__form" novalidate>
            @csrf
            <div class="auth__group">
                <label class="auth__label">メールアドレス</label>
                <input type="email" name="email" class="auth__input" value="{{ old('email') }}" required autofocus>
                @error('email')
                <div class="auth__error-message">{{ $message }}</div>
                @enderror
            </div>

            <div class="auth__group">
                <label class="auth__label">パスワード</label>
                <input type="password" name="password" class="auth__input" required>
                @error('password')
                <div class="auth__error-message">{{ $message }}</div>
                @enderror
            </div>

            <button type="submit" class="auth__button">管理者ログインする</button>
        </form>
    </div>
</div>
@endsection