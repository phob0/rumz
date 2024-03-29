<?php

namespace App\Notifications;

use App\Models\Comment;
use App\Models\RumPost;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CommentReport extends Notification
{
    use Queueable;

    public RumPost $rumPost;
    public Comment $comment;
    public array $reply = [];
    public string $message;
    public bool $follow_up = false;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($rumPost, $comment, $reply, $message = '')
    {
        $this->message = $message;
        $this->rumPost = $rumPost;
        $this->comment = $comment;
        $this->reply = $reply;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['database'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        return (new MailMessage)
                    ->line('The introduction to the notification.')
                    ->action('Notification Action', url('/'))
                    ->line('Thank you for using our application!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            "message" => $this->message,
            "rum_post" => $this->rumPost,
            "comment" => $this->comment,
            "reply" => $this->reply,
            "follow_up" => $this->follow_up,
            // TODO: add notification type
        ];
    }
}
