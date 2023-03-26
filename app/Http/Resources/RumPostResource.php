<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;

class RumPostResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $response = parent::toArray($request);

//         $response[] += [
// //            "users_like" => $this->usersLike ? $this->usersLike : false,
// //            "users_dislike" => $this->usersDislike ? $this->usersDislike : false,
// //            "comments" => $this->comments ? $this->comments : false,
//             "liked" => $this->usersLike->isNotEmpty() ? $this->usersLike->contains(fn($item) => $item->id === auth()->user()->id) : false,
//             "disliked" => $this->usersDislike->isNotEmpty() ? $this->usersDislike->contains(fn($item) => $item->id === auth()->user()->id) : false,
//         ];

        $response['liked'] = $this->usersLike->isNotEmpty() ? $this->usersLike->contains(fn($item) => $item->id === auth()->user()->id) : false;
        $response['disliked'] = $this->usersDislike->isNotEmpty() ? $this->usersDislike->contains(fn($item) => $item->id === auth()->user()->id) : false;

        return $response;
    }
}
