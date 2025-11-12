<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Player extends Model
{
    use HasFactory;

    protected $fillable = [
        'team_id',
        'name',
        'furigana',
        'handed_bat',
        'handed_throw',
        'position_1',
        'position_2',
        'position_3',
        'born_year',
    ];

    public function seasons()
    {
        return $this->hasMany(PlayerSeason::class);
    }

    public function team()
    {
        return $this->belongsTo(Team::class);
    }
}
