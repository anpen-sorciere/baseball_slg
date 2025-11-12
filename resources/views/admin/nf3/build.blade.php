@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <div class="row">
        <div class="col-12">
            <h2 class="mb-4">選手能力値ビルド</h2>

            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if ($errors->any())
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">nf3データから選手能力値をビルド</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.nf3.build.execute') }}" id="buildForm">
                        @csrf

                        <div class="mb-3">
                            <label for="year" class="form-label">年度 <span class="text-danger">*</span></label>
                            <select name="year" id="year" class="form-select" required>
                                @foreach ($years as $y)
                                    <option value="{{ $y }}" {{ old('year', $defaultYear) == $y ? 'selected' : '' }}>
                                        {{ $y }}年
                                    </option>
                                @endforeach
                            </select>
                            <small class="form-text text-muted">
                                ビルドする年度を選択してください。既存のデータは上書き更新されます。
                            </small>
                        </div>

                        <div class="mb-3">
                            <label for="league" class="form-label">リーグ（任意）</label>
                            <select name="league" id="league" class="form-select">
                                <option value="">すべて</option>
                                <option value="セ" {{ old('league') == 'セ' ? 'selected' : '' }}>セ・リーグ</option>
                                <option value="パ" {{ old('league') == 'パ' ? 'selected' : '' }}>パ・リーグ</option>
                                <option value="NPB" {{ old('league') == 'NPB' ? 'selected' : '' }}>NPB全体</option>
                            </select>
                            <small class="form-text text-muted">
                                特定のリーグのみをビルドする場合は選択してください。未選択の場合はすべてのリーグを処理します。
                            </small>
                        </div>

                        <div class="alert alert-info">
                            <strong>注意事項：</strong>
                            <ul class="mb-0 mt-2">
                                <li>この処理は既存の選手能力値データを上書き更新します。</li>
                                <li>処理には数秒から数分かかる場合があります。</li>
                                <li>処理中はページを閉じないでください。</li>
                                <li>ビルド後、<code>is_pitcher</code>フラグが自動的に設定されます。</li>
                            </ul>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary" id="buildBtn">
                                <span class="btn-text">ビルド実行</span>
                                <span class="btn-loading d-none">
                                    <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                                    処理中...
                                </span>
                            </button>
                            <a href="{{ route('admin.nf3.batting_index') }}" class="btn btn-secondary">キャンセル</a>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="mb-0">ビルド履歴・確認</h5>
                </div>
                <div class="card-body">
                    <p class="mb-2">ビルドされたデータの確認：</p>
                    <ul>
                        <li>
                            <a href="{{ route('admin.player-seasons.index') }}">年度別選手能力一覧</a>
                        </li>
                        <li>
                            <a href="{{ route('admin.nf3.batting_index') }}">nf3打撃データ一覧</a>
                        </li>
                        <li>
                            <a href="{{ route('admin.nf3.pitching_index') }}">nf3投球データ一覧</a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('buildForm');
    const buildBtn = document.getElementById('buildBtn');
    const btnText = buildBtn.querySelector('.btn-text');
    const btnLoading = buildBtn.querySelector('.btn-loading');

    if (form) {
        form.addEventListener('submit', function(e) {
            const year = document.getElementById('year').value;
            
            if (year) {
                if (!confirm(`本当に${year}年の選手能力値をビルドしますか？\n既存のデータは上書きされます。`)) {
                    e.preventDefault();
                    return;
                }

                // ボタンの状態変更
                btnText.classList.add('d-none');
                btnLoading.classList.remove('d-none');
                buildBtn.disabled = true;
            }
        });
    }
});
</script>
@endpush
@endsection

