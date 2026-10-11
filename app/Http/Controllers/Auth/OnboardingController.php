<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\CompanyProvisioner;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OnboardingController extends Controller
{
    public function create(Request $request): View|RedirectResponse
    {
        $profile = $request->session()->get('pending_google');

        if (! $profile) {
            return redirect()->route('login');
        }

        return view('auth.onboarding', ['profile' => $profile]);
    }

    public function store(Request $request, CompanyProvisioner $provisioner, LoginController $login): RedirectResponse
    {
        $profile = $request->session()->get('pending_google');

        if (! $profile) {
            return redirect()->route('login')->with('error', 'Sua sessão expirou. Entre com o Google novamente.');
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'document' => ['nullable', 'string', 'max:20'],
            'phone' => ['nullable', 'string', 'max:20'],
            'terms' => ['accepted'],
        ], ['terms.accepted' => 'Aceite os termos para continuar.'], ['name' => 'nome da empresa']);

        if (User::whereRaw('LOWER(email) = ?', [$profile['email']])->exists()) {
            $request->session()->forget('pending_google');

            return redirect()->route('login')->with('error', 'Este e-mail já está vinculado a uma empresa. Entre novamente.');
        }

        $user = $provisioner->create($data, $profile);
        $request->session()->forget('pending_google');

        return $login->loginAndRedirect($request, $user)->with('success', 'Empresa criada. Bem-vindo ao Meu Cash!');
    }
}
