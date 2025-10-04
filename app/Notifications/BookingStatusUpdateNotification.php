<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BookingStatusUpdateNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public $shipment;
    public $status;

    public function __construct($shipment, $status)
    {
        $this->shipment = $shipment;
        $this->status = $status;
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database']; // ✅ send both email + DB notification
    }

    public function toMail(object $notifiable): MailMessage
    {
        $link = url(env('FRONTEND_URL') . "/view_loads_single.html?id=" . base64_encode($this->shipment->id));

        return (new MailMessage)
            ->subject("Booking Status Update: {$this->status}")
            ->line("Your booking with reference #{$this->shipment->reference_number} has been {$this->status}.")
            ->action('View Booking', $link)
            ->line('Thank you for using our logistics service!');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'shipment_id' => $this->shipment->id,
            'status'      => $this->status,
            'message'     => "Booking has been {$this->status}",
            'link'        => url(env('FRONTEND_URL') . "/view_loads_single.html?id=" . base64_encode($this->shipment->id)),
        ];
    }
}

