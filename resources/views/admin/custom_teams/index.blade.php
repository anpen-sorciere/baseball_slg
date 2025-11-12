@extends('layouts.app')

@php use Illuminate\Support\Str; @endphp

@section('title', 'オリジナルチーム管理')

@section('content')
    <div class="d-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0">オリジナルチーム</h1>
        <a href="{{ route('admin.custom-teams.create') }}" class="btn btn-primary">新規作成</a>
    </div>

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <div class="card">
        <div class="card-body p-0">
            <table class="table table-striped mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>チーム名</th>
                        <th>年度</th>
                        <th>備考</th>
                        <th class="text-end">操作</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($teams as $team)
                        <tr>
                            <td>{{ $team->id }}</td>
                            <td>{{ $team->name }}</td>
                            <td>{{ $team->year }}</td>
                            <td>{{ Str::limit($team->notes, 40) }}</td>
                            <td class="text-end">
                                <a href="{{ route('admin.custom-teams.roster.edit', $team) }}" class="btn btn-sm btn-outline-success">
                                    ベンチ登録
                                </a>
                                <a href="{{ route('admin.custom-teams.lineup.edit', $team) }}" class="btn btn-sm btn-outline-secondary ms-1">
                                    チーム編成
                                </a>
                                <a href="{{ route('admin.custom-teams.edit', $team) }}" class="btn btn-sm btn-outline-primary">
                                    編集
                                </a>
                                <form action="{{ route('admin.custom-teams.destroy', $team) }}" method="POST" class="d-inline"
                                      onsubmit="return confirm('削除するとチーム編成の設定も失われます。削除してよろしいですか？');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">削除</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-4">オリジナルチームはまだ登録されていません。</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">
            {{ $teams->links() }}
        </div>
    </div>
@endsection

