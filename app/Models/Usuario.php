<?php
namespace App\Models;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;

class Usuario extends Authenticatable {
    use Notifiable, HasFactory;

    protected $table    = 'usuarios';
    protected $fillable = ['nombre','apodo','usuario','email','password','debe_cambiar_password','foto_perfil','id_rol','estado','ultimo_acceso'];
    protected $hidden   = ['password'];
    protected $casts    = ['password' => 'hashed', 'ultimo_acceso' => 'datetime', 'debe_cambiar_password' => 'boolean'];

    /** URL de la foto de perfil (null si no tiene; se muestran sus iniciales). */
    public function getFotoUrlAttribute(): ?string
    {
        return $this->foto_perfil ? route('perfil.foto', ['usuario' => $this->id, 'v' => substr(md5($this->foto_perfil), 0, 8)]) : null;
    }

    /** Iniciales para el avatar sin foto: "Maria Quispe" → "MQ". */
    public function getInicialesAttribute(): string
    {
        $partes = preg_split('/\s+/', trim((string) $this->nombre)) ?: [];
        $ini = mb_substr($partes[0] ?? '?', 0, 1) . (count($partes) > 1 ? mb_substr(end($partes), 0, 1) : '');
        return mb_strtoupper($ini);
    }

    /** Contraseña temporal fácil de dictar (sin letras que se confunden: 0/O, 1/l). */
    public static function generarClaveTemporal(): string
    {
        $letras = 'abcdefghjkmnpqrstuvwxyz';
        $l = fn () => $letras[random_int(0, strlen($letras) - 1)];
        return $l() . $l() . $l() . $l() . '-' . random_int(2000, 9999);
    }

    // Minutos de inactividad tras los cuales dejamos de considerar "en línea" a un usuario.
    const MINUTOS_CONECTADO = 3;

    /**
     * Permisos que el admin puede asignar, organizados IGUAL QUE EL MENÚ:
     *   sección del menú  →  ítems (pantallas)  →  pestañas dentro de una pantalla
     *
     * Cada pantalla o pestaña es un permiso aparte (su clave, p. ej. "inventario.conteo"),
     * así el admin elige exactamente qué puede usar cada usuario.
     *
     * ¿Una pantalla tiene pestañas adentro (como Productos: Productos / Producción /
     * Recetas)? Se declaran en 'pestanas' y el formulario de usuarios las muestra solas,
     * con una casilla general para el ítem y una casilla por pestaña. Para agregar una
     * pantalla o pestaña nueva en el futuro basta con añadirla aquí y proteger su ruta
     * con ->middleware('modulo:<clave>').
     *
     * 'menu' es el data-menu-id de la sección (para seguir el orden del menú).
     */
    const PERMISOS = [
        'inventario' => [
            'etiqueta' => 'Inventario', 'icono' => 'fa-boxes', 'menu' => 'sec-inventario',
            'items' => [
                'inventario.materia_prima' => ['nombre' => 'Materia Prima', 'desc' => 'Insumos: crear, editar y ajustar stock'],
                'inventario.movimientos'   => ['nombre' => 'Movimientos de Materia Prima', 'desc' => 'Historial de entradas y salidas de insumos'],
                'inventario.conteo'        => ['nombre' => 'Conteo Físico', 'desc' => 'Registrar conteos y ver su historial'],
                'inventario.kardex'        => ['nombre' => 'Movimientos de Productos', 'desc' => 'Historial de entradas y salidas de productos'],
            ],
        ],
        'compras' => [
            'etiqueta' => 'Compras', 'icono' => 'fa-truck', 'menu' => 'sec-compras',
            'items' => [
                'compras.proveedores' => ['nombre' => 'Proveedores', 'desc' => 'Registrar y editar proveedores'],
                'compras.compras'     => ['nombre' => 'Compras', 'desc' => 'Registrar, recibir y anular compras'],
                'compras.ordenes'     => ['nombre' => 'Órdenes Automáticas', 'desc' => 'Órdenes de reposición sugeridas'],
            ],
        ],
        'productos' => [
            'etiqueta' => 'Productos y Producción', 'icono' => 'fa-bread-slice', 'menu' => 'sec-productos',
            'items' => [
                // Ítem "Productos" del menú: una pantalla con 3 pestañas
                'productos' => [
                    'nombre' => 'Productos', 'desc' => 'Pantalla con pestañas: elige cuáles verá',
                    'pestanas' => [
                        'catalogo.productos'    => ['nombre' => 'Productos', 'desc' => 'Ver, crear y editar productos (y vender desde el carrito si tiene Ventas)'],
                        'produccion.produccion' => ['nombre' => 'Producción', 'desc' => 'Registrar producción (sube productos y descuenta insumos)'],
                        'produccion.recetas'    => ['nombre' => 'Recetas', 'desc' => 'Definir los ingredientes de cada producto'],
                    ],
                ],
                'catalogo.categorias' => ['nombre' => 'Categorías', 'desc' => 'Organizar los productos por categoría'],
            ],
        ],
        'reportes' => [
            'etiqueta' => 'Reportes', 'icono' => 'fa-chart-pie', 'menu' => 'sec-reportes',
            'items' => [
                'reportes.resumen' => ['nombre' => 'Resumen de Reportes', 'desc' => 'Cifras clave de todos los reportes'],
                'reportes.stock'   => ['nombre' => 'Reporte de Stock', 'desc' => 'Semáforo de stock y exportar PDF/Excel'],
                'reportes.kardex'  => ['nombre' => 'Reporte de Kardex', 'desc' => 'Kardex por insumo o producto, PDF/Excel'],
            ],
        ],
        'ventas_clientes' => [
            'etiqueta' => 'Ventas y Clientes', 'icono' => 'fa-cash-register', 'menu' => 'sec-ventas',
            'items' => [
                'ventas'   => ['nombre' => 'Ventas', 'desc' => 'Registrar, ver, anular y exportar ventas'],
                'clientes' => ['nombre' => 'Clientes', 'desc' => 'Registrar clientes y ver su historial'],
            ],
        ],
    ];

