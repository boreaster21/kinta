@extends('layouts.app')

@section('content')
<div class="l-auth-page l-auth-page--verify-email">
    <div class="c-card c-card--verify-email p-verify-email__container">
        <h2 class="c-title p-verify-email__title">メール認証</h2>

        <x-alert type="success" class="p-verify-email__message--success">
            @if (session('status') == 'verification-link-sent')
                {{ __('認証メールを再送信しました。') }}
            @endif
        </x-alert>

        <div class="p-verify-email__message">
            {{ __('登録していただいたメールアドレスに認証メールを送付しました。') }}<br>
            {{ __('メール認証を完了してください。') }}
        </div>

        <div class="p-verify-email__action">
            <form method="POST" action="{{ route('verification.send') }}" style="width: 100%;">
                @csrf
                <x-button type="submit" variant="secondary" class="c-button--auth">
                    {{ __('認証メールを再送信する') }}
                </x-button>
            </form>
        </div>
    </div>
</div>

@endsection