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
        return [
            "id" => $this->id,
            "rum_id" => $this->rum_id,
            "user_id" => $this->user_id,
            "master" => $this->master,
            "approved" => $this->approved,
            "description" => $this->description,
            "visible" => $this->visible,
            "metadata" => $this->metadata,
            "created_at" => $this->created_at,
            "updated_at" => $this->updated_at,
            "likes_count" => $this->likes_count,
            "dislikes_count" => $this->dislikes_count,
            "comments_count" => $this->comments_count,
            "users_like" => $this->usersLike,
            "users_dislike" => $this->usersDislike,
            "comments" => $this->comments,
            "images" => $this->images,
            "liked" => $this->usersLike->isNotEmpty() ? $this->usersLike->contains(fn($item) => $item->id === auth()->user()->id) : false,
            "disliked" => $this->usersDislike->isNotEmpty() ? $this->usersDislike->contains(fn($item) => $item->id === auth()->user()->id) : false,
        ];
    }
}
