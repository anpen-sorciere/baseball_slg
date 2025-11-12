<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Nf3BattingRow extends Model
{
    protected $fillable = [
        'year',
        'team_id',
        'team_name',
        'section',
        'row_index',
        'number',
        'name',
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
        $map = array_flip(config('nf3.batting_columns', []));
        if (!isset($map[$key])) {
            return null;
        }
        $index = $map[$key];
        return $this->columns[$index] ?? null;
    }

    public function getAvgAttribute()
    {
        return $this->getStat('avg');
    }

    public function getObpAttribute()
    {
        return $this->getStat('obp');
    }

    public function getSlgAttribute()
    {
        return $this->getStat('slg');
    }

    public function getOpsAttribute()
    {
        return $this->getStat('ops');
    }

    public function getHrAttribute()
    {
        return $this->getStat('hr');
    }

    public function getRbiAttribute()
    {
        return $this->getStat('rbi');
    }

    public function getStolenBasesAttribute()
    {
        return $this->getStat('stolen_bases');
    }
}


