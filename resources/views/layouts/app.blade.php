<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Baseball SLG 管理画面')</title>

    {{-- Bootstrap CSS --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    {{-- 任意: カスタムCSS（あれば） --}}
    @stack('styles')
</head>
<body class="bg-light">
    {{-- ナビゲーションバー --}}
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm mb-4">
        <div class="container-fluid">
            <a class="navbar-brand fw-bold" href="{{ route('title') }}">⚾ Baseball SLG</a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#adminNavbar"
                aria-controls="adminNavbar" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="adminNavbar">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    @auth
                    {{-- メインメニュー --}}
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('home') }}">メインメニュー</a>
                    </li>
                    @endauth
                    
                    {{-- ゲーム（ユーザー向け） --}}
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('game.index') }}">試合シミュレーター</a>
                    </li>
                    @auth
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('manager.game.index') }}">マネージャーモード</a>
                    </li>
                    @endauth
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('games.index') }}">試合履歴</a>
                    </li>

                    {{-- チーム編成（ログイン必須） --}}
                    @auth
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="teamDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            チーム編成
                        </a>
                        <ul class="dropdown-menu" aria-labelledby="teamDropdown">
                            <li><a class="dropdown-item" href="{{ route('admin.custom-teams.index') }}">オリジナルチーム</a></li>
                            {{-- <li><a class="dropdown-item" href="#">ドラフトチーム</a></li> --}}
                        </ul>
                    </li>
                    @endauth

                    {{-- 管理（管理者向け） --}}
                    @auth
                    @if(Auth::user()->isAdmin())
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="adminDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            管理
                        </a>
                        <ul class="dropdown-menu" aria-labelledby="adminDropdown">
                            <li><h6 class="dropdown-header">基本データ</h6></li>
                            <li><a class="dropdown-item" href="{{ route('teams.index') }}">チーム一覧</a></li>
                            <li><a class="dropdown-item" href="{{ route('players.index') }}">選手一覧</a></li>
                            <li><a class="dropdown-item" href="{{ route('admin.player-seasons.index') }}">年度別能力</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><h6 class="dropdown-header">nf3データ</h6></li>
                            <li><a class="dropdown-item" href="{{ route('admin.nf3.batting_index') }}">打撃データ</a></li>
                            <li><a class="dropdown-item" href="{{ route('admin.nf3.pitching_index') }}">投手データ</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><h6 class="dropdown-header">インポート</h6></li>
                            <li><a class="dropdown-item" href="{{ route('admin.nf3.batting_paste_form') }}">打撃データインポート</a></li>
                            <li><a class="dropdown-item" href="{{ route('admin.nf3.pitching_paste_form') }}">投手データインポート</a></li>
                        </ul>
                    </li>
                    @endif
                    @endauth
                </ul>

                {{-- 右側スペース（ログイン状態表示） --}}
                <ul class="navbar-nav ms-auto">
                    @auth
                        <li class="nav-item">
                            <span class="navbar-text me-3">
                                こんにちは、{{ Auth::user()->name }} さん
                            </span>
                        </li>
                        <li class="nav-item">
                            <form method="POST" action="{{ route('logout') }}" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-outline-secondary btn-sm">ログアウト</button>
                            </form>
                        </li>
                    @else
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('login') }}">ログイン</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('register') }}">新規登録</a>
                        </li>
                    @endauth
                    <li class="nav-item">
                        <a class="nav-link text-muted" href="{{ route('title') }}">トップへ戻る</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    {{-- メインコンテンツ --}}
    <main class="container mb-5">
        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if (session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @yield('content')
    </main>

    {{-- フッター --}}
    <footer class="text-center text-muted py-3 border-top">
        <small>© {{ date('Y') }} Baseball SLG System</small>
    </footer>

    {{-- Bootstrap JS --}}
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    {{-- 任意: 各ページ用スクリプト --}}
    @stack('scripts')
</body>
</html>
