{{--
    Barra de pestañas del módulo "Productos" (unifica Categorías, Productos,
    Producción y Recetas en una sola sección, sin depender del menú lateral).
    Cada pestaña respeta el permiso del usuario sobre 'catalogo' / 'produccion'.
--}}
@php
    $tabsProductos = [];
    if (auth()->user()->hasModulo('catalogo')) {
        $tabsProductos[] = ['ruta' => 'productos.index',  'activo' => request()->routeIs('productos.*'),   'icono' => 'fa-bread-slice', 'label' => 'Productos'];
    }
    if (auth()->user()->hasModulo('produccion')) {
        $tabsProductos[] = ['ruta' => 'produccion.index',   'activo' => request()->routeIs('produccion.index','produccion.create','produccion.store','produccion.show','produccion.ingredientes'), 'icono' => 'fa-industry', 'label' => 'Producción'];
        $tabsProductos[] = ['ruta' => 'produccion.recetas', 'activo' => request()->routeIs('produccion.recetas','produccion.guardar-receta'), 'icono' => 'fa-book', 'label' => 'Recetas'];
    }
@endphp

@if(count($tabsProductos) > 1)
<ul class="nav nav-pills tabs-productos mb-3">
    @foreach($tabsProductos as $tab)
    <li class="nav-item">
        <a href="{{ route($tab['ruta']) }}" class="nav-link {{ $tab['activo'] ? 'active' : '' }}">
            <i class="fas {{ $tab['icono'] }} mr-1"></i>{{ $tab['label'] }}
        </a>
    </li>
    @endforeach
</ul>
@endif

@once
@push('styles')
<style>
    .tabs-productos { border-bottom: 2px solid #eee; padding-bottom: 0; gap: .3rem; }
    .tabs-productos .nav-link {
        border-radius: 8px 8px 0 0; color: #6c757d; font-weight: 600; padding: .55rem 1rem;
        border: 2px solid transparent; border-bottom: none;
    }
    .tabs-productos .nav-link:hover { background: #f7f4f0; color: #b5451b; }
    .tabs-productos .nav-link.active { color: #b5451b; background: #fff; border-color: #eee; border-bottom-color: #fff; margin-bottom: -2px; }
    body.dark-mode .tabs-productos { border-bottom-color: #33334d; }
    body.dark-mode .tabs-productos .nav-link { color: #9a9ac0; }
    body.dark-mode .tabs-productos .nav-link.active { background: #1f1f33; border-color: #33334d; border-bottom-color: #1f1f33; color: #ff8f5e; }
</style>
@endpush
@endonce
