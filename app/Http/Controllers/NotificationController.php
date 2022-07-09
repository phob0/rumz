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

    /*
     * TODO
     *  add delete method
     *  add follow-up, refers to the action the user must take with a certain type of notifications.
     *  split into types of notifications, informative|interactive
     *  informative: notifications that inform the user of a certain change, including but not limited to: received a new message, a new post in a rum that he joined, invitation responses, shared posts
     *  interactive: notifications that require the user to take action, including but not limited to: Requests by other users to join a specific rum, request to manage a rum, pending request to join a rum (if user has admin privileges), friend requests, rum posts or comments reports.
     *  Only interactive notifications are push notifications.
     */
}
