@extends('layouts.app')

@section('content')
<div class="verify-email">
    <div class="verify-email__container">
        <h2 class="verify-email__title">メール認証</h2>

        @if (session('status') == 'verification-link-sent')
            <div class="verify-email__message verify-email__message--success">
                {{ __('認証メールを再送信しました。') }}
            </div>
        @endif

        <div class="verify-email__message">
            {{ __('登録していただいたメールアドレスに認証メールを送付しました。') }}<br>
            {{ __('メール認証を完了してください。') }}
        </div>

        <div class="verify-email__action">
            <div class="verify-email__resend">
                <form method="POST" action="{{ route('verification.send') }}">
                    @csrf
                    <button type="submit" class="verify-email__link">
                        {{ __('認証メールを再送信する') }}
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection