<?php

namespace Tests\Feature;

use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** "Mi perfil" (foto y datos) y contraseñas: solo el admin las cambia. */
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

    public function test_un_usuario_normal_no_puede_cambiar_su_contrasena(): void
    {
        $u = Usuario::factory()->create();   // contraseña "password"

        $this->actingAs($u)->get(route('perfil.edit'))->assertOk()
            ->assertDontSee('Cambiar mi contraseña')->assertSee('pídeselo');
        $this->actingAs($u)->put(route('perfil.password'), ['password_actual' => 'password', 'password' => 'nueva123', 'password_confirmation' => 'nueva123'])
            ->assertForbidden();
        $this->assertTrue(Hash::check('password', $u->fresh()->password));
    }

    public function test_el_admin_cambia_su_contrasena_pidiendo_la_actual(): void
    {
        $admin = Usuario::factory()->admin()->create();

        $this->actingAs($admin)->put(route('perfil.password'), ['password_actual' => 'mala', 'password' => 'nueva123', 'password_confirmation' => 'nueva123'])
            ->assertSessionHasErrors('password_actual');
        $this->actingAs($admin)->put(route('perfil.password'), ['password_actual' => 'password', 'password' => 'nueva123', 'password_confirmation' => 'nueva123'])
            ->assertRedirect(route('perfil.edit'));
        $this->assertTrue(Hash::check('nueva123', $admin->fresh()->password));
    }

    public function test_el_admin_genera_una_contrasena_nueva_para_quien_la_olvido(): void
    {
        $admin = Usuario::factory()->admin()->create();
        $u = Usuario::factory()->create(['usuario' => 'olvidadizo']);

        $r = $this->actingAs($admin)->from(route('usuarios.index'))->put(route('usuarios.clave-temporal', $u));
        $clave = session('clave_temporal.clave');
        $r->assertRedirect(route('usuarios.index'));
        $this->assertNotEmpty($clave);
        $this->assertTrue(Hash::check($clave, $u->fresh()->password));   // se guarda cifrada
        $this->assertNotSame($clave, $u->fresh()->password);

        // Entra con la nueva y usa el sistema normalmente
        auth()->logout();
        $this->post('/login', ['usuario' => 'olvidadizo', 'password' => $clave]);
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
        $vieja = $admin->password;
        $this->artisan('usuario:clave', ['usuario' => 'jefe'])->assertSuccessful();
        $this->assertNotSame($vieja, $admin->fresh()->password);
        $this->artisan('usuario:clave', ['usuario' => 'nadie'])->assertFailed();
    }
}
