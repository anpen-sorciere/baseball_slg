@extends('layouts.app')

@section('title', 'nf3 打撃データ一覧')

@section('content')
    <div class="d-flex align-items-center justify-content-between mb-4">
        <h1 class="h3">nf3 打撃データ一覧</h1>
        <a href="{{ route('admin.nf3.batting_paste_form') }}" class="btn btn-primary btn-sm">新規インポート</a>
    </div>

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped table-hover mb-0">
                    <thead>
                        <tr>
                            <th>年度</th>
                            <th>チーム</th>
                            <th>件数</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($groups as $group)
                            <tr>
                                <td>{{ $group->year }}</td>
                                <td>{{ $group->team_name ?? 'チーム未指定' }}</td>
                                <td>{{ $group->total_rows }}</td>
                                <td class="text-end">
                                    <a href="{{ route('admin.nf3.batting_show', ['year' => $group->year, 'team' => $group->team_id ?: null]) }}"
                                        class="btn btn-sm btn-outline-secondary">
                                        詳細
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted py-4">保存済みのデータがありません。</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection


