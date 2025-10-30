<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FriendRequestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $person = $this->receiver ?? $this->sender;

        return [
            'id' => $this->id,
            'person' => [
                'id'         => $person->id ?? null,
                'name'  => $person->name ?? null,
                // 'username'   => $person->username ?? null,
                'avatar' => $this->avatar ? asset($this->avatar) : asset('default/profile.jpg'),
            ],
            'status'       => $this->status,
            'sent_at'      => $this->created_at?->diffForHumans(),
            'accepted_at'  => $this->accepted_at?->diffForHumans(),
            'declined_at'  => $this->declined_at?->diffForHumans(),
            'is_sent'      => $this->sender_id === auth('api')->id(),
        ];
    }
}
