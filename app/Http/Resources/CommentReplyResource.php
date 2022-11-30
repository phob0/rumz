<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CommentReplyResource extends JsonResource
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
            'id' => $this->id,
            'user' => $this->user,
            'comment' => $this->comment,
            'parent' => $this->parent,
            'likes' => $this->likes,
            'dislikes' => $this->dislikes,
            'likes_count' => $this->likes_count,
            'dislikes_count' => $this->dislikes_count,
            "liked" => $this->likes->isNotEmpty() ? $this->likes->contains(fn($item) => $item->id === auth()->user()->id) : false,
            "disliked" => $this->dislikes->isNotEmpty() ? $this->dislikes->contains(fn($item) => $item->id === auth()->user()->id) : false,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
