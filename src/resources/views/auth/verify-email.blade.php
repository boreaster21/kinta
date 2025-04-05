@extends('layouts.app')

@section('content')
<div class="verify-email">
    <div class="verify-email__box">
        <h2 class="verify-email__title">メール認証</h2>

        @if (session('status') == 'verification-link-sent')
            <div class="verify-email__success">
                {{ __('認証メールを再送信しました。') }}
            </div>
        @endif

        <div class="verify-email__message">
            {{ __('登録していただいたメールアドレスに認証メールを送付しました。') }}<br>
            {{ __('メール認証を完了してください。') }}
        </div>

        <div class="verify-email__form">
            <form method="POST" action="{{ route('verification.send') }}">
                @csrf
                <button type="submit" class="verify-email__button">
                    {{ __('認証メールを再送信する') }}
                </button>
            </form>
        </div>
    </div>
</div>

<style>
/* インラインスタイルを追加してCSSが反映されるかテスト */
.verify-email {
    display: flex;
    justify-content: center;
    align-items: center;
    height: 100vh;
    background: #f8fafc;
}

.verify-email__box {
    background: #ffffff;
    padding: 2rem;
    border-radius: 10px;
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
    width: 100%;
    max-width: 400px;
    text-align: center;
}

.verify-email__title {
    font-size: 1.8rem;
    font-weight: bold;
    margin-bottom: 1rem;
    color: #333;
}

.verify-email__message {
    font-size: 1rem;
    color: #555;
    margin-bottom: 1.5rem;
}

.verify-email__success {
    color: #28a745;
    font-size: 1rem;
    background: #eaf7ea;
    padding: 10px;
    border-radius: 5px;
    margin-bottom: 1rem;
}

.verify-email__form {
    display: flex;
    flex-direction: column;
    align-items: center;
}

.verify-email__button {
    width: 100%;
    padding: 0.75rem;
    background-color: #2563eb;
    color: white;
    border: none;
    border-radius: 5px;
    font-size: 1rem;
    font-weight: bold;
    cursor: pointer;
    transition: background 0.3s;
}

.verify-email__button:hover {
    background-color: #1d4ed8;
}
</style>
@endsection