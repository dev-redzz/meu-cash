<?php

namespace App\Mail\Transport;

use Illuminate\Support\Facades\Http;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\MessageConverter;

/** Envia e-mails pela API HTTP da Brevo (funciona onde a porta SMTP é bloqueada, como no Railway). */
class BrevoTransport extends AbstractTransport
{
    public function __construct(private readonly string $key)
    {
        parent::__construct();
    }

    protected function doSend(SentMessage $message): void
    {
        $email = MessageConverter::toEmail($message->getOriginalMessage());
        $envelope = $message->getEnvelope();
        $from = $email->getFrom()[0] ?? $envelope->getSender();

        $people = fn (array $addresses) => array_map(
            fn (Address $a) => array_filter(['email' => $a->getAddress(), 'name' => $a->getName() ?: null]),
            $addresses,
        );

        $payload = array_filter([
            'sender' => array_filter(['email' => $from->getAddress(), 'name' => $from->getName() ?: null]),
            'to' => $people($email->getTo() ?: $envelope->getRecipients()),
            'cc' => $people($email->getCc()) ?: null,
            'bcc' => $people($email->getBcc()) ?: null,
            'replyTo' => ($reply = $email->getReplyTo()[0] ?? null) ? array_filter(['email' => $reply->getAddress(), 'name' => $reply->getName() ?: null]) : null,
            'subject' => $email->getSubject(),
            'htmlContent' => $email->getHtmlBody(),
            'textContent' => $email->getTextBody(),
            'attachment' => array_map(fn ($part) => [
                'name' => $part->getFilename() ?? 'anexo',
                'content' => base64_encode($part->getBody()),
            ], $email->getAttachments()) ?: null,
        ]);

        $response = Http::timeout(15)
            ->withHeaders(['api-key' => $this->key, 'accept' => 'application/json'])
            ->post('https://api.brevo.com/v3/smtp/email', $payload);

        if ($response->failed()) {
            throw new TransportException('Brevo recusou o e-mail: '.$response->status().' '.$response->body());
        }

        if ($id = $response->json('messageId')) {
            $message->setMessageId($id);
        }
    }

    public function __toString(): string
    {
        return 'brevo';
    }
}
