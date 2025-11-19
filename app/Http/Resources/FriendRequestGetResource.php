<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FriendRequestGetResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Since tumi incoming request fetch korcho, sender-i hobe "person"
        // Tai receiver check korar dorkar nei
        $person = $this->sender; // direct sender niye nibi

        return [
            'id' => $this->id,
            'person' => [
                'id'       => $person->id,
                'name'     => $person->name,
                // 'username' => $person->username ?? null,
                'avatar'   => $person->avatar
                    ? asset('/' . $person->avatar)
                    : asset('default/profile.jpg'),
            ],
            'status'       => $this->status,
            'sent_at'      => $this->created_at?->diffForHumans(),
            'accepted_at'  => $this->accepted_at?->diffForHumans(),
            'declined_at'  => $this->declined_at?->diffForHumans(),
            'is_sent'      => $this->sender_id === auth('api')->user()?->id, // true hole sent request, false hole incoming
        ];
    }
}
