<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class IdentifyCompany
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $company = $user?->company;

        if (! $user || ! $user->active || ! $company || ! $company->active) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            $message = $company && ! $company->active
                ? 'O acesso desta empresa está suspenso. Entre em contato com o suporte.'
                : 'Seu acesso não está ativo. Fale com o administrador da sua empresa.';

            return redirect()->route('login')->with('error', $message);
        }

        tenant()->set($company);

        return $next($request);
    }
}
