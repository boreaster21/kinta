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
    'resources/css/verify-email.css',
    'resources/css/admin/attendance-list.css',
    'resources/css/admin/staff-list.css',
    'resources/css/admin/monthly-attendance.css',
    'resources/css/admin/stamp-correction-request.css'
    ])
    <style>
        /* 共通のレスポンシブスタイル */
        :root {
            --max-content-width: 1400px;
            --content-padding: 20px;
        }

        body {
            margin: 0;
            padding: 0;
            min-height: 100vh;
        }

        .container {
            width: 100%;
            max-width: var(--max-content-width);
            margin: 0 auto;
            padding: 0 var(--content-padding);
            box-sizing: border-box;
        }

        /* 大画面（1540px以上） */
        @media screen and (min-width: 1541px) {
            .container {
                max-width: 1500px;
            }

            .table-responsive {
                margin: 0 auto;
            }
        }

        /* PC画面（1400px-1540px） */
        @media screen and (min-width: 1400px) and (max-width: 1540px) {
            .container {
                max-width: 1300px;
            }

            .table-responsive {
                margin: 0 auto;
            }
        }

        /* 中間画面（849px-1399px） */
        @media screen and (min-width: 851px) and (max-width: 1399px) {
            .container {
                max-width: 90%;
                padding: 0 15px;
            }

            .table-responsive {
                width: 100%;
                margin: 0 auto;
            }

            table {
                min-width: 100%;
            }
        }

        /* タブレット画面（768px-850px） */
        @media screen and (min-width: 768px) and (max-width: 850px) {
            .container {
                width: 100%;
                max-width: none;
                padding: 0;
            }

            .content-wrapper {
                padding: 0 15px;
                width: 100%;
                box-sizing: border-box;
            }

            .table-responsive-wrapper {
                width: 100%;
                margin: 0;
                padding: 0 15px;
                box-sizing: border-box;
            }

            .table-responsive {
                width: 100%;
                margin: 0;
                border-collapse: collapse;
            }

            table {
                width: 100%;
                min-width: 100%;
                margin: 0;
            }

            th, td {
                padding: 12px !important;
                white-space: nowrap;
            }
        }

        /* スマホ画面（767px以下） */
        @media screen and (max-width: 767px) {
            .container {
                width: 100%;
                padding: 0 10px;
            }

            .content-wrapper {
                padding: 0 10px;
            }

            .table-responsive-wrapper {
                width: 100%;
                margin: 0 -10px;
                padding: 0 10px;
            }

            .table-responsive {
                width: 100%;
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
            }

            table {
                min-width: 100%;
            }

            th, td {
                padding: 8px !important;
                font-size: 14px;
            }
        }

        /* 共通のテーブルスタイル */
        .table-responsive {
            width: 100%;
            margin-bottom: 1rem;
            border-collapse: collapse;
        }

        .table-responsive th,
        .table-responsive td {
            padding: 15px;
            border: 1px solid #ddd;
            vertical-align: middle;
        }

        /* テーブルラッパー */
        .table-responsive-wrapper {
            width: 100%;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        /* 共通のフォームスタイル */
        .form-group {
            margin-bottom: 1rem;
        }

        .form-control {
            width: 100%;
            padding: 0.5rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }

        /* 共通のボタンスタイル */
        .btn {
            display: inline-block;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            text-align: center;
            font-size: 16px;
            transition: background-color 0.3s;
        }

        /* フレックスボックスユーティリティ */
        .d-flex {
            display: flex;
        }

        .justify-content-between {
            justify-content: space-between;
        }

        .align-items-center {
            align-items: center;
        }

        .flex-wrap {
            flex-wrap: wrap;
        }

        /* マージンユーティリティ */
        .mt-3 {
            margin-top: 1rem;
        }

        .mb-3 {
            margin-bottom: 1rem;
        }

        .mx-auto {
            margin-left: auto;
            margin-right: auto;
        }
    </style>
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
        <div class="nav__menu">
            @auth
            @if (Auth::user()->isAdmin())
            <a href="{{ route('admin.attendance.list') }}" class="nav__item">勤怠一覧</a>
            <a href="{{ route('admin.staff.list') }}" class="nav__item">スタッフ一覧</a>
            <a href="{{ route('admin.stamp_correction_request.list') }}" class="nav__item">申請一覧</a>
            <form method="POST" action="{{ route('admin.logout') }}" class="inline">
                @csrf
                <button type="submit" class="nav__logout">ログアウト</button>
            </form>
            @else
            <a href="/attendance" class="nav__item">勤怠</a>
            <a href="/attendance/list" class="nav__item">勤怠一覧</a>
            <a href="/stamp_correction_request/list" class="nav__item">申請</a>
            <form method="POST" action="{{ route('logout') }}" class="inline">
                @csrf
                <button type="submit" class="nav__logout">ログアウト</button>
            </form>
            @endif
            @else
            @if (!Request::is('admin/login'))
            <a href="{{ route('login') }}" class="nav__item">ログイン</a>
            <a href="{{ route('register') }}" class="nav__item">会員登録</a>
            @endif
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