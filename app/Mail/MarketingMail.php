<?php

namespace App\Mail;

use App\Models\MarketingEmail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Headers;
use Illuminate\Queue\SerializesModels;

/**
 * NOT-3: one marketing email, with a one-click unsubscribe link in the body
 * and in the headers mail apps use for their own unsubscribe button.
 */
class MarketingMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public MarketingEmail $email, public string $unsubscribeUrl) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->email->subject);
    }

    public function headers(): Headers
    {
        return new Headers(text: [
            'List-Unsubscribe' => "<{$this->unsubscribeUrl}>",
            'List-Unsubscribe-Post' => 'List-Unsubscribe=One-Click',
        ]);
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.marketing');
    }
}
