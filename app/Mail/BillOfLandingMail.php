<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

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
            subject: 'Bill Of Lading - ' . ($this->shipment->shipment_tracking_number ?? ''),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'mail.bill-of-landing',
            with: [
                'shipment' => $this->shipment,
                'branch' => $this->branch,
            ]
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        $pdf = Pdf::loadView(
            'pdf.bill-of-landing',
            ['branch' => $this->branch, 'shipment' => $this->shipment]
        )->setPaper('a4', 'portrait');

        return [
            \Illuminate\Mail\Mailables\Attachment::fromData(
                fn () => $pdf->output(),
                'bill-of-landing-' . ($this->shipment->shipment_tracking_number ?? '') . '.pdf'
            )
            ->withMime('application/pdf'),
        ];
    }
}