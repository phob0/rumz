<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class MessagePolicy
{
    use HandlesAuthorization;

    /**
     * Create a new policy instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    public function send(User $user, $channel)
    {
        // if first or single record or multiple but with single unique user id that equals current session => true
        // else
        // get both user ids
        // compare channel with algorythm users id 
        // 
        return true;
    }

    public function history(User $user, $channel)
    {
        $message = Message::where([
            ['channel', '=', $channel],
            ['user_id', '=', $user->id]
        ]);

        return $message->count() > 0 && $message->exists();
    }
}
