<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\CompanyInvitation;
use App\Models\User;
use App\Services\CompanyProvisioner;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RegisterController extends Controller
{
    public function create(): View
    {
        return view('auth.register');
    }

    public function store(Request $request, CompanyProvisioner $provisioner, LoginController $login): RedirectResponse
    {
        $request->merge(['email' => mb_strtolower(trim((string) $request->input('email')))]);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'company' => ['nullable', 'string', 'max:120'],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
            'terms' => ['accepted'],
        ], [
            'email.unique' => 'Já existe uma conta com este e-mail. Entre ou use "Esqueci minha senha".',
            'terms.accepted' => 'Aceite os termos para continuar.',
        ], ['name' => 'seu nome', 'email' => 'e-mail', 'company' => 'nome da empresa', 'password' => 'senha']);

        $invitation = CompanyInvitation::pendingFor($data['email']);

        if ($invitation) {
            $user = DB::transaction(function () use ($invitation, $data) {
                $invitation->update(['accepted_at' => now()]);

                return User::create([
                    'company_id' => $invitation->company_id,
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'password' => $data['password'],
                    'role' => $invitation->role,
                    'active' => true,
                ]);
            });

            return $login->loginAndRedirect($request, $user)->with('success', 'Conta criada. Você entrou na empresa que te convidou.');
        }

        if (blank($data['company'])) {
            throw ValidationException::withMessages(['company' => 'Informe o nome da empresa.']);
        }

        $user = $provisioner->create(['name' => $data['company']], [
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
        ]);

        return $login->loginAndRedirect($request, $user)->with('success', 'Conta criada. Bem-vindo ao Meu Cash!');
    }
}
