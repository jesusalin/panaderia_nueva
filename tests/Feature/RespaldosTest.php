<?php

namespace Tests\Feature;

use App\Models\Usuario;
use App\Support\Respaldos;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class RespaldosTest extends TestCase
{
    use RefreshDatabase;

    private Usuario $admin;
    private string $carpeta;
    private string $base;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->admin = Usuario::factory()->admin()->create(['nombre' => 'Jefa']);
        // Carpeta y "base de datos" de prueba: el respaldo copia la conexión de las pruebas (en memoria)
        $this->carpeta = storage_path('framework/testing/respaldos-' . uniqid());
        $this->base = $this->carpeta . '-db.sqlite';
        File::put($this->base, 'SQLite format 3' . str_repeat("\0", 100));
        config(['backup.carpeta' => $this->carpeta, 'database.connections.sqlite.database' => $this->base]);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->carpeta);
        File::delete($this->base);
        parent::tearDown();
    }

    public function test_generar_con_nota_verificar_proteger_y_rotar(): void
    {
        $this->actingAs($this->admin)->post(route('backups.store'), ['nota' => 'Antes del inventario'])->assertSessionHas('success');
        $r = Respaldos::lista()->first();
        $this->assertSame('manual', $r['tipo']);
        $this->assertSame('Jefa', $r['usuario']);
        $this->assertSame('Antes del inventario', $r['nota']);
        $this->assertSame(Usuario::count(), $r['resumen']['usuarios']);
        $this->assertTrue(Respaldos::verificar($this->carpeta . '/' . $r['nombre'])[0]);

        $this->actingAs($this->admin)->post(route('backups.verificar', $r['nombre']))->assertSessionHas('success');
        $this->actingAs($this->admin)->post(route('backups.proteger', $r['nombre']));
        $this->actingAs($this->admin)->delete(route('backups.destroy', $r['nombre']))->assertSessionHas('error');   // protegido
        $this->assertFileExists($this->carpeta . '/' . $r['nombre']);

        // La limpieza automática no borra los protegidos
        config(['backup.mantener' => 1]);
        sleep(1); $this->artisan('backup:run')->assertSuccessful();
        sleep(1); $this->artisan('backup:run')->assertSuccessful();
        $lista = Respaldos::lista();
        $this->assertCount(2, $lista);                                   // 1 automático + el protegido
        $this->assertTrue($lista->contains('nombre', $r['nombre']));
        $this->assertSame('automatico', $lista->first()['tipo']);

        $this->actingAs($this->admin)->get(route('backups.index'))->assertOk()
            ->assertSee('Antes del inventario')->assertSee('Protegido')->assertSee('Verificado')->assertDontSee('El respaldo automático no se está haciendo');
        // un nombre con ../ no sale de la carpeta
        $this->actingAs($this->admin)->get(route('backups.download', '..%2F..%2F.env'))->assertNotFound();
    }

    public function test_restaurar_pide_la_palabra_y_guarda_copia_antes(): void
    {
        $this->actingAs($this->admin)->post(route('backups.store'));
        $r = Respaldos::lista()->first();
        $this->actingAs($this->admin)->post(route('backups.restore', $r['nombre']), ['confirmar' => 'si'])->assertSessionHas('error');
        $this->assertCount(1, Respaldos::lista());

        File::put($this->base, 'cambiado');
        sleep(1);
        $this->actingAs($this->admin)->post(route('backups.restore', $r['nombre']), ['confirmar' => 'restaurar'])->assertSessionHas('success');
        $this->assertStringStartsWith('SQLite format 3', File::get($this->base));     // volvió el contenido del respaldo
        $antes = Respaldos::lista()->firstWhere('tipo', 'antes_restaurar');
        $this->assertNotNull($antes);
        $this->assertNotNull(Respaldos::lista()->firstWhere('nombre', $r['nombre'])['restaurado']);
    }

    public function test_subir_un_respaldo(): void
    {
        $this->actingAs($this->admin)->post(route('backups.subir'), ['archivo' => UploadedFile::fake()->createWithContent('cualquier.txt', 'hola')])->assertSessionHasErrors('archivo');
        $this->actingAs($this->admin)->post(route('backups.subir'), ['archivo' => UploadedFile::fake()->createWithContent('roto.zip', 'no es zip')])->assertSessionHasErrors('archivo');
        $this->assertCount(0, Respaldos::lista());

        $zip = new \ZipArchive();
        $tmp = $this->carpeta . '-sub.zip';
        $zip->open($tmp, \ZipArchive::CREATE);
        $zip->addFromString('database.sqlite', 'SQLite format 3' . str_repeat("\0", 50));
        $zip->close();
        $this->actingAs($this->admin)->post(route('backups.subir'), ['archivo' => new UploadedFile($tmp, 'mi-respaldo.zip', null, null, true)])->assertSessionHas('success');
        File::delete($tmp);
        $s = Respaldos::lista()->first();
        $this->assertSame('subido', $s['tipo']);
        $this->assertTrue($s['verificado']['ok']);
        $this->assertStringContainsString('mi-respaldo.zip', $s['nota']);
    }
}
