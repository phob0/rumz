<?php

namespace App\Http\Controllers;

use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MessageController extends Controller
{

    public function send(Request $request, $channel): \Illuminate\Http\Response
    {
        Message::create([
            'user_id' => auth()->user()->id,
            'channel' => $channel,
            'message' => $request->message
        ]);

        broadcast(new \App\Events\MessageSent($channel, auth()->user(), $request->message));

        return response()->noContent();
    }

    public function history(Request $request, $channel): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        return JsonResource::collection(Message::where('channel', $channel)->paginate(5));
    }

    public function seen(Request $request, Message $message): \Illuminate\Http\Response
    {
        Message::where(
            [
                ['user_id', '=', $message->user_id],
                ['read_at', '=', null],
            ]
        )->update([
            'read_at' => Carbon\Carbon::now()
        ]);

        return response()->noContent();
    }
}
