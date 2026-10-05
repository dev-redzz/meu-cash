<?php

namespace App\Console\Commands;

use App\Services\NotificationService;
use Illuminate\Console\Command;

class AlertsCommand extends Command
{
    protected $signature = 'meucash:alertas';

    protected $description = 'Atualiza parcelas e contas vencidas e gera notificações de vencimento';

    public function handle(NotificationService $notifications): int
    {
        $notifications->sync();
        $this->info('Vencimentos verificados.');

        return self::SUCCESS;
    }
}
