<?php

namespace App\Console\Commands;

use App\Services\BackupService;
use Illuminate\Console\Command;

class BackupCommand extends Command
{
    protected $signature = 'meucash:backup';

    protected $description = 'Gera o backup do banco e envia por e-mail';

    public function handle(BackupService $backup): int
    {
        $status = $backup->run();
        $this->line($status['message'] ?? '');

        return $status['ok'] ? self::SUCCESS : self::FAILURE;
    }
}
