@extends('layouts.app')

@section('title', 'nf3 投手成績インポート')

@section('content')
    <h1>nf3 投手成績 ペーストインポート</h1>
    <p>
        <a href="https://nf3.sakura.ne.jp/index.html" target="_blank" rel="noopener">
            nf3 サイトトップ（外部サイト）
        </a>
    </p>

    @if(session('status'))
        <div class="alert alert-success mt-3">
            {{ session('status') }}
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger mt-3">
            <ul>
                @foreach($errors->all() as $e)
                    <li>{{ $e }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.nf3.pitching_paste_import') }}">
        @csrf

        <div class="mb-3">
            <label for="year" class="form-label">年度</label>
            <input type="number" name="year" id="year" class="form-control"
                   value="{{ old('year', date('Y')) }}">
        </div>

        <div class="mb-3">
            <label for="team_id" class="form-label">チーム</label>
            <select name="team_id" id="team_id" class="form-select">
                <option value="">選択しない（team_nameに直接入力）</option>
                @foreach($teams as $team)
                    <option value="{{ $team->id }}" @selected(old('team_id') == $team->id)>
                        {{ $team->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="mb-3">
            <label for="team_name" class="form-label">チーム名（任意）</label>
            <input type="text" name="team_name" id="team_name" class="form-control"
                   value="{{ old('team_name') }}">
        </div>

        <div class="mb-3">
            <label for="raw_text" class="form-label">nf3 投手成績テーブル（コピペ）</label>
            <textarea name="raw_text" id="raw_text" rows="20" class="form-control">{{ old('raw_text') }}</textarea>
            <small class="form-text text-muted">
                nf3の「投手成績一覧」ページを開き、表部分をコピーしてここに貼り付けてください。
            </small>
        </div>

        <button type="submit" class="btn btn-primary">インポート実行</button>
    </form>
@endsection


