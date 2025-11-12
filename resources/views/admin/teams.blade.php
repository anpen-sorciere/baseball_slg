@extends('layouts.app')

@section('title', 'チーム管理')

@section('content')
    <div class="d-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0">チーム管理</h1>
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

    <div class="card mb-4">
        <div class="card-header">新規チーム追加</div>
        <div class="card-body">
            <form method="POST" action="{{ route('teams.store') }}" class="row g-3">
                @csrf
                <div class="col-md-6">
                    <label class="form-label">チーム名</label>
                    <input type="text" name="name" class="form-control" value="{{ old('name') }}" placeholder="例：中日ドラゴンズ">
                </div>
                <div class="col-md-6">
                    <label class="form-label">略称</label>
                    <input type="text" name="short_name" class="form-control" value="{{ old('short_name') }}" placeholder="例：DRAGONS">
                </div>
                <div class="col-md-6">
                    <label class="form-label">リーグ</label>
                    <select name="league" class="form-select">
                        <option value="セ・リーグ" @selected(old('league') === 'セ・リーグ')>セ・リーグ</option>
                        <option value="パ・リーグ" @selected(old('league') === 'パ・リーグ')>パ・リーグ</option>
                        <option value="その他" @selected(old('league') === 'その他')>その他</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">創設年</label>
                    <input type="number" name="founded_year" class="form-control" value="{{ old('founded_year') }}" placeholder="例：1936">
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">登録</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header">登録済みチーム一覧</div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped table-hover mb-0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>名前</th>
                            <th>略称</th>
                            <th>リーグ</th>
                            <th>創設年</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($teams as $team)
                            <tr>
                                <td>{{ $team->id }}</td>
                                <td>
                                    <a href="{{ route('teams.show', $team) }}">
                                        {{ $team->name }}
                                    </a>
                                </td>
                                <td>{{ $team->short_name }}</td>
                                <td>{{ $team->league }}</td>
                                <td>{{ $team->founded_year }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-muted text-center py-4">登録済みのチームがありません。</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
