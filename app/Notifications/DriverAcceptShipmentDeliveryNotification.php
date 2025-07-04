<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DriverAcceptShipmentDeliveryNotification extends Notification
{
    use Queueable;
    protected $shipment;
    protected $driver;

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

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('Truck Assigned')
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('You have been assigned to a new truck.')
            ->line('Truck Number: ' . $this->truck->truck_number)
            ->line('License Plate: ' . $this->truck->license_plate_number)
            ->action('View Truck Details', url('/trucks/' . $this->truck->id))
            ->line('Please review the assignment.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'shipment_id' => $this->shipment->id,
            'message' => 'Driver has accepted your consolidation delivery request',
            'driver_name' => $this->driver->user->fname . ' ' . $this->driver->user->lname,
            'driver_phone' => $this->driver->phone ?? 'N/A',
            'tracking_number' => $this->shipment->consolidate_tracking_number,
            'type' => 'shipment_accepted',
            'link' => env('FRONTEND_URL') . '/view_consolidated_single.html?id=' . base64_encode($this->shipment->id),
        ];
    }
}
