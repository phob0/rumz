<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProfileController extends Controller
{
    /*
     * TODO
     *  total friends
     */

    public function profile(Request $request): JsonResource
    {
        $user =  User::withCount('rums')->find(auth()->user()->id);
        $user->total_members = $user->rums->sum('members');

        return JsonResource::make($user);
    }

    public function posts(): JsonResource
    {
        return JsonResource::make(auth()->user()->posts);
    }

    public function addBalance(Request $request)
    {}

}
