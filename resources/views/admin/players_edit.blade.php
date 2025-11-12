@extends('layouts.app')

@section('title', '選手編集')

@section('content')
    <div class="d-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0">選手編集</h1>
        <a href="{{ route('players.index') }}" class="btn btn-outline-secondary">一覧に戻る</a>
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

    <div class="card">
        <div class="card-header">選手情報編集</div>
        <div class="card-body">
            <form method="POST" action="{{ route('players.update', $player) }}" class="row g-3">
                @csrf
                @method('PUT')
                <input type="hidden" name="team_id" value="{{ request('team_id') }}">
                
                <div class="col-md-6">
                    <label class="form-label">名前 <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" value="{{ old('name', $player->name) }}" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">ふりがな</label>
                    <input type="text" name="furigana" class="form-control" value="{{ old('furigana', $player->furigana) }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">打席</label>
                    <select name="handed_bat" class="form-select">
                        <option value="">未設定</option>
                        <option value="右" @selected(old('handed_bat', $player->handed_bat) === '右')>右</option>
                        <option value="左" @selected(old('handed_bat', $player->handed_bat) === '左')>左</option>
                        <option value="両" @selected(old('handed_bat', $player->handed_bat) === '両')>両打ち</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">投げ手</label>
                    <select name="handed_throw" class="form-select">
                        <option value="">未設定</option>
                        <option value="右" @selected(old('handed_throw', $player->handed_throw) === '右')>右</option>
                        <option value="左" @selected(old('handed_throw', $player->handed_throw) === '左')>左</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">生年</label>
                    <input type="number" name="born_year" class="form-control" value="{{ old('born_year', $player->born_year) }}" placeholder="例：1994">
                </div>
                <div class="col-md-4">
                    <label class="form-label">守備位置1 <span class="text-danger">*</span></label>
                    <select name="position_1" class="form-select" required>
                        <option value="">選択してください</option>
                        <option value="投手" @selected(old('position_1', $player->position_1) === '投手')>投手</option>
                        <option value="捕手" @selected(old('position_1', $player->position_1) === '捕手')>捕手</option>
                        <option value="一塁手" @selected(old('position_1', $player->position_1) === '一塁手')>一塁手</option>
                        <option value="二塁手" @selected(old('position_1', $player->position_1) === '二塁手')>二塁手</option>
                        <option value="三塁手" @selected(old('position_1', $player->position_1) === '三塁手')>三塁手</option>
                        <option value="遊撃手" @selected(old('position_1', $player->position_1) === '遊撃手')>遊撃手</option>
                        <option value="左翼手" @selected(old('position_1', $player->position_1) === '左翼手')>左翼手</option>
                        <option value="中堅手" @selected(old('position_1', $player->position_1) === '中堅手')>中堅手</option>
                        <option value="右翼手" @selected(old('position_1', $player->position_1) === '右翼手')>右翼手</option>
                        <option value="内野手" @selected(old('position_1', $player->position_1) === '内野手')>内野手</option>
                        <option value="外野手" @selected(old('position_1', $player->position_1) === '外野手')>外野手</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">守備位置2</label>
                    <select name="position_2" class="form-select">
                        <option value="">未設定</option>
                        <option value="投手" @selected(old('position_2', $player->position_2) === '投手')>投手</option>
                        <option value="捕手" @selected(old('position_2', $player->position_2) === '捕手')>捕手</option>
                        <option value="一塁手" @selected(old('position_2', $player->position_2) === '一塁手')>一塁手</option>
                        <option value="二塁手" @selected(old('position_2', $player->position_2) === '二塁手')>二塁手</option>
                        <option value="三塁手" @selected(old('position_2', $player->position_2) === '三塁手')>三塁手</option>
                        <option value="遊撃手" @selected(old('position_2', $player->position_2) === '遊撃手')>遊撃手</option>
                        <option value="左翼手" @selected(old('position_2', $player->position_2) === '左翼手')>左翼手</option>
                        <option value="中堅手" @selected(old('position_2', $player->position_2) === '中堅手')>中堅手</option>
                        <option value="右翼手" @selected(old('position_2', $player->position_2) === '右翼手')>右翼手</option>
                        <option value="内野手" @selected(old('position_2', $player->position_2) === '内野手')>内野手</option>
                        <option value="外野手" @selected(old('position_2', $player->position_2) === '外野手')>外野手</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">守備位置3</label>
                    <select name="position_3" class="form-select">
                        <option value="">未設定</option>
                        <option value="投手" @selected(old('position_3', $player->position_3) === '投手')>投手</option>
                        <option value="捕手" @selected(old('position_3', $player->position_3) === '捕手')>捕手</option>
                        <option value="一塁手" @selected(old('position_3', $player->position_3) === '一塁手')>一塁手</option>
                        <option value="二塁手" @selected(old('position_3', $player->position_3) === '二塁手')>二塁手</option>
                        <option value="三塁手" @selected(old('position_3', $player->position_3) === '三塁手')>三塁手</option>
                        <option value="遊撃手" @selected(old('position_3', $player->position_3) === '遊撃手')>遊撃手</option>
                        <option value="左翼手" @selected(old('position_3', $player->position_3) === '左翼手')>左翼手</option>
                        <option value="中堅手" @selected(old('position_3', $player->position_3) === '中堅手')>中堅手</option>
                        <option value="右翼手" @selected(old('position_3', $player->position_3) === '右翼手')>右翼手</option>
                        <option value="内野手" @selected(old('position_3', $player->position_3) === '内野手')>内野手</option>
                        <option value="外野手" @selected(old('position_3', $player->position_3) === '外野手')>外野手</option>
                    </select>
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">更新</button>
                    <a href="{{ route('players.index') }}" class="btn btn-outline-secondary">キャンセル</a>
                </div>
            </form>
        </div>
    </div>
@endsection

