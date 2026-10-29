<?php

namespace Tests\Feature;

use App\Mail\BackupMail;
use App\Mail\ErrorAlertMail;
use App\Models\Customer;
use App\Models\ErrorReport;
use App\Models\User;
use App\Services\BackupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;
use RuntimeException;
use Tests\TestCase;

class BackupErrosTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['meucash.platform_admins' => ['dono@meucash.test'], 'meucash.backup.email' => null, 'meucash.errors.email' => null]);
    }

    public function test_backup_gera_arquivo_completo_e_envia_por_email(): void
    {
        Mail::fake();
        $admin = User::factory()->admin()->create();
        tenant()->run($admin->company, fn () => Customer::create(['name' => "Joana D'Arc"]));

        $path = app(BackupService::class)->dump();
        $sql = gzdecode(file_get_contents($path));
        $this->assertStringContainsString('CREATE TABLE', $sql);
        $this->assertStringContainsString("Joana D''Arc", $sql);
        $this->assertStringNotContainsString('"sessions"', $sql);

        $status = app(BackupService::class)->run();
        $this->assertTrue($status['ok']);
        Mail::assertSent(BackupMail::class, fn ($mail) => $mail->hasTo('dono@meucash.test') && $mail->attach);
        $this->assertFalse(app(BackupService::class)->isDue());
    }

    public function test_backup_fica_pendente_ate_rodar_no_dia(): void
    {
        Cache::forget('meucash.backup.last');
        $this->travelTo(today()->setTime(2, 0));
        $this->assertFalse(app(BackupService::class)->isDue());
        $this->travelTo(today()->setTime(4, 0));
        $this->assertTrue(app(BackupService::class)->isDue());
    }

    public function test_so_o_dono_da_plataforma_baixa_o_backup(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin)->get('/plataforma/backup/baixar')->assertNotFound();

        $dono = User::factory()->admin()->create(['email' => 'dono@meucash.test']);
        $this->actingAs($dono)->get('/plataforma/backup/baixar')->assertOk()->assertDownload();
        $this->actingAs($dono)->get('/plataforma')->assertOk()->assertSee('Backup diário')->assertSee('Monitoramento de erros');
    }

    public function test_erro_fica_registrado_agrupado_e_avisa_uma_vez(): void
    {
        Mail::fake();
        Route::middleware('web')->get('/_teste-erro', fn () => throw new RuntimeException('Deu ruim no teste'));

        $this->get('/_teste-erro')->assertStatus(500);
        $this->get('/_teste-erro')->assertStatus(500);

        $report = ErrorReport::first();
        $this->assertSame(1, ErrorReport::count());
        $this->assertSame(2, $report->count);
        $this->assertSame('Deu ruim no teste', $report->message);
        Mail::assertSent(ErrorAlertMail::class, 1);

        $dono = User::factory()->admin()->create(['email' => 'dono@meucash.test']);
        $this->actingAs($dono)->get('/plataforma/erros')->assertOk()->assertSee('Deu ruim no teste');
        $this->actingAs($dono)->patch("/plataforma/erros/{$report->id}/resolver")->assertSessionHas('success');
        $this->assertNotNull($report->fresh()->resolved_at);

        // Voltou a acontecer: reabre e avisa de novo
        $this->get('/_teste-erro');
        $this->assertNull($report->fresh()->resolved_at);
        Mail::assertSent(ErrorAlertMail::class, 2);
    }

    public function test_erros_comuns_nao_viram_alerta(): void
    {
        $this->get('/pagina-que-nao-existe')->assertNotFound();
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin)->post('/customers', [])->assertSessionHasErrors('name');
        $this->assertSame(0, ErrorReport::count());
    }
}
