<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AllAlbumResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $averageRating = $this->review->avg('rating');

        // Allowed extensions
        $imageExt = ['jpg', 'jpeg', 'png', 'webp'];
        $videoExt = ['mp4', 'mov', 'avi', 'mkv'];

        $totalImages = 0;
        $totalVideos = 0;

        if ($this->documents) {
            foreach ($this->documents as $doc) {

                // Extract extension from video_image
                $extension = strtolower(pathinfo($doc->video_image, PATHINFO_EXTENSION));

                if (in_array($extension, $imageExt)) {
                    $totalImages++;
                } elseif (in_array($extension, $videoExt)) {
                    $totalVideos++;
                }
            }
        }

        return [
            'id'             => $this->id,
            'type'           => $this->experiences && $this->experiences->first() ? $this->experiences->first()->status : null,
            'festival_name'  => $this->festival ? $this->festival->festival_name : null,
            'total_review'   => $this->review ? $this->review->count() : 0,
            'artist_image'   => url($this->image),
            'average_rating' => $averageRating ? round($averageRating, 2) : null,
            'published_at'   => $this->created_at ? $this->created_at->format('jS F'): null,

            // NEW FIELDS
            'total_images' => $totalImages,
            'total_videos' => $totalVideos,
        ];
    }
}
