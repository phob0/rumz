<?php

namespace App\Http\Controllers;

use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Symfony\Component\HttpFoundation\Response;

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

    public function markAsReadNotification(Request $request): \Illuminate\Http\Response
    {
        auth()->user()->notifications->where('data.follow_up', false)->markAsRead();

        return response()->noContent();
    }

    public function markAsOldNotification(Request $request, Notification $notification): \Illuminate\Http\Response
    {
        if ($notification->notifiable->id !== auth()->user()->id) {
            throw new HttpResponseException(
                response()->json(['error' => 'This notification doesn`t match current users'], Response::HTTP_FORBIDDEN)
            );
        }

        if (is_null($notification->read_at) || is_null($notification)) {
            throw new HttpResponseException(
                response()->json(['error' => 'Your notification is not read yet or doesn`t exist.'], Response::HTTP_NOT_ACCEPTABLE)
            );
        }

        $notification->delete();

        return response()->noContent();
    }

    public function clearAll(Request $request): \Illuminate\Http\Response
    {
        auth()->user()
            ->notifications->where('read_at', '!==', null)
            ->each(
                fn($item) => \DB::table('notifications')->where('id', $item->id)->delete()
            );

        return response()->noContent();
    }

    public function deleteNotification()
    {
        //force delete
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
