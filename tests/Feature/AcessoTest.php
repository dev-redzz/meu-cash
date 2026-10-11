<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AcessoTest extends TestCase
{
    use RefreshDatabase;

    private function cadastro(array $dados = []): array
    {
        return array_merge([
            'name' => 'Paula Lima',
            'company' => 'Loja da Paula',
            'email' => 'paula@loja.com',
            'password' => 'senha1234',
            'password_confirmation' => 'senha1234',
            'terms' => '1',
        ], $dados);
    }

    public function test_criar_conta_cria_empresa_e_entra(): void
    {
        $this->post('/criar-conta', $this->cadastro())->assertRedirect(route('dashboard'));

        $user = User::firstWhere('email', 'paula@loja.com');
        $this->assertTrue($user->isAdmin());
        $this->assertSame('Loja da Paula', $user->company->name);
        $this->assertAuthenticatedAs($user);
    }

    public function test_criar_conta_exige_empresa_sem_convite(): void
    {
        $this->post('/criar-conta', $this->cadastro(['company' => '']))->assertSessionHasErrors('company');
        $this->assertGuest();
    }

    public function test_criar_conta_com_convite_entra_na_empresa(): void
    {
        $dono = User::factory()->admin()->create();
        $this->actingAs($dono)->post('/equipe/convites', ['email' => 'paula@loja.com', 'role' => 'funcionario']);
        auth()->logout();

        $this->post('/criar-conta', $this->cadastro(['company' => '']))->assertRedirect(route('dashboard'));

        $paula = User::firstWhere('email', 'paula@loja.com');
        $this->assertSame($dono->company_id, $paula->company_id);
        $this->assertFalse($paula->isAdmin());
    }

    public function test_login_com_senha(): void
    {
        $this->post('/criar-conta', $this->cadastro());
        auth()->logout();

        $this->post('/login', ['email' => 'Paula@Loja.com', 'password' => 'errada123'])->assertSessionHasErrors('email');
        $this->assertGuest();

        $this->post('/login', ['email' => 'Paula@Loja.com', 'password' => 'senha1234'])->assertRedirect(route('dashboard'));
        $this->assertAuthenticated();
    }

    public function test_esqueci_senha_envia_link_e_redefine(): void
    {
        Notification::fake();
        $user = User::factory()->admin()->create(['email' => 'dono@loja.com']);

        $this->post('/esqueci-minha-senha', ['email' => 'dono@loja.com'])->assertSessionHas('success');

        $token = null;
        Notification::assertSentTo($user, ResetPasswordNotification::class, function ($n) use (&$token) {
            $token = $n->token;

            return true;
        });

        $this->post('/redefinir-senha', [
            'token' => $token, 'email' => 'dono@loja.com',
            'password' => 'novaSenha9', 'password_confirmation' => 'novaSenha9',
        ])->assertRedirect(route('login'));

        $this->post('/login', ['email' => 'dono@loja.com', 'password' => 'novaSenha9'])->assertRedirect(route('dashboard'));
    }

    public function test_email_inexistente_nao_revela_nada(): void
    {
        $this->post('/esqueci-minha-senha', ['email' => 'ninguem@x.com'])->assertSessionHas('success');
    }

    public function test_google_anula_senha_de_conta_nao_verificada_com_mesmo_email(): void
    {
        $this->post('/criar-conta', $this->cadastro(['email' => 'vitima@gmail.com']));
        auth()->logout();

        config(['services.google.client_id' => 'id', 'services.google.client_secret' => 'segredo']);
        Http::fake([
            'oauth2.googleapis.com/*' => Http::response(['access_token' => 't']),
            'openidconnect.googleapis.com/*' => Http::response(['sub' => '77', 'email' => 'vitima@gmail.com', 'email_verified' => true, 'name' => 'Vítima']),
        ]);

        $this->withSession(['google_state' => 'e'])->get('/auth/google/callback?code=c&state=e')->assertRedirect(route('dashboard'));
        auth()->logout();

        $this->post('/login', ['email' => 'vitima@gmail.com', 'password' => 'senha1234'])->assertSessionHasErrors('email');
    }
}
