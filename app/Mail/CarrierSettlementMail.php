<?php

namespace App\Mail;

use App\Models\Branch;
use App\Models\Settlement;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Contracts\Queue\ShouldQueue;

class CarrierSettlementMail extends Mailable
{
    use Queueable, SerializesModels;

    public $settlement;
    public $branch;

    /**
     * Create a new message instance.
     */
    public function __construct(Settlement $settlement, Branch $branch)
    {
        $this->settlement = $settlement;
        $this->branch = $branch;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Carrier Settlement',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'settlement.CarrierSettlementMail',
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
