@csrf
<div class="mb-3">
    <label for="name" class="form-label">チーム名</label>
    <input type="text" name="name" id="name" class="form-control"
           value="{{ old('name', optional($customTeam)->name) }}" required>
</div>

@isset($teams)
    <div class="mb-3">
        <label for="base_team_id" class="form-label">ベースにする球団</label>
        <select name="base_team_id" id="base_team_id" class="form-select" required>
            <option value="">--- 選択してください ---</option>
            @foreach ($teams as $team)
                <option value="{{ $team->id }}" @selected(old('base_team_id') == $team->id)>
                    {{ $team->name }}
                </option>
            @endforeach
        </select>
        <small class="text-muted">最新年度の選手データをもとにベンチ枠を自動作成します。</small>
    </div>
@endisset

<button type="submit" class="btn btn-primary">保存</button>
<a href="{{ route('admin.custom-teams.index') }}" class="btn btn-outline-secondary ms-2">一覧へ戻る</a>

