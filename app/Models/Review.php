<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Review extends Model
{


    protected $fillable = ['user_id', 'artist_id', 'rating', 'comment'];

    protected $hidden = ['created_at', 'updated_at'];


    public function user()
    {
        return $this->belongsTo(User::class);
    }


    public function comments()
    {
        return $this->hasMany(ReviewComment::class)->whereNull('parent_id');
    }


    public function likes()
    {
        return $this->hasMany(ReviewLike::class, 'review_id');
    }

    public function likedByUser($userId)
    {
        return $this->likes()->where('user_id', $userId)->exists();
    }
}
