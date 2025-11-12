<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomTeam extends Model
{
    use HasFactory;

    protected $table = 'custom_teams';

    protected $fillable = [
        'user_id',
        'name',
        'short_name',
        'type',
        'primary_color',
        'secondary_color',
        'emblem_image_path',
        'year',
        'notes',
    ];

    public function players()
    {
        return $this->hasMany(CustomTeamPlayer::class);
    }

    public function strategy()
    {
        return $this->hasOne(CustomTeamStrategy::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
