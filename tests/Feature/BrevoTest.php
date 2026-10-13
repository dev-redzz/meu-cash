<?php

namespace Tests\Feature;

use App\Notifications\ResetPasswordNotification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class BrevoTest extends TestCase
{
    use RefreshDatabase;

    public function test_envia_pela_api_da_brevo(): void
    {
        config([
            'mail.default' => 'brevo',
            'mail.mailers.brevo.key' => 'chave-teste',
            'mail.from' => ['address' => 'loja@gmail.com', 'name' => 'Meu Cash'],
        ]);
        Http::fake(['api.brevo.com/*' => Http::response(['messageId' => '<abc@brevo>'], 201)]);

        $user = User::factory()->create(['email' => 'cliente@gmail.com']);
        $user->notify(new ResetPasswordNotification('token'));

        Http::assertSent(fn (Request $r) => $r->url() === 'https://api.brevo.com/v3/smtp/email'
            && $r->header('api-key')[0] === 'chave-teste'
            && $r['sender']['email'] === 'loja@gmail.com'
            && $r['to'][0]['email'] === 'cliente@gmail.com'
            && str_contains($r['htmlContent'], 'Definir nova senha'));
    }

    public function test_erro_da_brevo_aparece_na_previa(): void
    {
        config(['mail.default' => 'brevo', 'mail.mailers.brevo.key' => 'errada', 'mail.from' => ['address' => 'loja@gmail.com', 'name' => 'x']]);
        Http::fake(['api.brevo.com/*' => Http::response(['message' => 'Key not found'], 401)]);

        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->post('/configuracoes/resumo-semanal/previa')->assertSessionHas('error');
    }
}
