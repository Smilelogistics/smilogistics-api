<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use App\Models\ConsolidateShipment;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use App\Models\User;

class DriverAcceptConsolidationDeliveryNotification extends Notification
{
    use Queueable;

    protected $consolidateShipment;
    protected $driver;

    /**
     * Create a new notification instance.
     */
    public function __construct(ConsolidateShipment $consolidateShipment, User $driver)
    {
        $this->consolidateShipment = $consolidateShipment;
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
    public function toArray(object $notifiable): array
    {
        return [
            'consolidate_shipment_id' => $this->consolidateShipment->id,
            'message' => 'Driver has accepted your consolidation delivery request',
            'driver_name' => $this->driver->fname . ' ' . $this->driver->last_name,
            'driver_phone' => $this->driver->phone ?? 'N/A',
            'tracking_number' => $this->consolidateShipment->consolidate_tracking_number,
            'type' => 'consolidation_accepted'
        ];
    }
}