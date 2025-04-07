@extends('layouts.app')

@section('content')
<div class="l-auth-page">
    <div class="c-card">
        <h2>会員登録</h2>

        <x-alert type="danger" class="c-error-message--general">
            {{ $errors->first('general') }}
        </x-alert>

        <form method="POST" action="{{ route('register') }}" novalidate>
            @csrf
            <x-forms.group>
                <x-forms.label for="name">お名前</x-forms.label>
                <x-forms.input name="name" />
                <x-forms.error field="name" />
            </x-forms.group>

            <x-forms.group>
                <x-forms.label for="email">メールアドレス</x-forms.label>
                <x-forms.input name="email" type="email" />
                <x-forms.error field="email" />
            </x-forms.group>

            <x-forms.group>
                <x-forms.label for="password">パスワード</x-forms.label>
                <x-forms.input name="password" type="password" />
                <x-forms.error field="password" />
            </x-forms.group>

            <x-forms.group>
                <x-forms.label for="password_confirmation">パスワード（確認用）</x-forms.label>
                <x-forms.input name="password_confirmation" type="password" />
                <x-forms.error field="password_confirmation" />
            </x-forms.group>

            <x-button type="submit" variant="primary" class="c-button--auth">登録する</x-button>
        </form>

        <div class="auth-links">
            <a href="{{ route('login') }}" class="c-link">ログインはこちら</a>
        </div>
    </div>
</div>

@endsection