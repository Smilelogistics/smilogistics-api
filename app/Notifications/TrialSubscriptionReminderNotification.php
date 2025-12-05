<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TrialSubscriptionReminderNotification extends Notification Implements ShouldQueue
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
        return (new MailMessage)
            ->greeting("Hi {$this->branch->user->fname},")
            ->subject('Trial Subscription Reminder')
            ->action('Check our plans', env('FRONTEND_URL') . '/pricing' ) //url('/pricing'))
            ->line("Your trial ends in {$this->daysLeft} day(s). Please consider upgrading.");
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'branch_id' => $this->branch->id,
            'days_left' => $this->daysLeft,
            'message' => "Trial ends in {$this->daysLeft} day(s).",
        ];
    }
}
