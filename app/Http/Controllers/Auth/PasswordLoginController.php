<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PasswordLoginController extends Controller
{
    public function store(Request $request, LoginController $login): RedirectResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ], [], ['email' => 'e-mail', 'password' => 'senha']);

        $key = 'login:'.Str::lower($data['email']).'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages([
                'email' => 'Muitas tentativas. Tente novamente em '.RateLimiter::availableIn($key).' segundos.',
            ]);
        }

        $user = User::whereRaw('LOWER(email) = ?', [Str::lower($data['email'])])->first();

        if (! $user || ! $user->hasPassword() || ! Hash::check($data['password'], $user->password)) {
            RateLimiter::hit($key, 60);

            $message = $user && ! $user->hasPassword()
                ? 'Esta conta ainda não tem senha. Entre com o Google ou use "Esqueci minha senha" para criar uma.'
                : 'E-mail ou senha incorretos.';

            throw ValidationException::withMessages(['email' => $message]);
        }

        RateLimiter::clear($key);

        if (! $user->active) {
            throw ValidationException::withMessages(['email' => 'Seu acesso foi desativado. Fale com o administrador da sua empresa.']);
        }

        return $login->loginAndRedirect($request, $user, $request->boolean('remember'));
    }
}
