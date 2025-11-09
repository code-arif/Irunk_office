<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Artist extends Model
{
    protected $fillable = [
        'user_id',
        'festival_id',
        'name',
        'image',
    ];

    public function experiences()
    {
        return $this->hasMany(FestiveExperience::class);
    }

    public function documents()
    {
        return $this->hasMany(FestiveDocument::class);
    }

    public function festival()
    {
        return $this->belongsTo(Festival::class);
    }   

    public function review()
    {
        return $this->hasMany(Review::class);
    }
}
