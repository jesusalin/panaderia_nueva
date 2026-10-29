<?php

namespace Tests\Feature;

use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** "Mi perfil" (foto, datos, contraseña) y contraseña temporal del admin. */
class PerfilUsuarioTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        Storage::fake('public');
    }

    public function test_cualquier_usuario_edita_su_perfil_y_sube_su_foto(): void
    {
        $u = Usuario::factory()->create();   // sin permisos: igual entra a su perfil

        $this->actingAs($u)->get(route('perfil.edit'))->assertOk()->assertSee('Mi perfil', false);

        $this->actingAs($u)->put(route('perfil.update'), [
            'nombre' => 'Maria Quispe Rojas', 'apodo' => 'Caja 1', 'usuario' => 'maria_q', 'email' => 'maria.q@gmail.com',
            'foto' => UploadedFile::fake()->image('yo.jpg', 300, 300),
        ])->assertRedirect(route('perfil.edit'));

        $u->refresh();
        $this->assertSame('maria.q@gmail.com', $u->email);
        $this->assertSame('Caja 1', $u->apodo);
        $this->assertSame('maria_q', $u->usuario);
        Storage::disk('public')->assertExists($u->foto_perfil);
        $this->actingAs($u)->get($u->foto_url)->assertOk();

        // Quitar la foto la borra del disco
        $ruta = $u->foto_perfil;
        $this->actingAs($u)->put(route('perfil.update'), ['nombre' => $u->nombre, 'usuario' => $u->usuario, 'email' => $u->email, 'quitar_foto' => 1]);
        $this->assertNull($u->fresh()->foto_perfil);
        Storage::disk('public')->assertMissing($ruta);
    }

    public function test_no_acepta_archivos_que_no_son_imagen(): void
    {
        $u = Usuario::factory()->create();
        $this->actingAs($u)->put(route('perfil.update'), [
            'nombre' => $u->nombre, 'usuario' => $u->usuario, 'email' => $u->email,
            'foto' => UploadedFile::fake()->create('virus.pdf', 10, 'application/pdf'),
        ])->assertSessionHasErrors('foto');
    }

    public function test_no_puede_usar_un_usuario_ajeno_ni_con_espacios(): void
    {
        Usuario::factory()->create(['usuario' => 'ocupado']);
        $u = Usuario::factory()->create();
        foreach (['ocupado', 'con espacio'] as $malo) {
            $this->actingAs($u)->put(route('perfil.update'), ['nombre' => $u->nombre, 'usuario' => $malo, 'email' => $u->email])
                ->assertSessionHasErrors('usuario');
        }
    }

    public function test_cambiar_su_contrasena_pide_la_actual(): void
    {
        $u = Usuario::factory()->create();   // contraseña "password"

        $this->actingAs($u)->put(route('perfil.password'), ['password_actual' => 'mala', 'password' => 'nueva123', 'password_confirmation' => 'nueva123'])
            ->assertSessionHasErrors('password_actual');

        $this->actingAs($u)->put(route('perfil.password'), ['password_actual' => 'password', 'password' => 'nueva123', 'password_confirmation' => 'nueva123'])
            ->assertRedirect(route('perfil.edit'));
        $this->assertTrue(Hash::check('nueva123', $u->fresh()->password));
    }

    public function test_el_admin_genera_una_clave_temporal_y_el_usuario_debe_cambiarla(): void
    {
        $admin = Usuario::factory()->admin()->create();
        $u = Usuario::factory()->create(['usuario' => 'olvidadizo']);

        $r = $this->actingAs($admin)->from(route('usuarios.index'))->put(route('usuarios.clave-temporal', $u));
        $clave = session('clave_temporal.clave');
        $r->assertRedirect(route('usuarios.index'));
        $this->assertNotEmpty($clave);
        $this->assertTrue(Hash::check($clave, $u->fresh()->password));   // se guarda cifrada
        $this->assertNotSame($clave, $u->fresh()->password);
        $this->assertTrue($u->fresh()->debe_cambiar_password);

        // Entra con la temporal: lo manda a elegir su contraseña antes de usar el sistema
        auth()->logout();
        $this->post('/login', ['usuario' => 'olvidadizo', 'password' => $clave]);
        $this->get(route('dashboard'))->assertRedirect(route('perfil.edit'));

        $this->put(route('perfil.password'), ['password' => 'mia12345', 'password_confirmation' => 'mia12345'])
            ->assertRedirect(route('perfil.edit'));
        $this->assertFalse($u->fresh()->debe_cambiar_password);
        $this->get(route('dashboard'))->assertOk();
    }

    public function test_un_usuario_normal_no_puede_generar_claves_temporales(): void
    {
        $u = Usuario::factory()->create();
        $otro = Usuario::factory()->create();
        $this->actingAs($u)->put(route('usuarios.clave-temporal', $otro))->assertForbidden();
    }

    public function test_comando_para_cuando_el_admin_olvida_su_clave(): void
    {
        $admin = Usuario::factory()->admin()->create(['usuario' => 'jefe']);
        $this->artisan('usuario:clave', ['usuario' => 'jefe'])->assertSuccessful();
        $this->assertTrue($admin->fresh()->debe_cambiar_password);
        $this->artisan('usuario:clave', ['usuario' => 'nadie'])->assertFailed();
    }
}
