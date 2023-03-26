<?php

namespace App\Http\Controllers;

use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MessageController extends Controller
{

    // TODO: write policies

    public function send(Request $request, $channel): \Illuminate\Http\Response
    {
        // $this->authorize('send', $channel);

        $message = Message::create([
            'user_id' => auth()->user()->id,
            'channel' => $channel,
            'message' => $request->message
        ]);

        broadcast(new \App\Events\MessageSent($channel, auth()->user(), $message->refresh()));

        return response()->noContent();
    }

    public function history(Request $request, $channel): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        // $this->authorize('history', $channel);

        return JsonResource::collection(
            Message::where('channel', $channel)->orderBy('created_at', 'DESC')->paginate(20)
        );
    }

    public function seen(Request $request, Message $message): \Illuminate\Http\Response
    {
        $message->update([
            'read_at' => \Carbon\Carbon::now()
        ]);

        return response()->noContent();
    }
}
