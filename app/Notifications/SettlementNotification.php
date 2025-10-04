<?php
namespace App\Notifications;

use App\Models\Settlement;
use App\Models\Branch;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SettlementNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public $settlement;
    public $branch;

    /**
     * Create a new notification instance.
     */
    public function __construct(Settlement $settlement, Branch $branch)
    {
        $this->settlement = $settlement;
        $this->branch = $branch;
    }

    /**
     * Get the notification's delivery channels.
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
            ->subject('Settlement Processed')
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('A new settlement has been processed for you.')
            ->line('**Amount:** ' . ($this->branch->currency ?? '$' . number_format($this->settlement->payment_total, 2)))
            ->line('**Processed Date:** ' . $this->settlement->created_at->format('F j, Y, g:i a'))
            ->line('**Processed By Branch:** ' . $this->branch->user->fname)
            ->action('View Settlement Details', url('/settlements/' . $this->settlement->id))
            ->line('Thank you for being a valued partner with ' . ($this->branch->name ?? 'Smiles Logistics') . '!');
    }

    /**
     * Get the array representation of the notification (for database).
     */
    public function toArray(object $notifiable): array
    {
        return [
            'settlement_id' => $this->settlement->id,
            'amount' => $this->settlement->payment_total,
            'branch_name' => $this->branch->user->fname,
            'created_at' => $this->settlement->created_at,
            'link' => env('FRONTEND_URL') . '/view_settlement_single.html?id=' . base64_encode($this->settlement->id),
            //'url' => url('/settlements/' . $this->settlement->id),
            'message' => 'A new settlement has been processed for you by ' . $this->branch->name . '.',
        ];
    }
}
