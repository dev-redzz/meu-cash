<?php

namespace App\Mail;

use App\Models\Feedback;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Support\Str;

class FeedbackMail extends Mailable
{
    use Queueable;

    public function __construct(public readonly Feedback $feedback)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->feedback->typeLabel().' no Meu Cash: '.Str::limit($this->feedback->message, 60),
            replyTo: $this->feedback->user?->email ? [$this->feedback->user->email] : [],
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.feedback', with: ['f' => $this->feedback]);
    }
}
