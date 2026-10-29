<?php

namespace App\Mail;

use App\Models\ErrorReport;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Support\Str;

class ErrorAlertMail extends Mailable
{
    use Queueable;

    public function __construct(public readonly ErrorReport $report)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Erro no Meu Cash: '.Str::limit($this->report->message, 80));
    }

    public function content(): Content
    {
        return new Content(view: 'emails.error-alert', with: ['r' => $this->report]);
    }
}
