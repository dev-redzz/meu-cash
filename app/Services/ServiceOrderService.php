<?php

namespace App\Services;

use App\Enums\ServiceStatus;
use App\Enums\TransactionType;
use App\Models\Service;
use App\Models\ServiceExpense;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ServiceOrderService
{
    public function __construct(
        private readonly InstallmentService $installments,
        private readonly FinanceService $finance,
    ) {
    }

    public function create(array $data): Service
    {
        return DB::transaction(function () use ($data) {
            $amount = round((float) $data['amount'], 2);
            $down = min(round((float) ($data['down_payment'] ?? 0), 2), $amount);
            $remaining = round($amount - $down, 2);
            $count = $remaining > 0 ? max(1, (int) ($data['installments_count'] ?? 1)) : 0;

            $service = Service::create([
                'customer_id' => $data['customer_id'] ?? null,
                'category_id' => $data['category_id'] ?? null,
                'user_id' => auth()->id(),
                'name' => $data['name'],
                'date' => $data['date'],
                'amount' => $amount,
                'down_payment' => $down,
                'payment_method' => $data['payment_method'],
                'installments_count' => $count,
                'status' => $data['status'],
                'notes' => $data['notes'] ?? null,
            ]);

            $method = $data['payment_method'] === 'parcelado' ? 'outro' : $data['payment_method'];
            $this->installments->registerDownPayment($service, $down, $service->date, $method);

            if ($remaining > 0) {
                $this->installments->generate(
                    $service,
                    $service->customer_id,
                    $remaining,
                    $count,
                    $data['first_due_date'] ?? Carbon::parse($service->date)->addMonthNoOverflow(),
                );
            }

            AuditService::log('servico_criado', "Serviço {$service->name} de ".money($amount), $service);

            return $service;
        });
    }

    public function update(Service $service, array $data): Service
    {
        $service->update($data);

        if (array_key_exists('customer_id', $data)) {
            $service->installments()->update(['customer_id' => $data['customer_id']]);
            $service->payments()->update(['customer_id' => $data['customer_id']]);
        }

        if ($service->status === ServiceStatus::Cancelado) {
            $this->installments->cancelOpen($service);
        }

        AuditService::log('servico_editado', "Serviço {$service->name} editado", $service);

        return $service;
    }

    public function cancel(Service $service): void
    {
        DB::transaction(function () use ($service) {
            $this->installments->cancelOpen($service);
            $service->update(['status' => ServiceStatus::Cancelado]);
            AuditService::log('servico_cancelado', "Serviço {$service->name} cancelado", $service);
        });
    }

    public function addExpense(Service $service, array $data): ServiceExpense
    {
        return DB::transaction(function () use ($service, $data) {
            $expense = $service->expenses()->create($data);
            $service->update(['expenses_total' => (float) $service->expenses()->sum('amount')]);

            $this->finance->record(
                TransactionType::Saida,
                'despesa_servico',
                "{$expense->description} · Serviço {$service->name}",
                (float) $expense->amount,
                $expense->date,
                null,
                'despesa',
                $expense,
            );

            AuditService::log('despesa_registrada', "Despesa {$expense->description} de ".money($expense->amount)." no serviço {$service->name}", $service);

            return $expense;
        });
    }

    public function removeExpense(ServiceExpense $expense): void
    {
        DB::transaction(function () use ($expense) {
            $service = $expense->service;
            $this->finance->removeFor($expense);
            $expense->delete();
            $service->update(['expenses_total' => (float) $service->expenses()->sum('amount')]);
            AuditService::log('despesa_removida', "Despesa {$expense->description} removida do serviço {$service->name}", $service);
        });
    }
}
