<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class newDriverMail extends Mailable
{
    use Queueable, SerializesModels;
    public $createUser;
    public $branch;
    /**
     * Create a new message instance.
     */
    public function __construct($createUser, $branch)
    {
        $this->createUser = $createUser;
        $this->branch = $branch;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Account Created Successfully',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
    view: 'mail.new-driver-mail',
    with: [
        'createUser' => $this->createUser,
        'branch'     => $this->branch,
    ],
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
