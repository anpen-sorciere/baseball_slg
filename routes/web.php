<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\PlayerController;
use App\Http\Controllers\Admin\CustomTeamController;
use App\Http\Controllers\Admin\Nf3ImportController;
use App\Http\Controllers\GameController;
use App\Http\Controllers\GameHistoryController;
use App\Http\Controllers\Manager\CustomGameController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\HomeController;

// タイトル画面（ログインしていない場合）
Route::get('/', [HomeController::class, 'index'])->name('title');

// 認証関連ルート（ログイン不要）
Route::get('/register', [RegisterController::class, 'showRegistrationForm'])->name('register');
Route::post('/register', [RegisterController::class, 'register']);
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// メインメニュー（ログイン必須）
Route::get('/home', [HomeController::class, 'home'])->middleware('auth')->name('home');

// 試合シミュレーター（ログイン不要）
Route::get('/game', [GameController::class, 'index'])->name('game.index');
Route::post('/game/simulate', [GameController::class, 'simulate'])->name('game.simulate');
Route::get('/games', [GameHistoryController::class, 'index'])->name('games.index');
Route::get('/games/{game}', [GameHistoryController::class, 'show'])->name('games.show');

// マネージャーモード（ログイン必須）
Route::prefix('manager')->middleware('auth')->group(function () {
    Route::get('/game', [CustomGameController::class, 'index'])->name('manager.game.index');
    Route::post('/game', [CustomGameController::class, 'simulate'])->name('manager.game.simulate');
    Route::get('/games', [CustomGameController::class, 'gameHistory'])->name('manager.games.index');
});

Route::prefix('admin')->middleware(['auth', 'admin'])->group(function () {
    Route::get('/nf3/batting', [Nf3ImportController::class, 'index'])
        ->name('admin.nf3.batting_index');
    Route::get('/nf3/batting/{year}/{team?}', [Nf3ImportController::class, 'show'])
        ->whereNumber('year')
        ->name('admin.nf3.batting_show');
    Route::get('/nf3/batting/paste', [Nf3ImportController::class, 'showBattingPasteForm'])
        ->name('admin.nf3.batting_paste_form');
    Route::post('/nf3/batting/paste', [Nf3ImportController::class, 'importBattingPaste'])
        ->name('admin.nf3.batting_paste_import');
    Route::get('/nf3/pitching', [Nf3ImportController::class, 'pitchingIndex'])
        ->name('admin.nf3.pitching_index');
    Route::get('/nf3/pitching/{year}/{team?}', [Nf3ImportController::class, 'pitchingShow'])
        ->whereNumber('year')
        ->name('admin.nf3.pitching_show');
    Route::get('/nf3/pitching/paste', [Nf3ImportController::class, 'showPitchingPasteForm'])
        ->name('admin.nf3.pitching_paste_form');
    Route::post('/nf3/pitching/paste', [Nf3ImportController::class, 'importPitchingPaste'])
        ->name('admin.nf3.pitching_paste_import');

    Route::get('/player-seasons', [\App\Http\Controllers\Admin\PlayerSeasonController::class, 'index'])
        ->name('admin.player-seasons.index');

    // オリジナルチーム管理（ログイン必須、管理者でなくてもOK）
    Route::middleware('auth')->group(function () {
        Route::resource('custom-teams', CustomTeamController::class)
            ->except(['show'])
            ->names([
                'index' => 'admin.custom-teams.index',
                'create' => 'admin.custom-teams.create',
                'store' => 'admin.custom-teams.store',
                'edit' => 'admin.custom-teams.edit',
                'update' => 'admin.custom-teams.update',
                'destroy' => 'admin.custom-teams.destroy',
            ]);

        Route::get('custom-teams/{customTeam}/roster', [CustomTeamController::class, 'editRoster'])
            ->name('admin.custom-teams.roster.edit');
        Route::post('custom-teams/{customTeam}/roster', [CustomTeamController::class, 'updateRoster'])
            ->name('admin.custom-teams.roster.update');
        Route::get('custom-teams/{customTeam}/lineup', [CustomTeamController::class, 'editLineup'])
            ->name('admin.custom-teams.lineup.edit');
        Route::post('custom-teams/{customTeam}/lineup', [CustomTeamController::class, 'updateLineup'])
            ->name('admin.custom-teams.lineup.update');
    });
});

// チーム管理（管理者のみ）
Route::prefix('admin')->middleware(['auth', 'admin'])->group(function () {
    Route::get('/teams', [TeamController::class, 'index'])->name('teams.index');
    Route::post('/teams', [TeamController::class, 'store'])->name('teams.store');
});

// チーム詳細（選手一覧）（ログイン不要）
Route::get('/teams/{team}', [TeamController::class, 'show'])->name('teams.show');

// 選手管理（管理者のみ）
Route::prefix('admin')->middleware(['auth', 'admin'])->group(function () {
    Route::get('/players', [PlayerController::class, 'index'])->name('players.index');
    Route::post('/players', [PlayerController::class, 'store'])->name('players.store');
    Route::get('/players/{player}/edit', [PlayerController::class, 'edit'])->name('players.edit');
    Route::put('/players/{player}', [PlayerController::class, 'update'])->name('players.update');
});
