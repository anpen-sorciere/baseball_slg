@extends('layouts.app')

@section('title', 'オリジナルチーム作成')

@section('content')
    <div class="mb-4">
        <h1 class="h3">オリジナルチーム作成</h1>
    </div>

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
        <div class="card-body">
            <form action="{{ route('admin.custom-teams.store') }}" method="POST">
                @include('admin.custom_teams._form', ['customTeam' => null, 'teams' => $teams])
            </form>
        </div>
    </div>
@endsection

