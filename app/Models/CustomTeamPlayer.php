<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomTeamPlayer extends Model
{
    use HasFactory;

    protected $table = 'custom_team_players';

    protected $fillable = [
        'custom_team_id',
        'player_season_id',
        'batting_order',
        'position',
        'role',
        'is_pitcher',
        'is_starting_pitcher',
        'pitcher_role',
    ];

    public function customTeam()
    {
        return $this->belongsTo(CustomTeam::class);
    }

    public function playerSeason()
    {
        return $this->belongsTo(PlayerSeason::class);
    }
}
