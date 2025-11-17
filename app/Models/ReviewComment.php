<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReviewComment extends Model
{



    protected $fillable = ['review_id', 'user_id', 'parent_id', 'comment'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // public function replies()
    // {
    //     return $this->hasMany(ReviewComment::class, 'parent_id')->with('replies', 'user');;
    // }

    public function replies()
    {
        return $this->hasMany(ReviewComment::class, 'parent_id')
            ->with('replies', 'user', 'likes');
    }


    public function parent()
    {
        return $this->belongsTo(ReviewComment::class, 'parent_id');
    }

    public function likes()
    {
        return $this->hasMany(CommentLike::class, 'comment_id');
    }

    /**
     * Check if the comment is liked by a specific user
     *
     * @param int $userId
     * @return bool
     */
    public function likedByUser($userId): bool
    {
        return $this->likes()->where('user_id', $userId)->exists();
    }
}
