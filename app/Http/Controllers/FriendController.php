<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FriendController extends Controller
{

    public function lookupFriends(Request $request): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        return JsonResource::collection(auth()->user()->friends);
    }
}
