<?php

namespace App\Http\Middleware;

use App\Models\Customer;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Área do cliente: identifica o cliente pelo código guardado na sessão
 * e limita todas as consultas à empresa dele.
 */
class EnsurePortalCustomer
{
    public function handle(Request $request, Closure $next): Response
    {
        $id = $request->session()->get('portal.customer_id');
        $fingerprint = $request->session()->get('portal.code_hash');

        $customer = $id ? Customer::withoutGlobalScopes()->with('company')->find($id) : null;

        $valid = $customer
            && $customer->company?->active
            && $customer->access_code
            && hash_equals((string) $fingerprint, hash('sha256', $customer->access_code));

        if (! $valid) {
            $request->session()->forget(['portal.customer_id', 'portal.code_hash']);

            return redirect()->route('portal.login')->with('error', $customer ? 'Seu código de acesso mudou. Entre com o novo código.' : null);
        }

        tenant()->set($customer->company);
        $request->attributes->set('portalCustomer', $customer);
        view()->share('portalCustomer', $customer);
        view()->share('portalCompany', $customer->company);

        $response = $next($request);
        $response->headers->set('Cache-Control', 'no-store, private');

        return $response;
    }
}
