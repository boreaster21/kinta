@extends('layouts.admin')

@section('content')
<div class="admin-auth">
    <div class="admin-auth__box">
        <h1 class="admin-auth__title">管理者ログイン</h1>
        
        @if (session('message'))
            <div class="admin-auth__message">
                {{ session('message') }}
            </div>
        @endif

        @if ($errors->has('general'))
            <div class="admin-auth__error-message--general">
                {{ $errors->first('general') }}
            </div>
        @endif

        <form method="POST" action="{{ route('admin.login.store') }}" class="admin-auth__form" novalidate>
            @csrf
            <div class="admin-auth__group">
                <label for="email" class="admin-auth__label">メールアドレス</label>
                <input 
                    type="email" 
                    id="email" 
                    name="email" 
                    class="admin-auth__input @error('email') admin-auth__input--error @enderror" 
                    value="{{ old('email') }}" 
                    required 
                    autofocus
                >
                @error('email')
                    <div class="admin-auth__error-message">{{ $message }}</div>
                @enderror
            </div>

            <div class="admin-auth__group">
                <label for="password" class="admin-auth__label">パスワード</label>
                <input 
                    type="password" 
                    id="password" 
                    name="password" 
                    class="admin-auth__input @error('password') admin-auth__input--error @enderror" 
                    required
                >
                @error('password')
                    <div class="admin-auth__error-message">{{ $message }}</div>
                @enderror
            </div>

            <button type="submit" class="admin-auth__button">管理者ログイン</button>
        </form>
    </div>
</div>
@endsection