<?php

namespace App\Services;

use App\Mail\BackupMail;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Throwable;

/**
 * Cópia completa do banco em um arquivo .sql.gz.
 * Todo dia o arquivo vai por e-mail para o dono da plataforma; no painel dá para baixar na hora.
 */
class BackupService
{
    private const SKIP = ['sessions', 'cache', 'cache_locks', 'jobs', 'job_batches', 'failed_jobs', 'undo_snapshots', 'error_reports'];

    private const STATUS_KEY = 'meucash.backup.last';

    /** Gera o arquivo e devolve o caminho completo. */
    public function dump(): string
    {
        $dir = storage_path('app/backups');
        if (! is_dir($dir)) {
            mkdir($dir, 0700, true);
        }

        $path = $dir.'/meucash-'.now()->format('Y-m-d-His').'.sql.gz';
        $gz = gzopen($path, 'wb6');

        if (! $gz) {
            throw new RuntimeException('Não foi possível criar o arquivo de backup.');
        }

        $mysql = DB::getDriverName() !== 'sqlite';
        $pdo = DB::getPdo();

        gzwrite($gz, "-- Backup do Meu Cash\n-- Gerado em ".now()->format('d/m/Y H:i:s')."\n\n");
        gzwrite($gz, $mysql ? "SET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS=0;\n\n" : "PRAGMA foreign_keys=OFF;\n\n");

        foreach ($this->tables() as $table) {
            $quoted = $mysql ? "`{$table}`" : "\"{$table}\"";
            gzwrite($gz, "-- Tabela {$table}\nDROP TABLE IF EXISTS {$quoted};\n".$this->createStatement($table, $mysql).";\n");

            $columns = null;
            DB::table($table)->orderBy(DB::raw('1'))->lazy(500)->chunk(200)->each(function ($rows) use ($gz, $quoted, $pdo, $mysql, &$columns) {
                $rows = $rows->map(fn ($row) => (array) $row);
                $columns ??= implode(', ', array_map(fn ($c) => $mysql ? "`{$c}`" : "\"{$c}\"", array_keys($rows->first())));
                $values = $rows->map(fn ($row) => '('.implode(', ', array_map(
                    fn ($v) => $v === null ? 'NULL' : (is_int($v) || is_float($v) ? (string) $v : $pdo->quote((string) $v)),
                    $row,
                )).')')->implode(",\n");
                gzwrite($gz, "INSERT INTO {$quoted} ({$columns}) VALUES\n{$values};\n");
            });

            gzwrite($gz, "\n");
        }

        gzwrite($gz, $mysql ? "SET FOREIGN_KEY_CHECKS=1;\n" : "PRAGMA foreign_keys=ON;\n");
        gzclose($gz);

        $this->keepLast($dir, 3);

        return $path;
    }

    /** Backup do dia: gera e manda por e-mail. Grava o resultado para o painel mostrar. */
    public function run(): array
    {
        $status = ['at' => now()->toIso8601String(), 'ok' => false, 'size' => 0, 'sent_to' => [], 'message' => null];

        try {
            $path = $this->dump();
            $status['size'] = filesize($path);
            $recipients = $this->recipients();
            $attach = $status['size'] <= $this->maxAttachmentBytes();

            if ($recipients) {
                Mail::to($recipients)->send(new BackupMail($path, $status['size'], $attach));
                $status['sent_to'] = $recipients;
            }

            $status['ok'] = true;
            $status['message'] = ! $recipients
                ? 'Backup gerado, mas não há e-mail para enviar. Defina BACKUP_EMAIL ou OWNER_EMAIL.'
                : ($attach ? 'Backup enviado por e-mail.' : 'Backup grande demais para anexar: o e-mail avisa para baixar pelo painel.');
        } catch (Throwable $e) {
            report($e);
            Log::error('Backup diário falhou: '.$e->getMessage());
            $status['message'] = 'Falhou: '.$e->getMessage();
        }

        Cache::forever(self::STATUS_KEY, $status);

        return $status;
    }

    /** Já passou das 3h e ainda não houve backup bem-sucedido hoje. */
    public function isDue(): bool
    {
        if (! config('meucash.backup.enabled') || now()->hour < 3) {
            return false;
        }

        $last = $this->lastStatus();

        return ! $last || ! $last['ok'] || ! Carbon::parse($last['at'])->isToday();
    }

    /** Roda o backup no fim da requisição, uma vez por dia, sem atrasar quem está usando. */
    public function runIfDue(): void
    {
        if ($this->isDue() && Cache::add('meucash.backup.running', true, now()->addMinutes(30))) {
            dispatch(fn () => app(self::class)->run())->afterResponse();
        }
    }

    public function lastStatus(): ?array
    {
        return Cache::get(self::STATUS_KEY);
    }

    public function recipients(): array
    {
        $list = config('meucash.backup.email') ?: implode(',', config('meucash.platform_admins', []));

        return array_values(array_filter(array_map('trim', explode(',', (string) $list)), fn ($e) => filter_var($e, FILTER_VALIDATE_EMAIL)));
    }

    private function maxAttachmentBytes(): int
    {
        return (int) config('meucash.backup.max_attachment_mb', 8) * 1024 * 1024;
    }

    private function tables(): array
    {
        return collect(Schema::getTables())
            ->pluck('name')
            ->reject(fn ($name) => in_array($name, self::SKIP, true) || str_starts_with($name, 'sqlite_'))
            ->sort()
            ->values()
            ->all();
    }

    private function createStatement(string $table, bool $mysql): string
    {
        if ($mysql) {
            $row = (array) DB::selectOne("SHOW CREATE TABLE `{$table}`");

            return $row['Create Table'] ?? array_values($row)[1];
        }

        return DB::selectOne("SELECT sql FROM sqlite_master WHERE type = 'table' AND name = ?", [$table])->sql;
    }

    private function keepLast(string $dir, int $keep): void
    {
        $files = glob($dir.'/meucash-*.sql.gz') ?: [];
        rsort($files);
        foreach (array_slice($files, $keep) as $old) {
            @unlink($old);
        }
    }
}
