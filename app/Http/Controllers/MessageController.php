<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Message;

class MessageController extends Controller
{

    public function send(Request $request, $channel)
    {
        Message::create([
            'user_id' => auth()->user()->id,
            'channel' => $channel,
            'message' => $request->message
        ]);

        broadcast(new \App\Events\MessageSent($channel, auth()->user(), $request->message));
    }
}
