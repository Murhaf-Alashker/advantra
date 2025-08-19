<?php

namespace App\Mail;

use App\Models\Guide;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class GuideWelcomeMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    protected Guide $guide;
    protected string $unHashedPassword;
    public function __construct(Guide $guide,$unHashedPassword)
    {
        $this->guide = $guide;
        $this->unHashedPassword = $unHashedPassword;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Guide Welcome Mail',
        );
    }


    public function build()
    {
        return $this->subject('Your Guide Account')
            ->html("
                        <h1>Welcome, {$this->guide->name}!</h1>
                        <p>Your account has been created successfully.</p>
                        <p><strong>Password:</strong> {$this->unHashedPassword}</p>
                        <p>Please change your password after logging in.</p>
                    ");
    }
    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'view.name',
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
