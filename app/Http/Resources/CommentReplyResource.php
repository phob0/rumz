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
        $response = parent::toArray($request);

        $response[] = [
            "liked" => $this->likes->isNotEmpty() ? $this->likes->contains(fn($item) => $item->user_id === auth()->user()->id) : false,
            "disliked" => $this->dislikes->isNotEmpty() ? $this->dislikes->contains(fn($item) => $item->user_id === auth()->user()->id) : false,
        ];

        return $response;
    }
}
