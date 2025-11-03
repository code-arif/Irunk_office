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
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'user_id'         => $this->user_id,
            'name'       => $this->user ? $this->user->name : null,
            'username'   => $this->user ? $this->user->username : null,
            'avatar'     => $this->user && $this->user->avatar ? asset($this->user->avatar) : asset('default/profile.jpg'),
            'favourite_set'   => $this->favourite_set,
            'favourite_day'   => $this->favourite_day,
            'festive_date'    => $this->festive_date,
            'camp_experience' => $this->camp_experience,
            'unique_moments'  => $this->unique_moments,
            'dairy_entry'     => $this->dairy_entry,
            'status'          => $this->status,
            'fest_type'       => $this->fest_type,

            // ✅ Include related images/videos
            'images' => $this->festiveAlbumImages ? $this->festiveAlbumImages->map(function ($image) {
                return [
                    'id'       => $image->id,
                    'full_url' => asset($image->image_or_video_path),
                ];
            }) : [],

        ];
    }

   
}
