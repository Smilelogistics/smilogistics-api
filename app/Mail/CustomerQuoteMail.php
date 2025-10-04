<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Mail\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Contracts\Queue\ShouldQueue;

class CustomerQuoteMail extends Mailable
{
    use Queueable, SerializesModels;
    public $quote;
    public $shipment;

    /**
     * Create a new message instance.
     */
    public function __construct($quote)
    {
        $this->quote = $quote;
        $this->shipment = $quote->shipment;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your Quote for ' . $this->shipment->origin . ' to ' . $this->shipment->destination,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'mail.customer-quote',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        //$pdf = Pdf::loadView('pdf.customer-quote', ['quote' => $this->quote, 'shipment' => $this->shipment])->setPaper('a4', 'portrait');
        //$pdf->save(public_path('quotes/' . $this->quote->id . '.pdf'));
        // return [
        //     Attachment::fromData(fn () => $pdf->output(), 'quote.pdf')
        //         ->withMime('application/pdf'),
        // ];

         $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView(
            'pdf.customer-quote',
            ['quote' => $this->quote, 'shipment' => $this->shipment]
        )->setPaper('a4', 'portrait');

        return [
            \Illuminate\Mail\Mailables\Attachment::fromData(
                fn () => $pdf->output(),
                'quote.pdf'
            )
            ->as('quote.pdf')
            ->withMime('application/pdf'),
        ];
    }
}
