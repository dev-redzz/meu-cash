<?php

namespace App\Services;

use App\Models\Installment;
use App\Models\Setting;
use App\Models\WhatsAppMessage;
use Illuminate\Support\Carbon;
use RuntimeException;

class WhatsAppService
{
    public const TYPES = [
        'lembrete' => 'Lembrete de parcela próxima',
        'vence_hoje' => 'Parcela vencendo hoje',
        'atrasada' => 'Parcela atrasada',
        'pagamento' => 'Confirmação de pagamento',
    ];

    public function suggestedType(Installment $installment): string
    {
        if (! $installment->isOpen()) {
            return 'pagamento';
        }

        $today = Carbon::today();

        return match (true) {
            $installment->due_date->lt($today) => 'atrasada',
            $installment->due_date->isSameDay($today) => 'vence_hoje',
            default => 'lembrete',
        };
    }

    public function message(Installment $installment, string $type): string
    {
        $template = (string) Setting::get("whatsapp_template_{$type}");

        return strtr($template, [
            '{cliente}' => $installment->customer?->name ?? 'cliente',
            '{valor}' => money($installment->amount),
            '{vencimento}' => $installment->due_date->format('d/m/Y'),
            '{parcela}' => $installment->label(),
            '{empresa}' => (string) Setting::get('company_name', 'Meu Cash'),
        ]);
    }

    public function normalizePhone(?string $phone): ?string
    {
        $digits = only_digits($phone);

        if ($digits === '') {
            return null;
        }

        if (strlen($digits) <= 11) {
            $digits = '55'.ltrim($digits, '0');
        }

        return $digits;
    }

    public function link(string $phone, string $message): string
    {
        return 'https://wa.me/'.$phone.'?text='.rawurlencode($message);
    }

    public function send(Installment $installment, ?string $type = null): string
    {
        $type = array_key_exists((string) $type, self::TYPES) ? $type : $this->suggestedType($installment);
        $phone = $this->normalizePhone($installment->customer?->whatsappNumber());

        if (! $phone) {
            throw new RuntimeException('O cliente desta parcela não tem WhatsApp ou telefone cadastrado.');
        }

        $message = $this->message($installment, $type);

        WhatsAppMessage::create([
            'customer_id' => $installment->customer_id,
            'installment_id' => $installment->id,
            'user_id' => auth()->id(),
            'phone' => $phone,
            'type' => $type,
            'message' => $message,
            'channel' => config('meucash.whatsapp.driver', 'link'),
        ]);

        AuditService::log('whatsapp', self::TYPES[$type].' para '.($installment->customer?->name ?? $phone), $installment);

        return $this->link($phone, $message);
    }
}
