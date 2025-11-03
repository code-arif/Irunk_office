<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FestiveAlbumImage extends Model
{
    protected $fillable = [
        'festive_album_id',
        'image_or_video_path',
    ];

    // Relation to FestiveAlbum
    public function festiveAlbum()
    {
        return $this->belongsTo(FestiveAlbum::class);
    }
}
