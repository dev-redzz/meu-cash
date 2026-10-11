<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use PDO;

class InstallCommand extends Command
{
    protected $signature = 'meucash:instalar {--fresh : Apaga todas as tabelas e instala do zero}';

    protected $description = 'Cria o banco (se não existir), executa as migrations, os seeders e o link de storage';

    public function handle(): int
    {
        if (blank(config('app.key'))) {
            $this->call('key:generate', ['--force' => true]);
        }

        $connection = config('database.default');
        $config = config("database.connections.{$connection}");

        if (in_array($config['driver'], ['mysql', 'mariadb'], true)) {
            try {
                $pdo = new PDO("mysql:host={$config['host']};port={$config['port']}", $config['username'], $config['password']);
                $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$config['database']}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                $this->info("Banco `{$config['database']}` pronto.");
            } catch (\Throwable $e) {
                $this->error('Não foi possível conectar ao MySQL. O MySQL do XAMPP está iniciado? Detalhe: '.$e->getMessage());

                return self::FAILURE;
            }

            DB::purge($connection);
        }

        $this->call($this->option('fresh') ? 'migrate:fresh' : 'migrate', ['--force' => true]);
        if (! file_exists(public_path('storage'))) {
            $this->call('storage:link');
        }

        $this->call('optimize:clear');

        $this->newLine();
        $this->info('Meu Cash instalado.');
        $this->line('Acesse: '.config('app.url'));
        $this->line('Entre com sua conta Google e cadastre sua empresa.');

        if (blank(config('services.google.client_id'))) {
            $this->warn('Falta configurar GOOGLE_CLIENT_ID e GOOGLE_CLIENT_SECRET no .env (veja o LEIA-ME).');
        }

        return self::SUCCESS;
    }
}
