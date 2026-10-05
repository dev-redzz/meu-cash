<?php

namespace App\Http\Controllers\Settings;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\UserRequest;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(): View
    {
        return view('settings.users.index', ['users' => User::orderBy('name')->get()]);
    }

    public function create(): View
    {
        return view('settings.users.form', ['user' => new User(['role' => UserRole::Funcionario, 'active' => true])]);
    }

    public function store(UserRequest $request): RedirectResponse
    {
        $user = User::create([...$request->safe()->except('password_confirmation'), 'active' => $request->boolean('active')]);
        AuditService::log('usuario_criado', "Usuário {$user->email} criado como {$user->role->label()}", $user);

        return redirect()->route('users.index')->with('success', 'Usuário criado.');
    }

    public function edit(User $user): View
    {
        return view('settings.users.form', compact('user'));
    }

    public function update(UserRequest $request, User $user): RedirectResponse
    {
        $data = $request->safe()->except(['password', 'password_confirmation']);
        $data['active'] = $request->boolean('active');

        if ($user->is(auth()->user())) {
            $data['active'] = true;
            $data['role'] = UserRole::Admin->value;
        }

        if ($request->filled('password')) {
            $data['password'] = $request->input('password');
        }

        $user->update($data);
        AuditService::log('usuario_editado', "Usuário {$user->email} editado", $user);

        return redirect()->route('users.index')->with('success', 'Usuário atualizado.');
    }
}
