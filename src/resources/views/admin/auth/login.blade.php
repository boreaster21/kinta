@extends('layouts.admin')

@section('content')
<div class="l-auth-page">
    <div class="c-card p-admin-login__box">
        <h1>管理者ログイン</h1>

        <x-alert type="info" :message="session('message')" />
        <x-alert type="danger" class="c-error-message--general" :message="$errors->first('general')" />

        <form method="POST" action="{{ route('admin.login.store') }}" novalidate>
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

            <x-button type="submit" variant="primary" class="c-button--auth">管理者ログインする</x-button>
        </form>
    </div>
</div>
@endsection