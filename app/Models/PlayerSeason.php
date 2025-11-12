<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlayerSeason extends Model
{
    use HasFactory;

    protected $fillable = [
        'player_id',
        'team_id',
        'year',
        'league',
        'uniform_number',
        'position_main',
        'overall_rating',
        'batting_contact',
        'batting_power',
        'batting_eye',
        'running_speed',
        'defense',
        'pitcher_stamina',
        'pitcher_control',
        'pitcher_velocity',
        'pitcher_movement',
        'role',
        'is_two_way',
        'nf3_batting_row_id',
        'nf3_pitching_row_id',
    ];

    public function player()
    {
        return $this->belongsTo(Player::class);
    }

    public function team()
    {
        return $this->belongsTo(Team::class);
    }
}
