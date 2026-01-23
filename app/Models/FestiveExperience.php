<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FestiveExperience extends Model
{
    protected $fillable = [
        'artist_id',
        'favourite_set',
        'favourite_day',
        'camp_experience',
        'festive_story',
        'festive_date',
        'status',
        'fest_type',
        'locations',
    ];

    public function artist()
    {
        return $this->belongsTo(Artist::class, 'artist_id');
    }

    public function documents()
    {
        return $this->hasMany(FestiveDocument::class, 'artist_id', 'artist_id');
    }
}
