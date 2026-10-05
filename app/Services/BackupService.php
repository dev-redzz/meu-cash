<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use RuntimeException;

class BackupService
{
    private const STRUCTURE_ONLY = ['sessions', 'cache', 'cache_locks', 'jobs', 'job_batches', 'failed_jobs'];

    public function directory(): string
    {
        $path = storage_path('app/backups');
        File::ensureDirectoryExists($path);

        return $path;
    }

    public function supported(): bool
    {
        return in_array(DB::connection()->getDriverName(), ['mysql', 'mariadb'], true);
    }

    public function list(): array
    {
        return collect(File::files($this->directory()))
            ->filter(fn ($file) => $file->getExtension() === 'sql')
            ->sortByDesc(fn ($file) => $file->getMTime())
            ->map(fn ($file) => [
                'name' => $file->getFilename(),
                'size' => $file->getSize(),
                'date' => date('d/m/Y H:i', $file->getMTime()),
            ])
            ->values()
            ->all();
    }

    public function create(): string
    {
        if (! $this->supported()) {
            throw new RuntimeException('O backup pelo sistema funciona apenas com MySQL/MariaDB.');
        }

        @set_time_limit(300);
        $pdo = DB::connection()->getPdo();
        $database = DB::connection()->getDatabaseName();
        $file = $this->directory().DIRECTORY_SEPARATOR.'meu_cash_'.date('Y-m-d_His').'.sql';
        $handle = fopen($file, 'w');

        fwrite($handle, "-- Backup Meu Cash\n-- Banco: {$database}\n-- Gerado em: ".date('d/m/Y H:i:s')."\n\n");
        fwrite($handle, "SET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS=0;\n\n");

        $tables = array_map(fn ($row) => array_values((array) $row)[0], DB::select("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'"));

        foreach ($tables as $table) {
            $create = (array) DB::selectOne("SHOW CREATE TABLE `{$table}`");
            fwrite($handle, "DROP TABLE IF EXISTS `{$table}`;\n".array_values($create)[1].";\n\n");

            if (in_array($table, self::STRUCTURE_ONLY, true)) {
                continue;
            }

            DB::table($table)->orderByRaw('1')->chunk(500, function ($rows) use ($handle, $table, $pdo) {
                $values = [];
                $columns = null;

                foreach ($rows as $row) {
                    $row = (array) $row;
                    $columns ??= '`'.implode('`, `', array_keys($row)).'`';
                    $values[] = '('.implode(', ', array_map(fn ($v) => $v === null ? 'NULL' : $pdo->quote((string) $v), $row)).')';
                }

                if ($values) {
                    fwrite($handle, "INSERT INTO `{$table}` ({$columns}) VALUES\n".implode(",\n", $values).";\n");
                }
            });

            fwrite($handle, "\n");
        }

        fwrite($handle, "SET FOREIGN_KEY_CHECKS=1;\n");
        fclose($handle);

        AuditService::log('backup_gerado', basename($file));

        return basename($file);
    }

    public function path(string $name): string
    {
        $name = basename($name);
        $path = $this->directory().DIRECTORY_SEPARATOR.$name;

        if (! preg_match('/^[\w\-.]+\.sql$/', $name) || ! File::exists($path)) {
            throw new RuntimeException('Arquivo de backup não encontrado.');
        }

        return $path;
    }

    public function restore(UploadedFile|string $source): void
    {
        if (! $this->supported()) {
            throw new RuntimeException('A restauração pelo sistema funciona apenas com MySQL/MariaDB.');
        }

        @set_time_limit(600);
        $sql = $source instanceof UploadedFile ? $source->get() : File::get($this->path($source));

        if (! str_contains((string) $sql, 'CREATE TABLE')) {
            throw new RuntimeException('O arquivo enviado não parece ser um backup SQL válido.');
        }

        DB::unprepared($sql);
        AuditService::log('backup_restaurado', $source instanceof UploadedFile ? $source->getClientOriginalName() : $source);
    }

    public function delete(string $name): void
    {
        File::delete($this->path($name));
    }
}
