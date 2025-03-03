@extends('layouts.app')

@section('content')
<div class="verify-email">
    <div class="verify-email__box">
        <h2 class="verify-email__title">メール認証が必要です</h2>
        <p class="verify-email__message">登録いただいたメールアドレスに認証メールを送付しました。メール認証を完了してください。</p>
        <p class="verify-email__message">認証メールを受け取っていない場合は、以下のボタンをクリックしてください。</p>

        @if (session('message'))
        <div class="verify-email__success">
            {{ session('message') }}
        </div>
        @endif

        <form method="POST" action="{{ route('verification.resend') }}" class="verify-email__form">
            @csrf
            <button type="submit" class="verify-email__button">認証メールを再送する</button>
        </form>
    </div>
</div>
@endsection