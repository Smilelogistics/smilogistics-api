<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AssigneDriverMail extends Mailable
{
    use Queueable, SerializesModels;
    public $shipment;
    public $driver;
    /**
     * Create a new message instance.
     */
    public function __construct($shipment, $driver)
    {
        $this->shipment = $shipment;
        $this->driver = $driver;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Shiptment Assigned',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'mail.assigne-driver-shipment',
            with: ['shipment' => $this->shipment, 'driver' => $this->driver],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
