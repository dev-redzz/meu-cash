<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('meucash:alertas')->dailyAt('07:00');
Schedule::command('meucash:resumo-semanal')->weeklyOn(1, '08:00');
Schedule::call(fn () => app(\App\Services\UndoService::class)->purgeExpired())->everyTenMinutes()->name('limpar-desfazer');
