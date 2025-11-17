<?php

namespace App\Http\Resources;

use App\Helpers\Helper;
use Illuminate\Http\Request;
use App\Models\FestiveAlbumImage;
use Illuminate\Http\Resources\Json\JsonResource;

class MyalbumResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray($request)
    {
        // Get the first experience or null
        $experience = $this->experiences->first();
        $averageRating = $this->review->avg('rating');

        return [
            'id'            => $this->id,
            'festival_name' => $this->festival ? $this->festival->festival_name : null,
            'total_review' => $this->review ? $this->review->count() : 0,
            'artist_image'  => url($this->image),
            'average_rating' => $averageRating ? round($averageRating, 2) : null,
            'published_at' => $this->created_at
                ? $this->created_at->format('jS F')
                : null,

            'experience'    => $experience ? [
                'id'              => $experience->id,
                'favourite_set'   => $experience->favourite_set,
                'favourite_day'   => $experience->favourite_day,
                'camp_experience' => $experience->camp_experience,
                'festive_story'   => $experience->festive_story,
                'festive_date'    => $experience->festive_date,
                'status'          => $experience->status,
                'fest_type'       => $experience->fest_type,
                'details'         => $experience->details,
            ] : null, // null if no experience
            'documents'     => $this->documents->map(function ($doc) {
                return [
                    'id'       => $doc->id,
                    'file_url' => url($doc->video_image) ?? null,
                ];
            }),
            'reviews' => $this->review->map(function ($rev) {
                return [
                    'id'        => $rev->id,
                    'user_name' => $rev->user ? $rev->user->name : 'Anonymous',
                    'avatar'    => $rev->user ? url($rev->user->avatar) : null,
                    'rating'    => $rev->rating,
                    'comment'   => $rev->comment,
                    'like_count' => $rev->likes()->count(),
                    // Add comments if exist
                    'comments'  => $rev->comments->map(function ($comment) {
                        return [
                            'id'         => $comment->id,
                            'user_name'  => $comment->user ? $comment->user->name : 'Anonymous',
                            'avatar'     => $comment->user ? url($comment->user->avatar) : null,
                            'comment'    => $comment->comment,
                            'parent_id'  => $comment->parent_id,
                            'likes_count' => $comment->likes()->count(),
                            'replies'    => $comment->replies->map(function ($reply) {
                                return [
                                    'id'         => $reply->id,
                                    'user_name'  => $reply->user ? $reply->user->name : 'Anonymous',
                                    'avatar'     => $reply->user ? url($reply->user->avatar) : null,
                                    'comment'    => $reply->comment,
                                    'parent_id'  => $reply->parent_id,
                                    'likes_count' => $reply->likes()->count(),
                                ];
                            }),
                        ];
                    }),
                ];
            }),


        ];
    }
}
