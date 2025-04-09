<?php

namespace App\Mail;

use App\Models\Branch;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use App\Models\ConsolidateShipment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Contracts\Queue\ShouldQueue;

class ConsolidateShipmentCustomerMail extends Mailable
{
    use Queueable, SerializesModels;

    public $consolidateShipment;
    public $branch;
    /**
     * Create a new message instance.
     */
    public function __construct(ConsolidateShipment $consolidateShipment, Branch $branch)
    {
        $this->consolidateShipment = $consolidateShipment;
        $this->branch = $branch;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Consolidate Shipment Customer Mail',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'mail.consolidate.consolidate-shipment-customer',
            with: ['consolidateShipment' => $this->consolidateShipment, 'branch' => $this->branch],
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
