{{--
    Permisos del usuario, organizados IGUAL QUE EL MENÚ (ver Usuario::PERMISOS):
    sección → ítems → pestañas dentro de un ítem (p. ej. Productos: Productos /
    Producción / Recetas). Cada pantalla o pestaña se envía como permisos[] = su clave.
    - La casilla de la sección marca/desmarca todo lo de adentro; la de un ítem con
      pestañas, todas sus pestañas. Quedan "a medias" si solo hay algunas marcadas.
    - Las secciones salen en el mismo orden que el menú (el que haya guardado el admin).
    - Plantillas rápidas para no marcar todo a mano.
--}}
@php
    $permisosDef   = \App\Models\Usuario::PERMISOS;
    $seleccionados = \App\Models\Usuario::expandirPermisos(old('permisos', $seleccionados ?? []));

    // Mismo orden de secciones que el menú lateral
    $ordenMenu = \App\Models\Configuracion::obtener(\App\Http\Controllers\MenuController::CLAVE)['secciones'] ?? [];
    $posicion  = fn ($def, $i) => ($k = array_search($def['menu'] ?? '', $ordenMenu, true)) !== false ? $k : 100 + $i;
    $i = 0;
    $permisosDef = collect($permisosDef)->map(function ($def) use (&$i, $posicion) { $def['_pos'] = $posicion($def, $i++); return $def; })
        ->sortBy('_pos')->all();

    $plantillas = [
        'almacenero' => ['icono' => 'fa-boxes-stacked', 'label' => 'Almacenero', 'claves' => [
            'inventario.materia_prima', 'inventario.movimientos', 'inventario.conteo',
            'compras.proveedores', 'compras.compras', 'compras.ordenes',
            'reportes.stock', 'reportes.kardex',
        ]],
        'ventas' => ['icono' => 'fa-cash-register', 'label' => 'Ventas / Caja', 'claves' => [
            'catalogo.productos', 'ventas', 'clientes',
        ]],
    ];
@endphp

<div class="form-group" id="bloquePermisos">
    <label class="section-label">
        <i class="fas fa-key mr-1"></i> ¿Qué puede usar este usuario?
    </label>

    <div class="permisos-grid">
        <div class="perm-toolbar">
            <span class="perm-toolbar-txt">Plantillas rápidas:</span>
            @foreach($plantillas as $clave => $p)
                <button type="button" class="perm-chip" data-plantilla='@json($p['claves'])'
                        title="Marca solo: {{ collect($p['claves'])->map(fn($c) => \App\Models\Usuario::etiquetaPermiso($c))->implode(', ') }}">
                    <i class="fas {{ $p['icono'] }} mr-1"></i>{{ $p['label'] }}
                </button>
            @endforeach
            <button type="button" class="perm-chip" data-plantilla="todo"><i class="fas fa-check-double mr-1"></i>Marcar todo</button>
            <button type="button" class="perm-chip perm-chip-quitar" data-plantilla="[]"><i class="fas fa-eraser mr-1"></i>Quitar todo</button>
            <span class="perm-resumen" id="permResumen"></span>
        </div>

        @foreach($permisosDef as $seccion => $def)
            <div class="perm-modulo" data-modulo="{{ $seccion }}">
                <label class="perm-cabecera">
                    <input type="checkbox" class="js-perm-modulo" aria-label="Todo {{ $def['etiqueta'] }}">
                    <span class="perm-icono"><i class="fas {{ $def['icono'] }}"></i></span>
                    <span class="perm-titulo">{{ $def['etiqueta'] }}</span>
                    <span class="perm-contador"></span>
                </label>
                <div class="perm-pantallas">
                    @foreach($def['items'] as $clave => $item)
                        @if(!empty($item['pestanas']))
                            {{-- Ítem con pestañas adentro: plegado por defecto (una sola fila, como
                                 el menú). La casilla marca/quita todas sus pestañas; el nombre o
                                 la flecha lo despliegan para elegir pestaña por pestaña. --}}
                            @php $idGrupo = 'grupo-' . \Illuminate\Support\Str::slug($seccion . '-' . $clave); @endphp
                            <div class="perm-grupo">
                                <div class="perm-grupo-cabecera">
                                    <label class="perm-grupo-check" title="Marcar o quitar todas las pestañas de {{ $item['nombre'] }}">
                                        <input type="checkbox" class="js-perm-grupo" aria-label="Todas las pestañas de {{ $item['nombre'] }}">
                                        <span class="perm-caja perm-caja-grupo"><i class="fas fa-check"></i><i class="fas fa-minus"></i></span>
                                    </label>
                                    <button type="button" class="perm-grupo-toggle js-perm-grupo-toggle" aria-expanded="false" aria-controls="{{ $idGrupo }}">
                                        <span class="perm-textos">
                                            <span class="perm-nombre">{{ $item['nombre'] }} <span class="perm-tag js-perm-grupo-cuenta">0/{{ count($item['pestanas']) }}</span></span>
                                            <span class="perm-desc js-perm-grupo-resumen">Ninguna pestaña marcada</span>
                                        </span>
                                        <i class="fas fa-chevron-down perm-grupo-flecha"></i>
                                    </button>
                                </div>
                                <div class="perm-pestanas" id="{{ $idGrupo }}" hidden>
                                    @foreach($item['pestanas'] as $cPestana => $pestana)
                                        <label class="perm-pantalla">
                                            <input type="checkbox" name="permisos[]" value="{{ $cPestana }}" class="js-perm-pantalla"
                                                   {{ in_array($cPestana, $seleccionados, true) ? 'checked' : '' }}>
                                            <span class="perm-caja"><i class="fas fa-check"></i></span>
                                            <span class="perm-textos">
                                                <span class="perm-nombre"><i class="fas fa-folder-open perm-pestana-ico"></i>{{ $pestana['nombre'] }}</span>
                                                <span class="perm-desc">{{ $pestana['desc'] ?? '' }}</span>
                                            </span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        @else
                            <label class="perm-pantalla">
                                <input type="checkbox" name="permisos[]" value="{{ $clave }}" class="js-perm-pantalla"
                                       {{ in_array($clave, $seleccionados, true) ? 'checked' : '' }}>
                                <span class="perm-caja"><i class="fas fa-check"></i></span>
                                <span class="perm-textos">
                                    <span class="perm-nombre">{{ $item['nombre'] }}</span>
                                    <span class="perm-desc">{{ $item['desc'] ?? '' }}</span>
                                </span>
                            </label>
                        @endif
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>

    <div class="alert-admin-total" id="avisoAdminTotal">
        <i class="fas fa-infinity mr-2"></i>
        Este usuario es <strong>Admin</strong>: ya tiene acceso a todo el sistema, no hace falta marcar permisos.
    </div>
