<?php

namespace App\Http\Middleware;

use App\Services\NotificationService;
use App\Services\WeeklySummaryService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SyncAlerts
{
    public function __construct(
        private readonly NotificationService $notifications,
        private readonly WeeklySummaryService $weeklySummary,
    ) {
    }

    public function handle(Request $request, Closure $next): Response
    {
        if ($request->isMethod('GET')) {
            try {
                $this->notifications->syncIfDue();

                if ($this->weeklySummary->isDue()) {
                    $company = tenant()->company();
                    dispatch(function () use ($company) {
                        tenant()->run($company, fn () => app(WeeklySummaryService::class)->sendForCurrentCompany());
                    })->afterResponse();
                }
            } catch (\Throwable $e) {
                report($e);
            }
        }

        return $next($request);
    }
}
