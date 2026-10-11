<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FotoTest extends TestCase
{
    use RefreshDatabase;

    public function test_foto_aparece_so_para_a_propria_empresa(): void
    {
        Storage::fake('public');
        $dono = User::factory()->admin()->create();
        $outro = User::factory()->admin()->create();

        $this->actingAs($dono)->post('/products', [
            'name' => 'Capinha', 'purchase_price' => '5', 'sale_price' => '20', 'quantity' => 3,
            'status' => 'disponivel', 'photos' => [UploadedFile::fake()->image('capa.png')],
        ])->assertSessionHasNoErrors();

        tenant()->set($dono->company);
        $url = Product::first()->photoUrls()[0];
        tenant()->set(null);

        $this->actingAs($dono)->get($url)->assertOk();
        $this->actingAs($outro)->get($url)->assertNotFound();
        $this->actingAs($dono)->get('/fotos/products/../../.env')->assertNotFound();
    }
}
