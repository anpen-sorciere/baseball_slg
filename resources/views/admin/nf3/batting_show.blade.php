@extends('layouts.app')

@section('title', $year . '年 ' . ($teamName ?? 'チーム未指定') . ' nf3打撃データ')

@section('content')
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h1 class="h3 mb-0">{{ $year }}年 {{ $teamName ?? 'チーム未指定' }} nf3打撃データ</h1>
            @if($team)
                <p class="text-muted mb-0">チームID: {{ $team->id }} / 略称: {{ $team->short_name }}</p>
            @endif
        </div>
        <div>
            <a href="{{ route('admin.nf3.batting_index') }}" class="btn btn-outline-secondary btn-sm">一覧へ戻る</a>
        </div>
    </div>

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped table-hover mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>区分</th>
                            <th>背番号</th>
                            <th>名前</th>
                            <th>列データ</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rows as $row)
                            <tr>
                                <td>{{ $row->row_index }}</td>
                                <td>
                                    <span class="badge bg-{{ $row->section === 'pitchers' ? 'info' : 'secondary' }}">
                                        {{ $row->section === 'pitchers' ? '投手' : '打者' }}
                                    </span>
                                </td>
                                <td>{{ $row->number }}</td>
                                <td>{{ $row->name }}</td>
                                <td>
                                    <pre class="mb-0">{{ json_encode($row->columns, JSON_UNESCAPED_UNICODE) }}</pre>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">該当データがありません。</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection


