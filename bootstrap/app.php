<?php

use App\Http\Middleware\EnsurePlatformAdmin;
use App\Http\Middleware\EnsureUserHasRole;
use App\Http\Middleware\IdentifyCompany;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\SyncAlerts;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*');
        $middleware->encryptCookies(except: ['cookie_notice']);
        $middleware->alias([
            'company' => IdentifyCompany::class,
            'role' => EnsureUserHasRole::class,
            'alerts' => SyncAlerts::class,
            'platform' => EnsurePlatformAdmin::class,
            'portal' => \App\Http\Middleware\EnsurePortalCustomer::class,
        ]);
        $middleware->web(append: [SecurityHeaders::class]);
        $middleware->prependToPriorityList(
            before: \Illuminate\Routing\Middleware\SubstituteBindings::class,
            prepend: IdentifyCompany::class,
        );
        $middleware->redirectGuestsTo('/login');
        $middleware->redirectUsersTo('/dashboard');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Monitoramento: guarda o erro e avisa o dono por e-mail (o log normal continua).
        $exceptions->report(function (Throwable $e) {
            app(\App\Services\ErrorMonitor::class)->record($e);
        });
    })->create();
