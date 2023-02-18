<?php

namespace App\Notifications;

use App\Models\Rum;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Interfaces\NotificationTypes;

class BanUnbanMember extends Notification implements NotificationTypes
{
    use Queueable;

    public Rum $rum;
    public string $message;
    public bool $ban = false;
    public bool $follow_up = false;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(Rum $rum, $message, $ban = false)
    {
        $this->rum = $rum;
        $this->message = $message;
        $this->ban = $ban;
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
            'rum' => $this->rum,
            'message' => $this->message,
            "follow_up" => $this->follow_up,
            'notification_type' => $this->ban ? self::BAN_MEMBER : self::UNBAN_MEMBER
        ];
    }
}