</div>

@once
@push('styles')
<style>
    #bloquePermisos .permisos-grid {
        display: grid; grid-template-columns: repeat(auto-fill, minmax(290px, 1fr)); gap: .8rem; align-items: start;
    }
    .perm-toolbar {
        grid-column: 1 / -1; display: flex; flex-wrap: wrap; align-items: center; gap: .4rem;
        padding: .2rem 0 .3rem;
    }
    .perm-toolbar-txt { font-size: .78rem; color: #8a8a9d; font-weight: 600; margin-right: .15rem; }
    .perm-chip {
        border: 1.5px solid #e3d6cc; background: #fff; color: #7a4a33; border-radius: 999px;
        font-size: .78rem; font-weight: 700; padding: .28rem .75rem; cursor: pointer; transition: all .12s;
    }
    .perm-chip:hover { background: #fff5f0; border-color: #b5451b; color: #b5451b; }
    .perm-chip:focus-visible { outline: 2px solid #b5451b; outline-offset: 2px; }
    .perm-chip-quitar { color: #6c757d; border-color: #e3e3ea; }
    .perm-resumen { margin-left: auto; font-size: .78rem; color: #6c757d; font-weight: 600; }

    .perm-modulo {
        border: 1.5px solid #e9ecef; border-radius: 12px; background: #fff; overflow: hidden;
        transition: border-color .15s;
    }
    .perm-modulo.tiene-algo { border-color: #e2b49c; }
    .perm-modulo.completo  { border-color: #b5451b; }
    .perm-cabecera {
        display: flex; align-items: center; gap: .65rem; margin: 0; padding: .7rem .9rem;
        cursor: pointer; background: #fbfaf9; border-bottom: 1px solid #f0ece6; user-select: none;
    }
    .perm-cabecera input[type=checkbox].js-perm-modulo {
        width: 17px; height: 17px; accent-color: #b5451b; cursor: pointer; flex-shrink: 0;
    }
    .perm-icono {
        width: 32px; height: 32px; border-radius: 8px; background: #f1f1f4; color: #6c757d;
        display: flex; align-items: center; justify-content: center; flex-shrink: 0; transition: all .15s;
    }
    .perm-modulo.tiene-algo .perm-icono { background: #fbe3d6; color: #b5451b; }
    .perm-modulo.completo .perm-icono  { background: #b5451b; color: #fff; }
    .perm-titulo { flex: 1; font-weight: 700; font-size: .9rem; color: #343a40; }
    .perm-contador { font-size: .72rem; font-weight: 700; color: #8a8a9d; white-space: nowrap; }
    .perm-modulo.tiene-algo .perm-contador { color: #b5451b; }

    .perm-pantallas { padding: .3rem .4rem .45rem; }
    .perm-pantalla {
        display: flex; align-items: flex-start; gap: .6rem; margin: 0; padding: .45rem .5rem;
        border-radius: 8px; cursor: pointer; transition: background .12s;
    }
    .perm-pantalla:hover { background: #fff7f2; }
    .perm-pantalla input { position: absolute; opacity: 0; pointer-events: none; }
    .perm-caja {
        width: 18px; height: 18px; border-radius: 5px; border: 1.5px solid #ced4da; flex-shrink: 0; margin-top: .1rem;
        display: flex; align-items: center; justify-content: center; transition: all .12s; background: #fff;
    }
    .perm-caja i { font-size: .6rem; color: transparent; }
    .perm-pantalla input:checked + .perm-caja { background: #b5451b; border-color: #b5451b; }
    .perm-pantalla input:checked + .perm-caja i { color: #fff; }
    .perm-pantalla input:focus-visible + .perm-caja { outline: 2px solid #b5451b; outline-offset: 2px; }
    .perm-textos { display: flex; flex-direction: column; line-height: 1.25; }
    .perm-nombre { font-size: .86rem; font-weight: 600; color: #495057; }
    .perm-pantalla input:checked ~ .perm-textos .perm-nombre { color: #1a1a2e; }
    .perm-desc { font-size: .74rem; color: #8a8a9d; font-weight: 400; margin-top: .1rem; }
    /* Ítem con pestañas adentro (p. ej. Productos) */
    .perm-grupo { margin: .15rem 0; border-radius: 9px; background: #faf8f6; padding-bottom: .2rem; }
    .perm-grupo-cabecera { display: flex; align-items: flex-start; gap: .1rem; padding: .25rem .2rem 0 .5rem; }
    .perm-grupo-check { margin: 0; padding: .45rem .5rem .45rem 0; cursor: pointer; }
    .perm-grupo-cabecera input { position: absolute; opacity: 0; pointer-events: none; }
    .perm-grupo-toggle {
        flex: 1; display: flex; align-items: flex-start; justify-content: space-between; gap: .5rem;
        background: none; border: 0; padding: .45rem .5rem .45rem .1rem; border-radius: 8px;
        text-align: left; cursor: pointer; color: inherit; font: inherit;
    }
    .perm-grupo-toggle:hover { background: #fff1e8; }
    .perm-grupo-toggle:focus-visible { outline: 2px solid #b5451b; outline-offset: 1px; }
    .perm-grupo-flecha { font-size: .7rem; color: #9a6a52; margin-top: .3rem; transition: transform .18s ease; }
    .perm-grupo.abierto .perm-grupo-flecha { transform: rotate(180deg); }
    .perm-grupo:not(.abierto) { padding-bottom: 0; }
    .perm-caja-grupo .fa-minus { display: none; font-size: .6rem; color: #fff; }
    .perm-grupo-cabecera input:checked + .perm-caja { background: #b5451b; border-color: #b5451b; }
    .perm-grupo-cabecera input:checked + .perm-caja .fa-check { color: #fff; }
    .perm-grupo-cabecera input:indeterminate + .perm-caja { background: #d98a66; border-color: #d98a66; }
    .perm-grupo-cabecera input:indeterminate + .perm-caja .fa-check { display: none; }
    .perm-grupo-cabecera input:indeterminate + .perm-caja .fa-minus { display: inline-block; }
    .perm-grupo-cabecera input:focus-visible + .perm-caja { outline: 2px solid #b5451b; outline-offset: 2px; }
    .perm-pestanas[hidden] { display: none; }
    .perm-pestanas { margin: 0 0 .2rem 1.15rem; padding-left: .55rem; border-left: 2px solid #ecdcd1; }
    .perm-pestana-ico { font-size: .7rem; color: #c9a08a; margin-right: .35rem; }
    .perm-tag {
        display: inline-block; font-size: .65rem; font-weight: 700; color: #9a6a52; background: #f6e6dc;
        border-radius: 999px; padding: .05rem .45rem; margin-left: .3rem; vertical-align: middle;
    }
    body.dark-mode .perm-grupo { background: rgba(255,255,255,.03); }
    body.dark-mode .perm-grupo-toggle:hover { background: rgba(181,69,27,.12); }
    body.dark-mode .perm-pestanas { border-left-color: #45455f; }
    body.dark-mode .perm-tag { background: rgba(181,69,27,.2); color: #e6c3b0; }

    body.dark-mode .perm-chip { background: #24243b; border-color: #45455f; color: #e6c3b0; }
    body.dark-mode .perm-chip:hover { background: rgba(181,69,27,.18); }
    body.dark-mode .perm-modulo { background: #24243b; border-color: #33334d; }
    body.dark-mode .perm-modulo.tiene-algo { border-color: #7a3b22; }
    body.dark-mode .perm-modulo.completo { border-color: #b5451b; }
    body.dark-mode .perm-cabecera { background: #20203a; border-bottom-color: #33334d; }
    body.dark-mode .perm-icono { background: #33334d; color: #b0b0cc; }
    body.dark-mode .perm-titulo, body.dark-mode .perm-pantalla input:checked ~ .perm-textos .perm-nombre { color: #f0f0f7; }
    body.dark-mode .perm-nombre { color: #d5d5e2; }
    body.dark-mode .perm-pantalla:hover { background: rgba(181,69,27,.1); }
    body.dark-mode .perm-caja { background: #1a1a2e; border-color: #45455f; }
</style>
@endpush

@push('scripts')
<script>
    (function () {
        const bloque = document.getElementById('bloquePermisos');
        if (!bloque) return;
        const resumen = document.getElementById('permResumen');

        // Actualiza la casilla de cada módulo (marcada / a medias / vacía) y los contadores
        function refrescar() {
            let total = 0, marcados = 0;
            bloque.querySelectorAll('.perm-modulo').forEach(function (mod) {
                const hijos = mod.querySelectorAll('.js-perm-pantalla');
                const n = Array.prototype.filter.call(hijos, function (c) { return c.checked; }).length;
                total += hijos.length; marcados += n;
                mod.classList.toggle('tiene-algo', n > 0);
                mod.classList.toggle('completo', n === hijos.length && n > 0);
                // Ítems con pestañas: su casilla general refleja sus pestañas
                mod.querySelectorAll('.perm-grupo').forEach(function (g) {
                    const ps = g.querySelectorAll('.js-perm-pantalla');
                    const m = Array.prototype.filter.call(ps, function (c) { return c.checked; }).length;
                    const cg = g.querySelector('.js-perm-grupo');
                    cg.checked = m === ps.length;
                    cg.indeterminate = m > 0 && m < ps.length;
                    // Resumen visible aunque esté plegado: cuántas y cuáles pestañas tiene
                    const nombres = Array.prototype.filter.call(ps, function (c) { return c.checked; })
                        .map(function (c) { return c.closest('.perm-pantalla').querySelector('.perm-nombre').textContent.trim(); });
                    g.querySelector('.js-perm-grupo-cuenta').textContent = m + '/' + ps.length;
                    g.querySelector('.js-perm-grupo-resumen').textContent =
                        m === 0 ? 'Ninguna pestaña marcada' : (m === ps.length ? 'Todas: ' : 'Solo: ') + nombres.join(', ');
                });
                const general = mod.querySelector('.js-perm-modulo');
                if (general) {
                    general.checked = n === hijos.length;
                    general.indeterminate = n > 0 && n < hijos.length;
                    mod.querySelector('.perm-contador').textContent = n + ' de ' + hijos.length;
                }
            });
            resumen.textContent = marcados === 0 ? 'Sin permisos: solo verá el Dashboard'
                                                 : marcados + ' de ' + total + ' pantallas';
        }

        bloque.addEventListener('change', function (e) {
            if (e.target.classList.contains('js-perm-grupo')) {
                const marcar = e.target.checked;
                e.target.closest('.perm-grupo').querySelectorAll('.js-perm-pantalla')
                    .forEach(function (c) { c.checked = marcar; });
            }
            if (e.target.classList.contains('js-perm-modulo')) {
                const marcar = e.target.checked;
                e.target.closest('.perm-modulo').querySelectorAll('.js-perm-pantalla')
                    .forEach(function (c) { c.checked = marcar; });
            }
            refrescar();
        });

        // Plegar / desplegar las pestañas de un ítem
        bloque.addEventListener('click', function (e) {
            const btn = e.target.closest('.js-perm-grupo-toggle');
            if (!btn) return;
            const grupo = btn.closest('.perm-grupo');
            const abrir = !grupo.classList.contains('abierto');
            grupo.classList.toggle('abierto', abrir);
            grupo.querySelector('.perm-pestanas').hidden = !abrir;
            btn.setAttribute('aria-expanded', abrir ? 'true' : 'false');
        });

        // Plantillas rápidas
        bloque.querySelectorAll('[data-plantilla]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                const valor = btn.getAttribute('data-plantilla');
                const lista = valor === 'todo' ? null : JSON.parse(valor);
                bloque.querySelectorAll('.js-perm-pantalla').forEach(function (c) {
                    c.checked = lista === null ? true : lista.indexOf(c.value) !== -1;
                });
                refrescar();
            });
        });

        refrescar();
    })();
</script>
@endpush
@endonce
