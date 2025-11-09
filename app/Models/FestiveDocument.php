<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FestiveDocument extends Model
{
    protected $fillable = [
        'artist_id',
        'video_image',
    ];

    public function artist()
    {
        return $this->belongsTo(Artist::class);
    }
}
