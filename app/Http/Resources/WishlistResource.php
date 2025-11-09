<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WishlistResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $experience = $this->artist ? $this->artist->experiences->first() : null;
        return [
            'id'           => $this->id,
            'artist_id'    => $this->artist_id,
            'artist_image' => $this->artist ? url($this->artist->image) : null,
            'is_wishlisted' => true,
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
            ] : null,
            'documents'   => $this->artist ? $this->artist->documents->map(function ($doc) {
                return [
                    'id'       => $doc->id,
                    'file_url' => url($doc->video_image) ?? null,
                ];
            }) : null,
        ];
    }
}
