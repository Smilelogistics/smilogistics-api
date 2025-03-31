<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InvoiceBilltoNotification extends Notification
{
    use Queueable;
    public $invoice;

    /**
     * Create a new notification instance.
     */
    public function __construct($invoice)
    {
        $this->invoice = $invoice;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database', 'broadcast'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Invoice Notification')
            ->greeting('Hello ' . ($notifiable->name ?? ''))
            ->line('An invoice has been generated for you.')
            ->action('View Invoice', url('/invoices/' . $this->invoice->id))
            ->line('Thank you for using our service!');
    }

 
    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray($notifiable): array
    {
        return [
            'invoice_id' => $this->invoice->id,
            'amount' => $this->invoice->total_amount ?? 0,
            'message' => 'A new invoice has been issued.',
            'url' => url('/invoices/' . $this->invoice->id),
        ];
    }

}
