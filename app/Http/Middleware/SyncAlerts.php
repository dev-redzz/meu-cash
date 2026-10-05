<?php

namespace App\Http\Middleware;

use App\Services\NotificationService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SyncAlerts
{
    public function __construct(private readonly NotificationService $notifications)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        if ($request->isMethod('GET')) {
            try {
                $this->notifications->syncIfDue();
            } catch (\Throwable $e) {
                report($e);
            }
        }

        return $next($request);
    }
}