    /** Prefijo de la clave => etiqueta (para textos cortos y compatibilidad). */
    const MODULOS = [
        'inventario' => 'Inventario',
        'compras'    => 'Compras',
        'catalogo'   => 'Catálogo',
        'produccion' => 'Producción',
        'reportes'   => 'Reportes',
        'ventas'     => 'Ventas',
        'clientes'   => 'Clientes',
    ];

    /**
     * Todas las pantallas/pestañas con permiso propio, en orden, aplanadas:
     * clave => ['nombre', 'desc', 'seccion' => clave de sección, 'padre' => nombre del ítem con pestañas o null]
     */
    public static function pantallasPlanas(): array
    {
        $planas = [];
        foreach (self::PERMISOS as $seccion => $def) {
            foreach ($def['items'] as $clave => $item) {
                if (!empty($item['pestanas'])) {
                    foreach ($item['pestanas'] as $cPestana => $pestana) {
                        $planas[$cPestana] = $pestana + ['seccion' => $seccion, 'padre' => $item['nombre']];
                    }
                } else {
                    $planas[$clave] = $item + ['seccion' => $seccion, 'padre' => null];
                }
            }
        }
        return $planas;
    }

    /** Todas las claves de permiso válidas. */
    public static function clavesPermiso(): array
    {
        return array_keys(self::pantallasPlanas());
    }

    /**
     * Convierte una lista de claves a permisos válidos. Un módulo completo guardado
     * al estilo antiguo ("inventario", "produccion"...) se expande a todas sus pantallas.
     */
    public static function expandirPermisos(array $claves): array
    {
        $validas = self::clavesPermiso();
        $resultado = [];
        foreach ($claves as $clave) {
            if (in_array($clave, $validas, true)) {
                $resultado[] = $clave;
            } else {
                foreach ($validas as $v) {
                    if (str_starts_with($v, $clave . '.')) $resultado[] = $v;
                }
            }
        }
        return array_values(array_unique($resultado));
    }

    /** Nombre legible: "Productos y Producción › Productos › Recetas" o "Inventario › Conteo Físico". */
    public static function etiquetaPermiso(string $clave): string
    {
        $p = self::pantallasPlanas()[$clave] ?? null;
        if (!$p) return $clave;
        return implode(' › ', array_filter([self::PERMISOS[$p['seccion']]['etiqueta'], $p['padre'], $p['nombre']]));
    }

    public function rol()          { return $this->belongsTo(Rol::class, 'id_rol'); }
    public function ventas()       { return $this->hasMany(Venta::class, 'id_usuario'); }
    public function compras()      { return $this->hasMany(Compra::class, 'id_usuario'); }
    public function movimientos()  { return $this->hasMany(MovimientoInventario::class, 'id_usuario'); }
    public function permisos()     { return $this->hasMany(PermisoUsuario::class, 'id_usuario'); }
    public function producciones() { return $this->hasMany(Produccion::class, 'id_usuario'); }
    public function kardex()       { return $this->hasMany(KardexProducto::class, 'id_usuario'); }

    /**
     * true si el usuario es administrador (acceso total, sin importar permisos_usuario).
     */
    public function isAdmin(): bool
    {
        return $this->rol?->nombre === 'admin';
    }

