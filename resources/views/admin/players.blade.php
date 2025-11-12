@extends('layouts.app')

@section('title', '選手管理')

@section('content')
    <div class="d-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0">選手管理</h1>
    </div>

      @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
      @endif

      @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
            @foreach ($errors->all() as $error)
              <li>{{ $error }}</li>
            @endforeach
          </ul>
        </div>
      @endif

    <form method="GET" action="{{ route('players.index') }}" class="card mb-4">
        <div class="card-header">絞り込み</div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="team_id" class="form-label">所属チーム</label>
                    <select name="team_id" id="team_id" class="form-select">
                        <option value="">すべてのチーム</option>
                        @foreach($teams as $team)
                            <option value="{{ $team->id }}" @selected($teamId == $team->id)>{{ $team->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-primary">絞り込む</button>
                    <a href="{{ route('players.index') }}" class="btn btn-outline-secondary">リセット</a>
                </div>
            </div>
        </div>
    </form>

    <div class="card mb-4">
        <div class="card-header">新規選手追加</div>
        <div class="card-body">
            <form method="POST" action="{{ route('players.store') }}" class="row g-3">
                @csrf
                <input type="hidden" name="team_id" value="{{ $teamId }}">
                <div class="col-md-6">
                    <label class="form-label">名前 <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" value="{{ old('name') }}" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">ふりがな</label>
                    <input type="text" name="furigana" class="form-control" value="{{ old('furigana') }}">
        </div>
                <div class="col-md-4">
                    <label class="form-label">打席</label>
                    <select name="handed_bat" class="form-select">
            <option value="">未設定</option>
            <option value="右" @selected(old('handed_bat') === '右')>右</option>
            <option value="左" @selected(old('handed_bat') === '左')>左</option>
            <option value="両" @selected(old('handed_bat') === '両')>両打ち</option>
          </select>
        </div>
                <div class="col-md-4">
                    <label class="form-label">投げ手</label>
                    <select name="handed_throw" class="form-select">
            <option value="">未設定</option>
            <option value="右" @selected(old('handed_throw') === '右')>右</option>
            <option value="左" @selected(old('handed_throw') === '左')>左</option>
          </select>
        </div>
                <div class="col-md-4">
                    <label class="form-label">守備位置1 <span class="text-danger">*</span></label>
                    <select name="position_1" class="form-select" required>
                        <option value="">選択してください</option>
                        <option value="投手" @selected(old('position_1') === '投手')>投手</option>
                        <option value="捕手" @selected(old('position_1') === '捕手')>捕手</option>
                        <option value="一塁手" @selected(old('position_1') === '一塁手')>一塁手</option>
                        <option value="二塁手" @selected(old('position_1') === '二塁手')>二塁手</option>
                        <option value="三塁手" @selected(old('position_1') === '三塁手')>三塁手</option>
                        <option value="遊撃手" @selected(old('position_1') === '遊撃手')>遊撃手</option>
                        <option value="左翼手" @selected(old('position_1') === '左翼手')>左翼手</option>
                        <option value="中堅手" @selected(old('position_1') === '中堅手')>中堅手</option>
                        <option value="右翼手" @selected(old('position_1') === '右翼手')>右翼手</option>
                        <option value="内野手" @selected(old('position_1') === '内野手')>内野手</option>
                        <option value="外野手" @selected(old('position_1') === '外野手')>外野手</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">守備位置2</label>
                    <select name="position_2" class="form-select">
                        <option value="">未設定</option>
                        <option value="投手" @selected(old('position_2') === '投手')>投手</option>
                        <option value="捕手" @selected(old('position_2') === '捕手')>捕手</option>
                        <option value="一塁手" @selected(old('position_2') === '一塁手')>一塁手</option>
                        <option value="二塁手" @selected(old('position_2') === '二塁手')>二塁手</option>
                        <option value="三塁手" @selected(old('position_2') === '三塁手')>三塁手</option>
                        <option value="遊撃手" @selected(old('position_2') === '遊撃手')>遊撃手</option>
                        <option value="左翼手" @selected(old('position_2') === '左翼手')>左翼手</option>
                        <option value="中堅手" @selected(old('position_2') === '中堅手')>中堅手</option>
                        <option value="右翼手" @selected(old('position_2') === '右翼手')>右翼手</option>
                        <option value="内野手" @selected(old('position_2') === '内野手')>内野手</option>
                        <option value="外野手" @selected(old('position_2') === '外野手')>外野手</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">守備位置3</label>
                    <select name="position_3" class="form-select">
                        <option value="">未設定</option>
                        <option value="投手" @selected(old('position_3') === '投手')>投手</option>
                        <option value="捕手" @selected(old('position_3') === '捕手')>捕手</option>
                        <option value="一塁手" @selected(old('position_3') === '一塁手')>一塁手</option>
                        <option value="二塁手" @selected(old('position_3') === '二塁手')>二塁手</option>
                        <option value="三塁手" @selected(old('position_3') === '三塁手')>三塁手</option>
                        <option value="遊撃手" @selected(old('position_3') === '遊撃手')>遊撃手</option>
                        <option value="左翼手" @selected(old('position_3') === '左翼手')>左翼手</option>
                        <option value="中堅手" @selected(old('position_3') === '中堅手')>中堅手</option>
                        <option value="右翼手" @selected(old('position_3') === '右翼手')>右翼手</option>
                        <option value="内野手" @selected(old('position_3') === '内野手')>内野手</option>
                        <option value="外野手" @selected(old('position_3') === '外野手')>外野手</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">生年</label>
                    <input type="number" name="born_year" class="form-control" value="{{ old('born_year') }}" placeholder="例：1994">
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">登録</button>
        </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header">登録済み選手一覧</div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped table-hover mb-0">
                    <thead>
        <tr>
          <th>ID</th>
          <th>名前</th>
          <th>ふりがな</th>
          <th>打席</th>
          <th>投げ手</th>
          <th>守備位置</th>
          <th>生年</th>
          <th>最新所属</th>
          <th>操作</th>
        </tr>
                    </thead>
                    <tbody>
                        @forelse($players as $player)
          <tr>
            <td>{{ $player->id }}</td>
            <td>{{ $player->name }}</td>
            <td>{{ $player->furigana }}</td>
            <td>{{ $player->handed_bat }}</td>
            <td>{{ $player->handed_throw }}</td>
            <td>
                @php
                    $positions = array_filter([$player->position_1, $player->position_2, $player->position_3]);
                @endphp
                @if(!empty($positions))
                    {{ implode(' / ', $positions) }}
                @else
                    <span class="text-muted">-</span>
                @endif
            </td>
            <td>{{ $player->born_year }}</td>
            <td>
                @php
                    $latestSeason = $player->seasons->first();
                @endphp
                @if($latestSeason)
                    <div>{{ $latestSeason->team?->name ?? 'チーム不明' }}</div>
                    <small class="text-muted">{{ $latestSeason->year }}年 / {{ $latestSeason->league ?? 'リーグ不明' }}</small>
                @else
                    <span class="text-muted">未登録</span>
                @endif
            </td>
            <td>
                <a href="{{ route('players.edit', ['player' => $player, 'team_id' => $teamId]) }}" class="btn btn-sm btn-outline-primary">編集</a>
            </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-muted text-center py-4">登録済みの選手がありません。</td>
          </tr>
                        @endforelse
                    </tbody>
      </table>
    </div>
  </div>
    </div>
@endsection
