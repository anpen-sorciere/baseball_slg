<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomTeamStrategy extends Model
{
    use HasFactory;

    protected $table = 'custom_team_strategies';

    protected $fillable = [
        'custom_team_id',
        'offense_style',
        'pitching_style',
        'defense_style',
    ];

    public function customTeam()
    {
        return $this->belongsTo(CustomTeam::class);
    }
}
