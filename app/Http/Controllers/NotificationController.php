<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationController extends Controller
{
    public function lookup(Request $request): JsonResource
    {
        return JsonResource::make(['count' => auth()->user()->notifications->count()]);
    }

    public function allNotifications(): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        return JsonResource::collection(auth()->user()->notifications);
    }
}
