<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FestiveAlbum extends Model
{
    protected $fillable = [
        'user_id',
        'favourite_set',
        'favourite_day',
        'festive_date',
        'camp_experience',
        'unique_moments',
        'dairy_entry',
        'status',
        'fest_type',
    ];

    // Relation to User
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Relation to FestiveAlbumImages
    public function festiveAlbumImages()
    {
        return $this->hasMany(FestiveAlbumImage::class);
    }
}
