<?php

namespace App\Http\Controllers\Settings;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\CompanyInvitation;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(): View
    {
        return view('settings.users.index', [
            'users' => User::where('company_id', tenant_id())->orderBy('name')->get(),
            'invitations' => CompanyInvitation::pending()->with('inviter')->latest()->get(),
        ]);
    }

    public function invite(Request $request): RedirectResponse
    {
        $request->merge(['email' => mb_strtolower(trim((string) $request->input('email')))]);

        $data = $request->validate([
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'role' => ['required', Rule::enum(UserRole::class)],
        ], ['email.unique' => 'Este e-mail já tem acesso ao Meu Cash (nesta ou em outra empresa).'], ['email' => 'e-mail', 'role' => 'perfil']);

        CompanyInvitation::updateOrCreate(
            ['email' => $data['email']],
            ['role' => $data['role'], 'invited_by' => auth()->id(), 'accepted_at' => null],
        );

        AuditService::log('usuario_criado', "Convite enviado para {$data['email']}");

        return back()->with('success', "Pronto. Peça para {$data['email']} entrar no Meu Cash com essa conta Google.");
    }

    public function cancelInvite(CompanyInvitation $invitation): RedirectResponse
    {
        $invitation->delete();

        return back()->with('success', 'Convite cancelado.');
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        abort_unless($user->company_id === tenant_id(), 404);

        if ($user->is(auth()->user())) {
            return back()->with('error', 'Você não pode alterar o próprio perfil ou desativar a si mesmo.');
        }

        $data = $request->validate([
            'role' => ['required', Rule::enum(UserRole::class)],
            'active' => ['required', 'boolean'],
        ]);

        $user->update($data);
        AuditService::log('usuario_editado', "Acesso de {$user->email} alterado", $user);

        return back()->with('success', 'Acesso atualizado.');
    }
}
