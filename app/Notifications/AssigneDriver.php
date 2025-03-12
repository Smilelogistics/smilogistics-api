<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AssigneDriver extends Notification
{
    use Queueable;
    public $shipment;
    public $driver;
    /**
     * Create a new notification instance.
     */
    public function __construct($shipment, $driver)
    {
        $this->shipment = $shipment;
        $this->driver = $driver;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
                    ->line('The introduction to the notification.')
                    ->action('Notification Action', url('/'))
                    ->line('Thank you for using our application!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'shipment_id' => $this->shipment->id,
            'shipment_status' => $this->shipment->status,
            'shipment_destination' => $this->shipment->destination,
            'driver_id' => $this->driver->id,
            'driver_name' => $this->driver->user->name,
            'driver_email' => $this->driver->user->email,
            'message' => "You have been assigned a new shipment (#{$this->shipment->id}).",
        ];
    }
}
