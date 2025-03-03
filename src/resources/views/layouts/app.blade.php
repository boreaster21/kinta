<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name', 'Kinta') }}</title>
    @vite([
    'resources/css/app.css',
    'resources/css/auth.css',
    'resources/css/attendance.css',
    'resources/css/attendance-list.css',
    'resources/css/attendance-detail.css',
    'resources/css/stamp_correction_request.css',
    ])
</head>

<body>
    <nav class="nav">
        <a href="/" class="nav__logo">
            <img src="{{ asset('img/logo_coachtech.svg') }}" alt="COACHTECH ロゴ">
        </a>
        <div class="nav__menu">
            @auth
            @if (Auth::user()->is_admin)
            <a href="/admin/attendance/list" class="nav__item">勤怠一覧</a>
            <a href="/admin/staff/list" class="nav__item">スタッフ一覧</a>
            <a href="/admin/stamp_correction_request/list" class="nav__item">申請一覧</a>
            @else
            <a href="/attendance" class="nav__item">勤怠</a>
            <a href="/attendance/list" class="nav__item">勤怠一覧</a>
            <a href="/stamp_correction_request/list" class="nav__item">申請</a>
            @endif
            <form method="POST" action="{{ route('logout') }}" class="inline">
                @csrf
                <button type="submit" class="nav__logout">ログアウト</button>
            </form>
            @else
            <a href="{{ route('login') }}" class="nav__item">ログイン</a>
            <a href="{{ route('register') }}" class="nav__item">会員登録</a>
            @endauth
        </div>
    </nav>

    <main>
        @yield('content')
    </main>

    <footer class="footer">
        &copy; 2024 勤怠管理システム
    </footer>

</body>

</html>