<?php

namespace Tests\Feature;

use App\Models\ActividadUsuario;
use App\Models\Rol;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UsuariosTest extends TestCase
{
    use RefreshDatabase;

    private Usuario $admin;
    private int $rolUsuario;
    private int $rolAdmin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->admin = Usuario::factory()->admin()->create(['nombre' => 'Jefa', 'usuario' => 'jefa']);
        $this->rolUsuario = Rol::where('nombre', 'usuario')->value('id');
        $this->rolAdmin = Rol::where('nombre', 'admin')->value('id');
    }

    private function datos(array $extra = []): array
    {
        return $extra + ['nombre' => 'María Quispe', 'usuario' => 'MQuispe', 'email' => 'Maria@Muruwasi.pe', 'password' => 'clave123', 'password_confirmation' => 'clave123',
            'id_rol' => $this->rolUsuario, 'estado' => 'activo', 'permisos' => ['ventas', 'clientes']];
    }

    public function test_crear_usuario_y_ver_su_ficha(): void
    {
        $r = $this->actingAs($this->admin)->post(route('usuarios.store'), $this->datos());
        $u = Usuario::where('usuario', 'mquispe')->firstOrFail();          // se guarda en minúsculas
        $r->assertRedirect(route('usuarios.show', $u));
        $this->assertSame('maria@muruwasi.pe', $u->email);
        $this->assertEqualsCanonicalizing(['ventas', 'clientes'], $u->permisos->pluck('modulo')->all());

        // sin espacios, sin repetir usuario ni correo
        $this->actingAs($this->admin)->post(route('usuarios.store'), $this->datos(['usuario' => 'maria q', 'email' => 'otro@x.pe']))->assertSessionHasErrors('usuario');
        $this->actingAs($this->admin)->post(route('usuarios.store'), $this->datos(['usuario' => 'MQUISPE', 'email' => 'otro@x.pe']))->assertSessionHasErrors('usuario');
        $this->actingAs($this->admin)->post(route('usuarios.store'), $this->datos(['usuario' => 'otra', 'email' => 'maria@muruwasi.pe']))->assertSessionHasErrors('email');

        ActividadUsuario::create(['id_usuario' => $u->id, 'accion' => 'crear', 'modulo' => 'Ventas', 'descripcion' => 'Registró la venta V-000001', 'created_at' => now()]);
        ActividadUsuario::create(['id_usuario' => $u->id, 'accion' => 'login_fallido', 'modulo' => 'Sesión', 'descripcion' => 'Contraseña incorrecta', 'created_at' => now()]);
        $this->actingAs($this->admin)->get(route('usuarios.show', $u))->assertOk()
            ->assertSee('María Quispe')->assertSee('Registró la venta V-000001')->assertSee('1 intento(s) fallido(s)')->assertSee('Clientes');
        $this->actingAs($this->admin)->get(route('usuarios.index'))->assertOk()->assertSee('data-href="' . route('usuarios.show', $u) . '"', false);
        $this->actingAs($this->admin)->get(route('usuarios.create'))->assertOk()->assertSee('Crear usuario')->assertSee('Panadero / Producción');
        $this->actingAs($this->admin)->get(route('usuarios.edit', $u))->assertOk()->assertSee('Guardar cambios');
    }

    public function test_no_se_queda_sin_administrador(): void
    {
        // No puede quitarse el rol de admin ni desactivarse a sí mismo
        $base = ['nombre' => 'Jefa', 'usuario' => 'jefa', 'email' => $this->admin->email];
        $this->actingAs($this->admin)->put(route('usuarios.update', $this->admin), $base + ['id_rol' => $this->rolUsuario, 'estado' => 'activo'])->assertSessionHasErrors('id_rol');
        $this->actingAs($this->admin)->put(route('usuarios.update', $this->admin), $base + ['id_rol' => $this->rolAdmin, 'estado' => 'inactivo'])->assertSessionHasErrors('id_rol');
        $this->assertTrue($this->admin->fresh()->isAdmin());
        $this->assertSame('activo', $this->admin->fresh()->estado);

        // Otro admin no puede dejar al sistema sin administradores activos
        $otro = Usuario::factory()->admin()->create(['usuario' => 'otro', 'email' => 'otro@muruwasi.pe']);
        $this->actingAs($otro)->put(route('usuarios.toggle-estado', $this->admin));
        $this->assertSame('inactivo', $this->admin->fresh()->estado);            // quedaba "otro"
        $this->admin->refresh()->update(['estado' => 'activo']);
        $this->actingAs($this->admin)->put(route('usuarios.update', $otro), ['nombre' => $otro->nombre, 'usuario' => $otro->usuario, 'email' => $otro->email, 'id_rol' => $this->rolUsuario, 'estado' => 'activo'])
            ->assertSessionHasNoErrors();                                          // "jefa" sigue siendo admin
        $this->assertFalse($otro->fresh()->isAdmin());
        // Si "jefa" queda como única admin activa, el sistema la protege
        Usuario::where('id', '!=', $this->admin->id)->update(['estado' => 'inactivo']);
        $this->actingAs($this->admin)->get(route('usuarios.show', $this->admin))->assertOk()->assertSee('único administrador activo');
        $otroAdmin = Usuario::factory()->admin()->create(['usuario' => 'temporal', 'email' => 't@muruwasi.pe']);
        $otroAdmin->update(['estado' => 'inactivo']);
        $this->actingAs($this->admin)->put(route('usuarios.toggle-estado', $this->admin))->assertSessionHasErrors('error');
        $this->assertSame('activo', $this->admin->fresh()->estado);
    }
}
