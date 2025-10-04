<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BillOfLandingMail extends Mailable
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
            subject: 'Bill Of Landing',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'mail.bill-of-landing',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView(
            'pdf.bill-of-landing',
            ['branch' => $this->quote, 'shipment' => $this->shipment]
        )->setPaper('a4', 'portrait');

        return [
            \Illuminate\Mail\Mailables\Attachment::fromData(
                fn () => $pdf->output(),
                'bill-of-landing.pdf'
            )
            ->as('bill-of-landing.pdf')
            ->withMime('application/pdf'),
        ];
    }
}
