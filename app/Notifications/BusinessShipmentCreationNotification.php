<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BusinessShipmentCreationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public $shipment;

    /**
     * Create a new notification instance.
     */
    public function __construct($shipment)
    {
        $this->shipment = $shipment;
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
            ->subject('New Shipment Created')
            ->line('A new shipment has been created by a customer.')
            ->line('Reference Number: ' . $this->shipment->reference_number)
            // ->line('Status: ' . $this->shipment->status)
            ->action(
                'View Shipment',
                config('app.frontend_url'). '/view_loads_single.html?id=' . base64_encode($this->shipment->id)
            );
            //->line('Thank you for using our application!');
    }

    /**
     * Get the array representation of the notification (database storage).
     */
    public function toArray(object $notifiable): array
    {
        return [
            'shipment_id'   => $this->shipment->id,
            'reference'     => $this->shipment->reference_number,
            'status'        => $this->shipment->status,
            'created_by'    => auth()->id(),
            'url'           => url('/view_loads_single.html?id=' . base64_encode($this->shipment->id)),
        ];
    }
}
