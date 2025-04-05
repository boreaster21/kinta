@extends('layouts.app')

@section('content')
<div class="auth">
    <div class="auth__box">
        <h2 class="auth__title">会員登録</h2>

        @if ($errors->has('general'))
        <div class="auth__error-message auth__error-message--general">
            {{ $errors->first('general') }}
        </div>
        @endif

        <form method="POST" action="{{ route('register') }}" novalidate>
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

<style>
/* インラインスタイルを追加してCSSが反映されるかテスト */
.auth {
    padding: 20px;
    max-width: 500px;
    margin: 0 auto;
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 80vh;
}

.auth__box {
    background: #ffffff;
    padding: 2rem;
    border-radius: 10px;
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
    width: 100%;
    max-width: 400px;
}

.auth__title {
    font-size: 24px;
    margin-bottom: 30px;
    text-align: center;
}

.auth__group {
    margin-bottom: 20px;
}

.auth__label {
    display: block;
    margin-bottom: 8px;
    font-weight: bold;
}

.auth__input {
    width: 100%;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 16px;
    box-sizing: border-box;
}

.auth__error-message {
    color: #ff3333;
    font-size: 14px;
    margin-top: 5px;
}

.auth__error-message--general {
    background: #ffdddd;
    border: 1px solid #ff3333;
    padding: 10px;
    border-radius: 5px;
    text-align: center;
    margin-bottom: 15px;
}

.auth__button {
    width: 100%;
    padding: 12px;
    background: #4CAF50;
    color: white;
    border: none;
    border-radius: 4px;
    font-size: 16px;
    cursor: pointer;
    margin-top: 10px;
}

.auth__link {
    display: block;
    text-align: center;
    margin-top: 20px;
    color: #666;
}
</style>
@endsection