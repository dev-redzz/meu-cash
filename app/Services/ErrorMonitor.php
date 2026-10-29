<?php

namespace App\Services;

use App\Mail\ErrorAlertMail;
use App\Models\ErrorReport;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Throwable;

/**
 * Guarda cada erro do sistema (agrupando os iguais) e avisa o dono por e-mail.
 * Nunca pode quebrar a página: qualquer falha aqui é ignorada.
 */
class ErrorMonitor
{
    private static bool $busy = false;

    public function record(Throwable $e): void
    {
        if (self::$busy) {
            return;
        }

        self::$busy = true;

        try {
            if (! Schema::hasTable('error_reports')) {
                return;
            }

            $file = $e->getFile();
            $fingerprint = sha1(get_class($e).'|'.$file.'|'.$e->getLine());
            $request = app()->runningInConsole() ? null : request();
            $now = now();

            $report = ErrorReport::firstOrNew(['fingerprint' => $fingerprint]);
            $isNew = ! $report->exists || $report->resolved_at !== null;

            $report->fill([
                'class' => get_class($e),
                'message' => Str::limit($e->getMessage() ?: '(sem mensagem)', 2000, ''),
                'file' => $file,
                'line' => $e->getLine(),
                'url' => $request ? Str::limit($request->fullUrl(), 490, '') : 'artisan '.implode(' ', array_slice($_SERVER['argv'] ?? [], 1, 3)),
                'method' => $request?->method(),
                'user_id' => auth()->id(),
                'company_id' => tenant_id(),
                'trace' => Str::limit($e->getTraceAsString(), 6000, "\n..."),
                'last_seen_at' => $now,
                'resolved_at' => null,
            ]);
            $report->first_seen_at ??= $now;
            $report->count = $report->exists ? $report->count + 1 : 1;

            $notify = config('meucash.errors.notify')
                && ($isNew || ! $report->notified_at || $report->notified_at->lt($now->copy()->subHour()));

            if ($notify) {
                $report->notified_at = $now;
            }

            $report->save();

            if ($notify && $to = $this->recipients()) {
                $send = function () use ($to, $report) {
                    try {
                        Mail::to($to)->send(new ErrorAlertMail($report));
                    } catch (Throwable) {
                    }
                };

                app()->runningInConsole() ? $send() : dispatch($send)->afterResponse();
            }
        } catch (Throwable) {
        } finally {
            self::$busy = false;
        }
    }

    public function recipients(): array
    {
        $list = config('meucash.errors.email') ?: implode(',', config('meucash.platform_admins', []));

        return array_values(array_filter(array_map('trim', explode(',', (string) $list)), fn ($e) => filter_var($e, FILTER_VALIDATE_EMAIL)));
    }
}
