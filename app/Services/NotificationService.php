<?php

namespace App\Services;

use App\Enums\InstallmentStatus;
use App\Models\AppNotification;
use App\Models\Installment;
use App\Models\Setting;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

class NotificationService
{
    public function notify(string $type, string $title, ?string $message = null, ?string $url = null, ?string $key = null): AppNotification
    {
        $data = ['type' => $type, 'title' => $title, 'message' => $message, 'url' => $url];

        if ($key) {
            return AppNotification::firstOrCreate(['key' => $key], $data);
        }

        return AppNotification::create($data);
    }

    public function syncIfDue(): void
    {
        if (Cache::add('meucash.alerts-sync', true, now()->addMinutes(30))) {
            $this->sync();
        }
    }

    public function sync(): void
    {
        $today = Carbon::today();
        $limit = $today->copy()->addDays(max(0, (int) Setting::get('due_reminder_days', 3)));

        Installment::where('status', InstallmentStatus::Pendente->value)->where('due_date', '<', $today->toDateString())->update(['status' => InstallmentStatus::Vencido->value]);

        Installment::with(['customer', 'sale', 'service'])
            ->open()
            ->where('due_date', '<=', $limit->toDateString())
            ->get()
            ->each(function (Installment $installment) use ($today) {
                $overdue = $installment->due_date->lt($today);
                $who = $installment->customer?->name ?? 'Cliente';
                $this->notify(
                    $overdue ? 'parcela_vencida' : 'parcela_proxima',
                    $overdue ? "Parcela vencida · {$who}" : "Parcela vence em {$installment->due_date->format('d/m')} · {$who}",
                    "Parcela {$installment->label()} de ".money($installment->amount).' · '.$installment->originLabel(),
                    $installment->originUrl(),
                    ($overdue ? 'parcela_vencida_' : 'parcela_proxima_').$installment->id,
                );
            });
    }
}
