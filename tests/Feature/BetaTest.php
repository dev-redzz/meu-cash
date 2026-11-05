<?php

namespace Tests\Feature;

use App\Mail\FeedbackMail;
use App\Models\Feedback;
use App\Models\User;
use App\Support\Period;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class BetaTest extends TestCase
{
    use RefreshDatabase;

    public function test_usuario_envia_sugestao_e_dono_ve_no_painel(): void
    {
        Mail::fake();
        config(['meucash.platform_admins' => ['dono@meucash.test'], 'meucash.errors.email' => null]);
        $func = User::factory()->create();

        $this->actingAs($func)->get('/dashboard')->assertSee('Beta')->assertSee('Enviar sugestão');
        $this->actingAs($func)->post('/sugestoes', ['type' => 'sugestao', 'message' => 'Queria filtro por vendedor', 'page' => '/sales'])
            ->assertSessionHas('success');

        $this->assertSame(1, Feedback::count());
        Mail::assertSent(FeedbackMail::class, fn ($m) => $m->hasTo('dono@meucash.test'));

        $dono = User::factory()->admin()->create(['email' => 'dono@meucash.test']);
        $this->actingAs($dono)->get('/plataforma/sugestoes')->assertOk()->assertSee('Queria filtro por vendedor');
        $this->actingAs($dono)->post('/plataforma/sugestoes/lidas')->assertSessionHas('success');
        $this->assertNotNull(Feedback::first()->read_at);
        $this->actingAs($func)->get('/plataforma/sugestoes')->assertNotFound();
    }

    public function test_comparacao_usa_o_mesmo_trecho_do_periodo_anterior(): void
    {
        $this->travelTo(now()->setDate(2026, 11, 3)->setTime(10, 0));
        $compare = Period::fromRequest(new Request(['periodo' => 'mes']))->comparable();
        $this->assertSame('2026-10-01', $compare->startDate());
        $this->assertSame('2026-10-03', $compare->endDate());

        $year = Period::fromRequest(new Request(['periodo' => 'ano']))->comparable();
        $this->assertSame('2025-01-01', $year->startDate());
        $this->assertSame('2025-11-03', $year->endDate());
    }

    public function test_lucro_desejado_fica_nas_configuracoes_e_aparece_no_cadastro(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin)->get('/products/create')->assertOk()->assertSee('Preço sugerido')->assertSee('value="40"', false);
    }
}
