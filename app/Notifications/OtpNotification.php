<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OtpNotification extends Notification
{
    use Queueable;
    public $otp;
    public $expiryMinutes;

    /**
     * Create a new notification instance.
     */
    public function __construct($otp, $expiryMinutes)
    {
        $this->otp = $otp;
        $this->expiryMinutes = $expiryMinutes;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('Your OTP Code')
            ->line("Your OTP code is: **{$this->otp}**")
            ->line('It will expire in ' . $this->expiryMinutes . ' minutes.')
            ->line('If you did not request this, please ignore.');
    }
    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
