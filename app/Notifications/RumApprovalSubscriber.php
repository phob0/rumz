<?php

namespace App\Notifications;

use App\Models\Rum;
use App\Models\UserRum;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Interfaces\NotificationTypes;

class RumApprovalSubscriber extends Notification implements NotificationTypes
{
    use Queueable;

    public Rum $rum;
    public UserRum $userRum;
    public string $message;
    public bool $follow_up = false;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($rum, $userRum, $message = '')
    {
        $this->rum = $rum;
        $this->userRum = $userRum;
        $this->message = $message;
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
            "subscriber" => $notifiable,
            "rum" => $this->rum,
            "user_rum" => $this->userRum,
            "follow_up" => $this->follow_up,
            'notification_type' => self::ROOM_APPROVAL_SUBSCRIBER
        ];
    }
}
