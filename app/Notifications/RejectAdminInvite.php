<?php

namespace App\Notifications;

use App\Interfaces\NotificationTypes;
use App\Models\Rum;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RejectAdminInvite extends Notification implements NotificationTypes
{
    use Queueable;

    public Rum $rum;
    public string $message;
    public bool $follow_up = false;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(Rum $rum, $message)
    {
        $this->rum = $rum;
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
            'rum' => $this->rum,
            'message' => $this->message,
            'follow_up' => $this->follow_up,
            'notification_type' => self::ADMIN_ROOM_ACCEPTANCE
        ];
    }
}
