<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Nf3PitchingRow extends Model
{
    protected $fillable = [
        'year',
        'team_id',
        'team_name',
        'row_index',
        'number',
        'name',
        'arm',
        'columns',
        'raw_line',
    ];

    protected $casts = [
        'columns' => 'array',
    ];

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public function getStat(string $key): ?string
    {
        $map = array_flip(config('nf3.pitching_columns', []));
        if (!isset($map[$key])) {
            return null;
        }
        $index = $map[$key];
        return $this->columns[$index] ?? null;
    }

    public function getEraAttribute()
    {
        return $this->getStat('era');
    }

    public function getInningsAttribute()
    {
        return $this->getStat('innings');
    }

    public function getStrikeoutsAttribute()
    {
        return $this->getStat('strikeouts');
    }

    public function getWalksAttribute()
    {
        return $this->getStat('walks');
    }

    public function getWinsAttribute()
    {
        return $this->getStat('wins');
    }

    public function getLossesAttribute()
    {
        return $this->getStat('losses');
    }
}


