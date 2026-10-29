<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class BackupMail extends Mailable
{
    use Queueable;

    public function __construct(public readonly string $path, public readonly int $size, public readonly bool $attach)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Backup do Meu Cash · '.now()->format('d/m/Y'));
    }

    public function content(): Content
    {
        return new Content(view: 'emails.backup', with: [
            'size' => $this->size,
            'attach' => $this->attach,
            'file' => basename($this->path),
        ]);
    }

    public function attachments(): array
    {
        return $this->attach ? [Attachment::fromPath($this->path)->as(basename($this->path))->withMime('application/gzip')] : [];
    }
}
