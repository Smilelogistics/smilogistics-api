<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CustomerShipmentCreatedMail extends Mailable
{
    use Queueable, SerializesModels;

    public $shipment;
    public $branch;

    /**
     * Create a new message instance.
     */
    public function __construct($shipment, $branch)
    {
        $this->shipment = $shipment;
        $this->branch = $branch;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Shipment Created',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'mail.customer-shipment-mail',
            with: [
                'shipment' => $this->shipment,
                'branch'   => $this->branch,
            ],
        );
    }
}

