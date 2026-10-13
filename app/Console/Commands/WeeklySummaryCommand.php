<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Services\WeeklySummaryService;
use Illuminate\Console\Command;

class WeeklySummaryCommand extends Command
{
    protected $signature = 'meucash:resumo-semanal {--empresa= : ID de uma empresa específica}';

    protected $description = 'Envia por e-mail o resumo da semana anterior para os administradores de cada empresa';

    public function handle(WeeklySummaryService $service): int
    {
        $sent = 0;

        Company::where('active', true)
            ->when($this->option('empresa'), fn ($query, $id) => $query->whereKey($id))
            ->each(function (Company $company) use ($service, &$sent) {
                $sent += tenant()->run($company, fn () => $service->sendForCurrentCompany());
            });

        $this->info("Resumos enviados: {$sent}.");

        return self::SUCCESS;
    }
}
