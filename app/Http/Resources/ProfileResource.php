<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Resources\Json\JsonResource;

class ProfileResource extends JsonResource
{

    protected array $additional_params;

    public function __construct(User $resource, $additional_params)
    {
        parent::__construct($resource);

        $this->additional_params = $additional_params;
    }

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $response =  parent::toArray($request);

        return array_merge($response, $this->additional_params);
    }
}
