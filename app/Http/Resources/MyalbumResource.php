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

        return [
            'id'            => $this->id,
            'festival_name' => $this->festival ? $this->festival->festival_name : null,
            'artist_image'  => url($this->image),
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
            'documents'     => $this->documents->map(function($doc) {
                return [
                    'id'       => $doc->id,
                    'file_url' => url($doc->video_image) ?? null,
                ];
            }),
        ];
    }

   
}
