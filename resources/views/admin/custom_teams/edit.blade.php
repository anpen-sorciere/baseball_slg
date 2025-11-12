@extends('layouts.app')

@section('title', 'オリジナルチーム編集')

@section('content')
    <div class="mb-4">
        <h1 class="h3">オリジナルチーム編集</h1>
        <p class="text-muted mb-0">{{ $customTeam->name }}（ID: {{ $customTeam->id }}）</p>
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
            <form action="{{ route('admin.custom-teams.update', $customTeam) }}" method="POST">
                @method('PUT')
                @include('admin.custom_teams._form', ['customTeam' => $customTeam])
            </form>
        </div>
    </div>
@endsection

