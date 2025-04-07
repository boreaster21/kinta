@extends('layouts.app')

@section('content')
<div class="l-auth-page">
    <div class="c-card">
        <h2>ログイン</h2>

        <x-alert type="danger" class="c-error-message--general">
            {{ $errors->first('general') }}
        </x-alert>

        <form method="POST" action="{{ route('login') }}" novalidate>
            @csrf
            <x-forms.group>
                <x-forms.label for="email">メールアドレス</x-forms.label>
                <x-forms.input name="email" type="email" required autofocus />
                <x-forms.error field="email" />
            </x-forms.group>

            <x-forms.group>
                <x-forms.label for="password">パスワード</x-forms.label>
                <x-forms.input name="password" type="password" required />
                <x-forms.error field="password" />
            </x-forms.group>

            <x-button type="submit" variant="primary" class="c-button--auth">ログインする</x-button>
        </form>

        <div class="auth-links">
            <a href="{{ route('register') }}" class="c-link">会員登録はこちら</a>
        </div>
    </div>
</div>
@endsection