<?php

namespace App\Http\Middleware;

use App\Services\Payments\SubscriptionService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Assinatura vencida (passada a tolerância): a loja continua vendo e exportando os dados,
 * mas não consegue cadastrar, vender nem alterar nada até pagar.
 */
class EnsureActivePlan
{
    private const ALLOWED = ['billing.*', 'logout', 'export.*', 'platform.*', 'feedback.store', 'notifications.*'];

    public function __construct(private readonly SubscriptionService $billing)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $company = tenant()->company();

        if (! $company || ! $this->billing->enabled()) {
            return $next($request);
        }

        $status = $this->billing->status($company);
        view()->share('plan', $status);

        if ($status['blocked'] && ! $request->isMethodSafe() && ! $request->routeIs(...self::ALLOWED)) {
            $message = 'Sua assinatura venceu. Você ainda pode ver e exportar seus dados; para voltar a vender, renove a assinatura.';

            if ($request->expectsJson()) {
                return response()->json(['message' => $message], 402);
            }

            return redirect()->route('billing.show')->with('error', $message);
        }

        return $next($request);
    }
}
