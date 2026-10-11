<?php

namespace App\Services;

use App\Enums\InstallmentStatus;
use App\Enums\TransactionType;
use App\Models\Installment;
use App\Models\Payment;
use App\Models\Sale;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class InstallmentService
{
    public function __construct(
        private readonly FinanceService $finance,
        private readonly NotificationService $notifications,
    ) {
    }

    public function generate(Sale $sale, ?int $customerId, float $amount, int $count, mixed $firstDue, int $intervalDays = 0): void
    {
        if ($amount <= 0 || $count < 1) {
            return;
        }

        $cents = (int) round($amount * 100);
        $base = intdiv($cents, $count);
        $first = Carbon::parse($firstDue);

        for ($i = 1; $i <= $count; $i++) {
            $value = $i === $count ? $cents - $base * ($count - 1) : $base;
            $due = $intervalDays > 0 ? $first->copy()->addDays($intervalDays * ($i - 1)) : $first->copy()->addMonthsNoOverflow($i - 1);

            Installment::create([
                'sale_id' => $sale->id,
                'customer_id' => $customerId,
                'number' => $i,
                'total_count' => $count,
                'amount' => $value / 100,
                'due_date' => $due->toDateString(),
                'status' => $due->lt(Carbon::today()) ? InstallmentStatus::Vencido : InstallmentStatus::Pendente,
            ]);
        }
    }

    public function registerDownPayment(Sale $sale, float $amount, mixed $date, string $method): void
    {
        if ($amount <= 0) {
            return;
        }

        $payment = Payment::create([
            'sale_id' => $sale->id,
            'customer_id' => $sale->customer_id,
            'user_id' => auth()->id(),
            'amount' => $amount,
            'paid_at' => Carbon::parse($date)->toDateString(),
            'payment_method' => $method,
            'notes' => 'Entrada / pagamento à vista',
        ]);

        $this->finance->record(TransactionType::Entrada, 'venda', "Venda {$sale->code} · ".$sale->customerName(), $amount, $date, $method, null, $payment);
    }

    public function pay(Installment $installment, mixed $paidAt, string $method, ?string $notes = null): Payment
    {
        if (! $installment->isOpen()) {
            throw new RuntimeException('Esta parcela não está em aberto.');
        }

        return DB::transaction(function () use ($installment, $paidAt, $method, $notes) {
            $installment->update([
                'status' => InstallmentStatus::Pago,
                'paid_at' => Carbon::parse($paidAt)->toDateString(),
                'payment_method' => $method,
                'notes' => $notes ?: $installment->notes,
            ]);

            $payment = Payment::create([
                'installment_id' => $installment->id,
                'sale_id' => $installment->sale_id,
                'customer_id' => $installment->customer_id,
                'user_id' => auth()->id(),
                'amount' => $installment->amount,
                'paid_at' => Carbon::parse($paidAt)->toDateString(),
                'payment_method' => $method,
                'notes' => $notes,
            ]);

            $who = $installment->customer?->name ?? 'Sem cliente';
            $this->finance->record(
                TransactionType::Entrada,
                'parcela',
                "Parcela {$installment->label()} · {$installment->originLabel()} · {$who}",
                (float) $installment->amount,
                $paidAt,
                $method,
                null,
                $payment,
            );

            $this->notifications->notify('pagamento_recebido', "Pagamento recebido · {$who}", 'Parcela '.$installment->label().' de '.money($installment->amount), $installment->originUrl());
            AuditService::log('pagamento_registrado', "Parcela {$installment->label()} de ".money($installment->amount)." · {$installment->originLabel()}", $installment);

            return $payment;
        });
    }

    public function changeDueDate(Installment $installment, mixed $date): void
    {
        if (! $installment->isOpen()) {
            throw new RuntimeException('Só é possível alterar o vencimento de parcelas em aberto.');
        }

        $old = $installment->due_date->format('d/m/Y');
        $new = Carbon::parse($date);

        $installment->update([
            'due_date' => $new->toDateString(),
            'status' => $new->lt(Carbon::today()) ? InstallmentStatus::Vencido : InstallmentStatus::Pendente,
        ]);

        AuditService::log('parcela_alterada', "Vencimento da parcela {$installment->label()} alterado de {$old} para {$new->format('d/m/Y')}", $installment);
    }

    public function cancelOpen(Sale $sale): void
    {
        $sale->installments()->whereIn('status', InstallmentStatus::open())->update(['status' => InstallmentStatus::Cancelado->value]);
    }
}
