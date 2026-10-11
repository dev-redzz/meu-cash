<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\CompanyInvitation;
use App\Models\User;
use App\Services\AuditService;
use App\Services\GoogleAuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use RuntimeException;

class LoginController extends Controller
{
    public function __construct(private readonly GoogleAuthService $google)
    {
    }

    public function create(): View
    {
        return view('auth.login');
    }

    public function redirect(Request $request): RedirectResponse
    {
        if (! $this->google->configured()) {
            return redirect()->route('login')->with('error', 'O login com Google ainda não foi configurado no servidor (GOOGLE_CLIENT_ID e GOOGLE_CLIENT_SECRET).');
        }

        $state = $this->google->newState();
        $request->session()->put('google_state', $state);

        return redirect()->away($this->google->authorizationUrl($state));
    }

    public function callback(Request $request): RedirectResponse
    {
        $expected = $request->session()->pull('google_state');

        if ($request->filled('error') || blank($request->input('code')) || ! $expected || ! hash_equals($expected, (string) $request->input('state'))) {
            return redirect()->route('login')->with('error', 'Login cancelado ou expirado. Tente novamente.');
        }

        try {
            $profile = $this->google->userFromCode($request->input('code'));
        } catch (RuntimeException $e) {
            return redirect()->route('login')->with('error', $e->getMessage());
        }

        $user = User::where('google_id', $profile['google_id'])->first()
            ?? User::whereRaw('LOWER(email) = ?', [$profile['email']])->first();

        if (! $user && ($invitation = CompanyInvitation::pendingFor($profile['email']))) {
            $user = DB::transaction(function () use ($invitation, $profile) {
                $invitation->update(['accepted_at' => now()]);

                return User::create([
                    'company_id' => $invitation->company_id,
                    'name' => $profile['name'],
                    'email' => $profile['email'],
                    'role' => $invitation->role,
                    'active' => true,
                ]);
            });
        }

        if (! $user) {
            $request->session()->put('pending_google', $profile);

            return redirect()->route('onboarding.create');
        }

        if (! $user->active) {
            return redirect()->route('login')->with('error', 'Seu acesso foi desativado. Fale com o administrador da sua empresa.');
        }

        $user->forceFill([
            'google_id' => $profile['google_id'],
            'avatar' => $profile['avatar'],
            'name' => $user->name ?: $profile['name'],
        ])->save();

        return $this->loginAndRedirect($request, $user);
    }

    public function loginAndRedirect(Request $request, User $user): RedirectResponse
    {
        Auth::login($user, true);
        $request->session()->regenerate();

        if ($user->company) {
            tenant()->set($user->company);
            AuditService::log('login', 'Entrou com Google');
        }

        return redirect()->intended(route('dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        if (tenant()->check()) {
            AuditService::log('logout', 'Saiu do sistema');
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
