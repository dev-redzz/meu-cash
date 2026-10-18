<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Plano vencido (depois da tolerância): a empresa continua vendo tudo,
 * mas não consegue criar, editar nem apagar até renovar.
 */
class EnsureActivePlan
{
    private const ALWAYS_ALLOWED = ['billing.*', 'logout', 'notifications.*', 'platform.*'];

    public function handle(Request $request, Closure $next): Response
    {
        $company = tenant()->company();
        $user = $request->user();

        $readOnly = $company && ! $company->planActive() && ! $user?->isPlatformAdmin();
        view()->share('planReadOnly', $readOnly);

        if (! $readOnly || $request->isMethodSafe() || $request->routeIs(...self::ALWAYS_ALLOWED)) {
            return $next($request);
        }

        $message = $user?->isAdmin()
            ? 'Seu plano venceu. Renove em Plano para voltar a cadastrar e vender.'
            : 'O plano da empresa venceu. Peça ao administrador para renovar.';

        if ($request->expectsJson()) {
            return response()->json(['message' => $message], 402);
        }

        return redirect()->to(url()->previous(route('dashboard')))->with('error', $message);
    }
}
