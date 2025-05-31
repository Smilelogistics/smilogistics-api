<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ShipmentUpdateNotification extends Notification
{
    use Queueable;
    public $shipment;
    public $previousStatus;
    /**
     * Create a new notification instance.
     */
    public function __construct($shipment, $previousStatus)
    {
        $this->shipment = $shipment;
        $this->previousStatus = $previousStatus;
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
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Update on Your Shipment')
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line("We wanted to let you know that the status of your shipment (Tracking ID: {$this->shipment->shipment_tracking_number}) has been updated.")
            ->line("**Previous Status:** {$this->previousStatus}")
            ->line("**Current Status:** {$this->shipment->status}")
            ->line('You can view more details about your shipment by clicking the button below.')
            ->action('Track Shipment', url("/shipments/{$this->shipment->id}"))
            ->line('Thank you for choosing our service!');
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
