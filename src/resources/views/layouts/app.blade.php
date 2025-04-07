<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Kinta') }}</title>

    @vite([
        'resources/css/app.css',
        'resources/js/app.js',
    ])
    @stack('styles')
    @stack('scripts')
</head>

<body>
    <nav class="nav">
        @auth
            @if(Auth::user()->isAdmin())
                <a href="{{ route('admin.attendance.list') }}" class="nav__logo">
                    <img src="{{ asset('img/logo_coachtech.svg') }}" alt="COACHTECH ロゴ">
                </a>
            @else
                <a href="{{ route('attendance.index') }}" class="nav__logo">
                    <img src="{{ asset('img/logo_coachtech.svg') }}" alt="COACHTECH ロゴ">
                </a>
            @endif
        @else
            <a href="/" class="nav__logo">
                <img src="{{ asset('img/logo_coachtech.svg') }}" alt="COACHTECH ロゴ">
            </a>
        @endauth

        @if(!Request::is('email/verify'))
            @if(Request::is('login', 'register') && !Request::is('admin/login'))
                <div class="nav__menu">
                    <a href="{{ route('login') }}" class="nav__item">ログイン</a>
                    <a href="{{ route('register') }}" class="nav__item">会員登録</a>
                </div>
            @elseif(Request::is('admin/login'))
                <div class="nav__menu">
                    <a href="{{ route('login') }}" class="nav__item">一般ログイン</a>
                </div>
            @else
                <div class="nav__menu">
                    @auth
                        @if (Auth::user()->isAdmin())
                            <a href="{{ route('admin.attendance.list') }}" class="nav__item">勤怠一覧</a>
                            <a href="{{ route('admin.staff.list') }}" class="nav__item">スタッフ一覧</a>
                            <a href="{{ route('stamp_correction_request.list') }}" class="nav__item">申請一覧</a>
                            <form method="POST" action="{{ route('admin.logout') }}" class="nav__form">
                                @csrf
                                <button type="submit" class="nav__logout">ログアウト</button>
                            </form>
                        @else
                            <a href="/attendance" class="nav__item">勤怠</a>
                            <a href="/attendance/list" class="nav__item">勤怠一覧</a>
                            <a href="/stamp_correction_request/list" class="nav__item">申請</a>
                            <form method="POST" action="{{ route('logout') }}" class="nav__form">
                                @csrf
                                <button type="submit" class="nav__logout">ログアウト</button>
                            </form>
                        @endif
                    @endauth
                </div>
            @endif
        @endif
    </nav>

    <main>
        @yield('content')
    </main>

    <footer class="footer">
        &copy; 2024 勤怠管理システム
    </footer>
</body>

</html>

