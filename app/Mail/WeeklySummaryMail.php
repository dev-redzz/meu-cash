<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class WeeklySummaryMail extends Mailable
{
    use Queueable;

    public function __construct(public readonly array $summary, public readonly User $user)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Resumo da semana · '.$this->summary['start']->format('d/m').' a '.$this->summary['end']->format('d/m').' · '.$this->summary['company'],
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.weekly-summary', with: ['s' => $this->summary, 'user' => $this->user]);
    }
}
