<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SiteTest extends TestCase
{
    use RefreshDatabase;

    public function test_site_de_apresentacao_e_paginas_legais(): void
    {
        $this->get('/')->assertOk()->assertSee('Criar conta da loja')->assertSee('img/site/painel.webp', false)->assertSee(route('terms'), false);
        $this->get('/termos')->assertOk()->assertSee('Termos de Uso')->assertSee('Lei aplicável');
        $this->get('/privacidade')->assertOk()->assertSee('Política de Privacidade')->assertSee('Encarregado');

        $user = User::factory()->admin()->create();
        $this->actingAs($user)->get('/')->assertRedirect(route('dashboard'));
    }

    public function test_atalhos_aparecem_no_painel_conforme_o_perfil(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin)->get('/dashboard')->assertOk()->assertSee('shortcuts-data', false)->assertSee('Atalhos do teclado')->assertSee(route('finance.index'), false);

        $func = User::factory()->create(['company_id' => $admin->company_id]);
        $html = $this->actingAs($func)->get('/dashboard')->assertOk()->getContent();
        preg_match('#<script type="application/json" id="shortcuts-data">(.*?)</script>#s', $html, $m);
        $data = json_decode($m[1], true);
        $this->assertArrayNotHasKey('f', $data['go']);
        $this->assertArrayHasKey('n', $data['new']);
    }
}
