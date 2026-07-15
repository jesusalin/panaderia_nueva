@php
    $iconos = [
        'catalogo'   => 'fa-tags',
        'inventario' => 'fa-boxes',
        'produccion' => 'fa-industry',
        'compras'    => 'fa-truck',
        'clientes'   => 'fa-user-friends',
        'ventas'     => 'fa-cash-register',
        'reportes'   => 'fa-stopwatch',
    ];
    $seleccionados = old('permisos', $seleccionados ?? []);
@endphp

<div class="form-group" id="bloquePermisos">
    <label class="section-label">
        <i class="fas fa-key mr-1"></i> Módulos a los que tiene acceso
    </label>

    <div class="permisos-grid">
        @foreach($modulos as $clave => $etiqueta)
            <label class="permiso-card" for="mod_{{ $clave }}">
                <input type="checkbox" id="mod_{{ $clave }}" name="permisos[]" value="{{ $clave }}"
                    {{ in_array($clave, $seleccionados) ? 'checked' : '' }}>
                <span class="permiso-icon"><i class="fas {{ $iconos[$clave] ?? 'fa-cube' }}"></i></span>
                <span class="permiso-texto">{{ $etiqueta }}</span>
                <span class="permiso-check"><i class="fas fa-check"></i></span>
            </label>
        @endforeach
    </div>

    <div class="alert-admin-total" id="avisoAdminTotal">
        <i class="fas fa-infinity mr-2"></i>
        Este usuario es <strong>Admin</strong>: ya tiene acceso a todo el sistema, no hace falta marcar módulos.
    </div>
</div>
