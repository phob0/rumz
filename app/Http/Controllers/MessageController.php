<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class MessageController extends Controller
{

    public function send(Request $request, $channel): \Illuminate\Http\Response
    {
        Message::create([
            'user_id' => auth()->user()->id,
            'channel' => $channel,
            'message' => $request->message
        ]);

        broadcast(new \App\Events\MessageSent($channel, auth()->user(), 'prin api'));

        return response()->noContent();
    }
}