    /**
     * true si el usuario tiene el permiso indicado. El admin siempre puede todo.
     *  - Una pantalla:  $user->hasPermiso('inventario.conteo')
     *  - Un módulo:     $user->hasPermiso('inventario')  => true si tiene AL MENOS
     *                   una pantalla de ese módulo (sirve para mostrar la sección del menú).
     * Un permiso antiguo de módulo completo ("inventario") da acceso a todas sus pantallas.
     */
    public function hasPermiso(string $clave): bool
    {
        if ($this->isAdmin()) return true;

        $mios = $this->permisos->pluck('modulo');

        if (str_contains($clave, '.')) {
            [$modulo] = explode('.', $clave, 2);
            return $mios->contains($clave) || $mios->contains($modulo);
        }

        return $mios->contains($clave)
            || $mios->contains(fn ($p) => str_starts_with($p, $clave . '.'));
    }

    /** Igual que hasPermiso() (se mantiene el nombre usado en vistas y rutas). */
    public function hasModulo(string $modulo): bool
    {
        return $this->hasPermiso($modulo);
    }

    /**
     * Resumen por módulo para mostrar en el listado de usuarios:
     * [modulo => ['etiqueta', 'marcadas', 'total', 'pantallas' => [nombres]]]
     */
    public function resumenPermisos(): array
    {
        $mios = $this->modulosAsignados();
        $resumen = [];
        $porSeccion = [];
        foreach (self::pantallasPlanas() as $clave => $p) {
            $porSeccion[$p['seccion']][$clave] = $p;
        }
        foreach ($porSeccion as $seccion => $pantallas) {
            $tiene = array_values(array_intersect(array_keys($pantallas), $mios));
            if (!$tiene) continue;
            $resumen[$seccion] = [
                'etiqueta'  => self::PERMISOS[$seccion]['etiqueta'],
                'marcadas'  => count($tiene),
                'total'     => count($pantallas),
                'pantallas' => array_map(fn ($c) => trim(($pantallas[$c]['padre'] ? $pantallas[$c]['padre'] . ' › ' : '') . $pantallas[$c]['nombre']), $tiene),
            ];
        }
        return $resumen;
    }

    /**
     * Permisos asignados a este usuario, ya expandidos a pantallas (para
     * precargar las casillas en el formulario de edición).
     */
    public function modulosAsignados(): array
    {
        return self::expandirPermisos($this->permisos->pluck('modulo')->all());
    }

    /** Días sin entrar a partir de los cuales se considera "sin actividad". */
    const DIAS_SIN_ACTIVIDAD = 30;
    /** Días sin entrar a partir de los cuales se considera "poco activo". */
    const DIAS_POCO_ACTIVO = 7;

    public function actividades() { return $this->hasMany(ActividadUsuario::class, 'id_usuario'); }

    /** La última acción real (no inicios/cierres de sesión) que hizo el usuario. */
    public function ultimaAccion()
    {
        // El filtro va DENTRO de ofMany: así busca la última entre las acciones reales
        // (si fuera afuera, tomaría el último registro, p. ej. un "Cerró sesión", y lo descartaría).
        return $this->hasOne(ActividadUsuario::class, 'id_usuario')
            ->ofMany(['created_at' => 'max', 'id' => 'max'],
                fn ($q) => $q->whereNotIn('accion', ['inicio_sesion', 'cierre_sesion', 'login_fallido']));
    }

    /**
     * Estado de actividad según su último acceso:
     *   en_linea · activo (≤ 7 días) · poco_activo (8–29 días) · sin_actividad (30+ días) · nunca
     * @return array{clave:string, texto:string, dias:?int}
     */
    public function estadoActividad(): array
    {
        if ($this->estaConectado()) return ['clave' => 'en_linea', 'texto' => 'En línea', 'dias' => 0];
        if (!$this->ultimo_acceso)  return ['clave' => 'nunca', 'texto' => 'Nunca ingresó', 'dias' => null];

        $dias = (int) $this->ultimo_acceso->copy()->startOfDay()->diffInDays(now()->startOfDay());
        return match (true) {
            $dias < self::DIAS_POCO_ACTIVO     => ['clave' => 'activo', 'texto' => 'Activo', 'dias' => $dias],
            $dias < self::DIAS_SIN_ACTIVIDAD   => ['clave' => 'poco_activo', 'texto' => "Sin entrar hace {$dias} días", 'dias' => $dias],
            default                            => ['clave' => 'sin_actividad', 'texto' => "Sin actividad hace {$dias} días", 'dias' => $dias],
        };
    }

    /**
     * true si el usuario tuvo actividad en el sistema hace pocos minutos
     * (ver ActualizarUltimoAcceso). Se usa para el indicador "En línea"
     * en el listado de Usuarios.
     */
    public function estaConectado(): bool
    {
        return $this->ultimo_acceso !== null
            && $this->ultimo_acceso->diffInMinutes(now()) <= self::MINUTOS_CONECTADO;
    }
}
