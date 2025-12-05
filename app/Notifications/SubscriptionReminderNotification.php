<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SubscriptionReminderNotification extends Notification implements ShouldQueue
{
    use Queueable;
    protected $branch;
    protected $daysLeft;
    /**
     * Create a new notification instance.
     */
    public function __construct($branch, $daysLeft)
    {
        $this->branch = $branch;
        $this->daysLeft = $daysLeft;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        if ($this->daysLeft == 0) {
            return (new MailMessage)
                ->subject('Subscription Expired')
                ->line("Hi {$this->branch->user->fname}, your subscription has expired today.")
                ->line('Please renew your subscription to continue using our service.');
        } else {
            return (new MailMessage)
                ->subject('Subscription Reminder')
                ->line("Hi {$this->branch->user->fname}, your subscription will expire in {$this->daysLeft} day(s).")
                ->line('Please consider renewing it soon.');
        }
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'user_id' => $this->branch->user->id,
            'days_left' => $this->daysLeft,
            'message' => $this->daysLeft == 0
                ? "Your subscription has expired today."
                : "Your subscription will expire in {$this->daysLeft} day(s).",
        ];
    }
}
