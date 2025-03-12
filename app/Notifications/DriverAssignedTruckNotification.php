<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\DatabaseMessage;
use App\Models\Truck;

class DriverAssignedTruckNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $truck;

    public function __construct(Truck $truck)
    {
        $this->truck = $truck;
    }

    public function via($notifiable)
    {
        return ['mail', 'database'];
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

    public function toDatabase($notifiable)
    {
        return [
            'message' => 'You have been assigned to Truck #' . $this->truck->truck_number,
            'truck_id' => $this->truck->id,
        ];
    }
}

