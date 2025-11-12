@extends('layouts.app')

@section('title', 'メインメニュー')

@section('content')
<div class="container">
    <div class="text-center mb-5">
        <h1 class="display-5 fw-bold mb-2">⚾ Baseball SLG</h1>
        <p class="text-muted">こんにちは、{{ Auth::user()->name }} さん</p>
    </div>

    <div class="row g-4">
        {{-- マネージャーモード --}}
        <div class="col-md-6 col-lg-4">
            <div class="card h-100 shadow-sm border-0 hover-shadow" style="transition: transform 0.2s;">
                <div class="card-body text-center p-4">
                    <div class="mb-3">
                        <span style="font-size: 3rem;">👔</span>
                    </div>
                    <h3 class="h5 fw-bold mb-3">マネージャーモード</h3>
                    <p class="text-muted small mb-4">
                        オリジナルチームを作成して<br>
                        NPBチームと対戦しよう
                    </p>
                    <a href="{{ route('manager.game.index') }}" class="btn btn-primary w-100">
                        開始
                    </a>
                </div>
            </div>
        </div>

        {{-- チーム編成 --}}
        <div class="col-md-6 col-lg-4">
            <div class="card h-100 shadow-sm border-0 hover-shadow" style="transition: transform 0.2s;">
                <div class="card-body text-center p-4">
                    <div class="mb-3">
                        <span style="font-size: 3rem;">⚙️</span>
                    </div>
                    <h3 class="h5 fw-bold mb-3">チーム編成</h3>
                    <p class="text-muted small mb-4">
                        オリジナルチームの<br>
                        編成・管理
                    </p>
                    <a href="{{ route('admin.custom-teams.index') }}" class="btn btn-success w-100">
                        開始
                    </a>
                </div>
            </div>
        </div>

        {{-- 試合シミュレーター --}}
        <div class="col-md-6 col-lg-4">
            <div class="card h-100 shadow-sm border-0 hover-shadow" style="transition: transform 0.2s;">
                <div class="card-body text-center p-4">
                    <div class="mb-3">
                        <span style="font-size: 3rem;">🎮</span>
                    </div>
                    <h3 class="h5 fw-bold mb-3">試合シミュレーター</h3>
                    <p class="text-muted small mb-4">
                        NPBチーム同士の<br>
                        試合をシミュレート
                    </p>
                    <a href="{{ route('game.index') }}" class="btn btn-info w-100">
                        開始
                    </a>
                </div>
            </div>
        </div>

        {{-- 試合履歴 --}}
        <div class="col-md-6 col-lg-4">
            <div class="card h-100 shadow-sm border-0 hover-shadow" style="transition: transform 0.2s;">
                <div class="card-body text-center p-4">
                    <div class="mb-3">
                        <span style="font-size: 3rem;">📊</span>
                    </div>
                    <h3 class="h5 fw-bold mb-3">試合履歴</h3>
                    <p class="text-muted small mb-4">
                        過去の試合結果を<br>
                        確認
                    </p>
                    <a href="{{ route('games.index') }}" class="btn btn-secondary w-100">
                        確認
                    </a>
                </div>
            </div>
        </div>

        {{-- マネージャー試合履歴 --}}
        <div class="col-md-6 col-lg-4">
            <div class="card h-100 shadow-sm border-0 hover-shadow" style="transition: transform 0.2s;">
                <div class="card-body text-center p-4">
                    <div class="mb-3">
                        <span style="font-size: 3rem;">📈</span>
                    </div>
                    <h3 class="h5 fw-bold mb-3">マネージャー試合履歴</h3>
                    <p class="text-muted small mb-4">
                        マネージャーモードの<br>
                        試合結果を確認
                    </p>
                    <a href="{{ route('manager.games.index') }}" class="btn btn-warning w-100">
                        確認
                    </a>
                </div>
            </div>
        </div>

        {{-- 管理（管理者向け） --}}
        @if(Auth::user()->isAdmin())
        <div class="col-md-6 col-lg-4">
            <div class="card h-100 shadow-sm border-0 hover-shadow" style="transition: transform 0.2s;">
                <div class="card-body text-center p-4">
                    <div class="mb-3">
                        <span style="font-size: 3rem;">🔧</span>
                    </div>
                    <h3 class="h5 fw-bold mb-3">管理</h3>
                    <p class="text-muted small mb-4">
                        データ管理・設定
                    </p>
                    <div class="btn-group w-100" role="group">
                        <a href="{{ route('teams.index') }}" class="btn btn-outline-dark btn-sm">
                            チーム
                        </a>
                        <a href="{{ route('players.index') }}" class="btn btn-outline-dark btn-sm">
                            選手
                        </a>
                        <a href="{{ route('admin.player-seasons.index') }}" class="btn btn-outline-dark btn-sm">
                            能力
                        </a>
                    </div>
                </div>
            </div>
        </div>
        @endif
    </div>

    <div class="text-center mt-5">
        <form method="POST" action="{{ route('logout') }}" class="d-inline">
            @csrf
            <button type="submit" class="btn btn-outline-secondary">
                ログアウト
            </button>
        </form>
    </div>
</div>

<style>
.hover-shadow:hover {
    transform: translateY(-5px);
    box-shadow: 0 5px 20px rgba(0,0,0,0.15) !important;
}

@media (max-width: 768px) {
    .card {
        margin-bottom: 1rem;
    }
    .btn-group {
        flex-direction: column;
    }
    .btn-group .btn {
        border-radius: 0.375rem !important;
        margin-bottom: 0.25rem;
    }
}
</style>
@endsection

