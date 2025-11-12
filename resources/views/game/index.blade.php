@extends('layouts.app')

@section('title', '試合シミュレーター')

@section('content')
    <div class="d-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0">試合シミュレーター</h1>
    </div>

    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ route('game.simulate') }}" class="row g-3" id="gameForm">
                @csrf
                <div class="col-md-3">
                    <label for="year" class="form-label">年度</label>
                    <select name="year" id="year" class="form-select" required>
                        @foreach($years as $year)
                            <option value="{{ $year }}" @selected(old('year', $defaultYear) == $year)>
                                {{ $year }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="team_a_id" class="form-label">先攻</label>
                    <select name="team_a_id" id="team_a_id" class="form-select" required>
                        <option value="">選択してください</option>
                        @foreach($teams as $team)
                            <option value="{{ $team->id }}" @selected(old('team_a_id', $selectedTeamA) == $team->id)>
                                {{ $team->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="team_b_id" class="form-label">後攻</label>
                    <select name="team_b_id" id="team_b_id" class="form-select" required>
                        <option value="">選択してください</option>
                        @foreach($teams as $team)
                            <option value="{{ $team->id }}" @selected(old('team_b_id', $selectedTeamB) == $team->id)>
                                {{ $team->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-primary btn-lg btn-start-game" id="startGameBtn">
                        <span class="btn-text">⚾ 試合開始</span>
                        <span class="btn-loading d-none">
                            <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                            試合進行中...
                        </span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ローディングオーバーレイ -->
    <div class="loading-overlay d-none" id="loadingOverlay">
        <div class="loading-content">
            <div class="baseball-loader">⚾</div>
            <h3 class="loading-title">試合進行中...</h3>
            <p class="loading-message">シミュレーションを実行しています</p>
            <div class="loading-progress">
                <div class="loading-bar"></div>
            </div>
        </div>
    </div>
@endsection

@push('styles')
<style>
.btn-start-game {
    min-width: 200px;
    font-size: 1.1rem;
    padding: 0.75rem 2rem;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.btn-start-game:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(0, 123, 255, 0.4);
}

.btn-start-game:active {
    transform: translateY(0);
}

.btn-loading {
    display: flex;
    align-items: center;
    justify-content: center;
}

/* ローディングオーバーレイ */
.loading-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.85);
    z-index: 9999;
    display: flex;
    align-items: center;
    justify-content: center;
    backdrop-filter: blur(5px);
}

.loading-content {
    text-align: center;
    color: white;
}

.baseball-loader {
    font-size: 4rem;
    animation: baseballSpin 1s linear infinite;
    margin-bottom: 1rem;
}

@keyframes baseballSpin {
    0% {
        transform: rotate(0deg) scale(1);
    }
    50% {
        transform: rotate(180deg) scale(1.2);
    }
    100% {
        transform: rotate(360deg) scale(1);
    }
}

.loading-title {
    font-size: 2rem;
    font-weight: bold;
    margin-bottom: 0.5rem;
    animation: pulse 1.5s ease-in-out infinite;
}

@keyframes pulse {
    0%, 100% {
        opacity: 1;
    }
    50% {
        opacity: 0.7;
    }
}

.loading-message {
    font-size: 1.1rem;
    color: rgba(255, 255, 255, 0.8);
    margin-bottom: 2rem;
}

.loading-progress {
    width: 300px;
    height: 4px;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 2px;
    overflow: hidden;
    margin: 0 auto;
}

.loading-bar {
    height: 100%;
    background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
    width: 0%;
    animation: loadingProgress 2s ease-in-out infinite;
}

@keyframes loadingProgress {
    0% {
        width: 0%;
        transform: translateX(0);
    }
    50% {
        width: 70%;
        transform: translateX(0);
    }
    100% {
        width: 100%;
        transform: translateX(0);
    }
}
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('gameForm');
    const startBtn = document.getElementById('startGameBtn');
    const loadingOverlay = document.getElementById('loadingOverlay');
    const btnText = startBtn.querySelector('.btn-text');
    const btnLoading = startBtn.querySelector('.btn-loading');

    if (form) {
        form.addEventListener('submit', function(e) {
            // バリデーションエラーがない場合のみローディング表示
            const teamA = document.getElementById('team_a_id').value;
            const teamB = document.getElementById('team_b_id').value;
            const year = document.getElementById('year').value;

            if (teamA && teamB && year && teamA !== teamB) {
                // ボタンの状態変更
                btnText.classList.add('d-none');
                btnLoading.classList.remove('d-none');
                startBtn.disabled = true;

                // ローディングオーバーレイ表示
                loadingOverlay.classList.remove('d-none');
            }
        });
    }
});
</script>
@endpush


