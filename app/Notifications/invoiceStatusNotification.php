<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class invoiceStatusNotification extends Notification
{
    use Queueable;

    public $invoice;
    public $previousStatus;
    /**
     * Create a new notification instance.
     */
    public function __construct($invoice, $previousStatus)
    {
        $this->invoice = $invoice;
        $this->previousStatus = $previousStatus;
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
            ->subject('Invoice Status Updated')
            ->line('Your invoice status has been updated.')
            ->line('Invoice Number: ' . $this->invoice->invoice_number)
            ->line('Previous Status: ' . ucfirst($this->previousStatus))
            ->line('New Status: ' . ucfirst($this->invoice->status))
            ->action('View Invoice', env('FRONTEND_URL') . '/view_invoice_single.html?id=' . base64_encode($this->invoice->id))
            //->action('View Invoice', url('/invoices/' . $this->invoice->id))
            ->line('Thank you for using our application!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'message' => 'Invoice status updated to ' . $this->invoice->status . '  from ' . $this->previousStatus . ' status',
            'invoice_id' => $this->invoice->id,
            'invoice_number' => $this->invoice->invoice_number,
            'new_status' => $this->invoice->status,
            'link' => '/invoices/' . $this->invoice->id
        ];
    }
}
