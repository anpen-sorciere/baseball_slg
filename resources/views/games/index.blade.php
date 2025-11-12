@extends('layouts.app')

@section('title', '試合一覧')

@section('content')
    <div class="d-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0">試合一覧</h1>
    </div>

    <div class="card">
        <div class="card-body p-0">
            <table class="table table-striped mb-0">
                <thead>
                    <tr>
                        <th>日時</th>
                        <th>年度</th>
                        <th>カード</th>
                        <th>スコア</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($games as $game)
                        <tr>
                            <td>{{ $game->created_at->format('Y-m-d H:i') }}</td>
                            <td>{{ $game->year }}</td>
                            <td>{{ optional($game->teamA)->name }} vs {{ optional($game->teamB)->name }}</td>
                            <td>{{ $game->score_a }} - {{ $game->score_b }}</td>
                            <td>
                                <a href="{{ route('games.show', $game) }}" class="btn btn-sm btn-outline-primary">
                                    詳細
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-4">試合結果はまだありません。</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection


