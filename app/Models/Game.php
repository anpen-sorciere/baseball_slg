<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Game extends Model
{
    use HasFactory;

    protected $fillable = [
        'year',
        'team_a_id',
        'team_b_id',
        'score_a',
        'score_b',
        'result_json',
        'custom_team_id',
    ];

    protected $casts = [
        'result_json' => 'array',
    ];

    public function teamA()
    {
        return $this->belongsTo(Team::class, 'team_a_id');
    }

    public function teamB()
    {
        return $this->belongsTo(Team::class, 'team_b_id');
    }

    public function customTeam()
    {
        return $this->belongsTo(CustomTeam::class);
    }
}

