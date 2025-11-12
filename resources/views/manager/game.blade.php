@extends('layouts.app')

@section('title', 'マネージャーモード：試合設定')

@section('content')
    <div class="mb-4">
        <h1 class="h3">マネージャーモード - 試合設定</h1>
        <p class="text-muted mb-0">
            オリジナルチームを選んで、NPBチームまたは他のオリジナルチームと対戦させることができます。
        </p>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if ($errors->custom_team ?? false)
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->custom_team->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if ($errors->opponent_custom_team ?? false)
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->opponent_custom_team->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card">
        <div class="card-body">
            <form action="{{ route('manager.game.simulate') }}" method="POST" class="row g-4" id="gameForm">
                @csrf
                <div class="col-md-6">
                    <label for="custom_team_id" class="form-label">先攻チーム（オリジナルチーム）</label>
                    <select name="custom_team_id" id="custom_team_id" class="form-select" required>
                        <option value="">選択してください</option>
                        @foreach ($customTeams as $team)
                            <option value="{{ $team->id }}" @selected(old('custom_team_id') == $team->id)>
                                {{ $team->name }}（{{ $team->year }}年）
                            </option>
                        @endforeach
                    </select>
                    <div class="form-text">
                        先にチーム編成が完了していることをご確認ください。
                    </div>
                </div>

                <div class="col-md-6">
                    <label for="opponent_type" class="form-label">後攻チームの種類</label>
                    <select name="opponent_type" id="opponent_type" class="form-select" required>
                        <option value="npb" @selected(old('opponent_type', 'npb') === 'npb')>NPBチーム</option>
                        <option value="custom" @selected(old('opponent_type') === 'custom')>オリジナルチーム</option>
                    </select>
                </div>

                <div class="col-md-6" id="opponent_npb_container">
                    <label for="opponent_team_id" class="form-label">後攻チーム（NPBチーム）</label>
                    <select name="opponent_team_id" id="opponent_team_id" class="form-select">
                        <option value="">選択してください</option>
                        @foreach ($teams as $team)
                            <option value="{{ $team->id }}" @selected(old('opponent_team_id') == $team->id)>
                                {{ $team->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-6 d-none" id="opponent_custom_container">
                    <label for="opponent_custom_team_id" class="form-label">後攻チーム（オリジナルチーム）</label>
                    <select name="opponent_custom_team_id" id="opponent_custom_team_id" class="form-select">
                        <option value="">選択してください</option>
                        @foreach ($opponentCustomTeams as $team)
                            <option value="{{ $team->id }}" @selected(old('opponent_custom_team_id') == $team->id)>
                                {{ $team->name }}（{{ $team->year }}年）@if($team->user) - {{ $team->user->name }}@endif
                            </option>
                        @endforeach
                    </select>
                    <div class="form-text">
                        他のユーザーのオリジナルチームとも対戦できます。
                    </div>
                </div>

                <div class="col-12">
                    <button type="submit" class="btn btn-primary btn-lg btn-start-game" id="startGameBtn">
                        <span class="btn-text">⚾ 試合開始</span>
                        <span class="btn-loading d-none">
                            <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                            試合進行中...
                        </span>
                    </button>
                    <a href="{{ route('admin.custom-teams.index') }}" class="btn btn-outline-secondary ms-2">
                        オリジナルチーム一覧
                    </a>
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
            const customTeamId = document.getElementById('custom_team_id').value;
            const opponentType = document.getElementById('opponent_type').value;
            const opponentId = opponentType === 'npb' 
                ? document.getElementById('opponent_team_id').value 
                : document.getElementById('opponent_custom_team_id').value;

            if (customTeamId && opponentId && customTeamId !== opponentId) {
                btnText.classList.add('d-none');
                btnLoading.classList.remove('d-none');
                startBtn.disabled = true;
                loadingOverlay.classList.remove('d-none');
            }
        });
    }

    const opponentTypeSelect = document.getElementById('opponent_type');
    const npbContainer = document.getElementById('opponent_npb_container');
    const customContainer = document.getElementById('opponent_custom_container');
    const npbSelect = document.getElementById('opponent_team_id');
    const customSelect = document.getElementById('opponent_custom_team_id');

    const updateOpponentDisplay = () => {
        const type = opponentTypeSelect.value;
        if (type === 'npb') {
            npbContainer.classList.remove('d-none');
            customContainer.classList.add('d-none');
            npbSelect.setAttribute('required', 'required');
            customSelect.removeAttribute('required');
            customSelect.value = '';
        } else {
            npbContainer.classList.add('d-none');
            customContainer.classList.remove('d-none');
            npbSelect.removeAttribute('required');
            npbSelect.value = '';
            customSelect.setAttribute('required', 'required');
        }
    };

    opponentTypeSelect.addEventListener('change', updateOpponentDisplay);
    updateOpponentDisplay();
});
</script>
@endpush

