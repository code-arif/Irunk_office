<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserFriendResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'     => $this->id,
            'name'   => $this->name,
            'avatar' => $this->avatar
                ? url('/' . $this->avatar)
                : asset('default/profile.jpg'),
        ];
    }
}
