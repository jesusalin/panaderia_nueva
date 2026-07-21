@extends('layouts.app')
@section('title', 'Categorías')
@section('breadcrumb') <li class="breadcrumb-item active">Categorías</li> @endsection

@push('styles')
<style>
    .cat-toolbar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; }
    .cat-toolbar h2 { font-weight: 800; margin: 0; color: #1a1a2e; }
    .cat-toolbar p { margin: .15rem 0 0; color: #8a8a9d; font-size: .88rem; }

    .cat-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 1.1rem; }
    .cat-card {
        background: #fff; border-radius: 14px; padding: 1.3rem; box-shadow: 0 2px 12px rgba(0,0,0,.05);
        border: 1.5px solid transparent; transition: all .15s; position: relative;
    }
    .cat-card:hover { box-shadow: 0 6px 20px rgba(0,0,0,.08); border-color: #f0dccd; }
    .cat-card.inactiva { border-color: #f2d4d4; }
    .cat-card.inactiva .cat-icon { filter: grayscale(.3); }

    .cat-head { display: flex; align-items: flex-start; gap: .85rem; margin-bottom: .8rem; }
    .cat-icon {
        width: 50px; height: 50px; border-radius: 12px; flex-shrink: 0;
        background: linear-gradient(135deg, #1a1a2e, #b5451b); color: #fff;
        display: flex; align-items: center; justify-content: center; font-size: 1.3rem;
    }
    .cat-name { font-weight: 800; color: #1a1a2e; margin: 0; font-size: 1.05rem; }
    .cat-desc { font-size: .82rem; color: #8a8a9d; margin: .15rem 0 0; line-height: 1.35; }

    .cat-meta { display: flex; align-items: center; justify-content: space-between; margin-top: 1rem; padding-top: .9rem; border-top: 1px solid #f2f2f2; }
    .cat-count { font-size: .78rem; color: #6c757d; }
    .cat-count strong { color: #1a1a2e; font-size: .95rem; }

    .cat-actions { display: flex; gap: .4rem; }
    .cat-actions .btn { width: 32px; height: 32px; padding: 0; display: flex; align-items: center; justify-content: center; border-radius: 8px; }
    .cat-actions .btn[disabled] { cursor: not-allowed; opacity: .4; }

    .estado-switch { display: flex; align-items: center; gap: .4rem; cursor: pointer; border: none; background: none; padding: 0; }
    .estado-switch .track {
        width: 34px; height: 18px; border-radius: 20px; background: #dee2e6; position: relative; transition: background .15s;
    }
    .estado-switch .track::after {
        content: ''; position: absolute; top: 2px; left: 2px; width: 14px; height: 14px; border-radius: 50%;
        background: #fff; transition: left .15s;
    }
    .estado-switch.activa .track { background: #2ecc71; }
    .estado-switch.activa .track::after { left: 18px; }
    .estado-switch .txt { font-size: .74rem; font-weight: 700; color: #8a8a9d; }
    .estado-switch.activa .txt { color: #1e8e5a; }

    .empty-state { text-align: center; padding: 4rem 1rem; color: #adb5bd; }
    .empty-state i { font-size: 2.6rem; margin-bottom: .8rem; opacity: .5; }
</style>
@endpush

@section('content')

@include('partials.tabs-productos')

<div class="cat-toolbar">
    <div>
        <h2><i class="fas fa-tags mr-2 text-primary"></i>Categorías</h2>
        <p>Agrupa tus productos por tipo para organizarlos mejor</p>
    </div>
    <a href="{{ route('categorias.create') }}" class="btn btn-primary">
        <i class="fas fa-plus mr-1"></i>Nueva Categoría
    </a>
</div>

@if($categorias->count() > 0)
<div class="cat-grid">
    @foreach($categorias as $c)
        @php
            $nombreLower = mb_strtolower($c->nombre);
            $icono = 'fa-tag';
            if (str_contains($nombreLower, 'pan'))                          $icono = 'fa-bread-slice';
            elseif (str_contains($nombreLower, 'pastel') || str_contains($nombreLower, 'torta')) $icono = 'fa-birthday-cake';
            elseif (str_contains($nombreLower, 'galleta'))                  $icono = 'fa-cookie';
            elseif (str_contains($nombreLower, 'empanada'))                 $icono = 'fa-cheese';
            elseif (str_contains($nombreLower, 'bebida') || str_contains($nombreLower, 'café')) $icono = 'fa-mug-hot';
        @endphp
        <div class="cat-card {{ $c->estado !== 'activo' ? 'inactiva' : '' }}">
            <div class="cat-head">
                <div class="cat-icon"><i class="fas {{ $icono }}"></i></div>
                <div>
                    <p class="cat-name">{{ $c->nombre }}</p>
                    <p class="cat-desc">{{ $c->descripcion ?? 'Sin descripción' }}</p>
                </div>
            </div>

            <div class="cat-meta">
                <span class="cat-count"><strong>{{ $c->productos_count }}</strong> producto{{ $c->productos_count === 1 ? '' : 's' }}</span>

                <form action="{{ route('categorias.toggle-estado', $c) }}" method="POST">
                    @csrf @method('PUT')
                    <button type="submit" class="estado-switch {{ $c->estado === 'activo' ? 'activa' : '' }}">
                        <span class="track"></span>
                        <span class="txt">{{ $c->estado === 'activo' ? 'Activa' : 'Inactiva' }}</span>
                    </button>
                </form>
            </div>

            <div class="cat-meta" style="border-top:none;padding-top:.6rem;">
                <div class="cat-actions ml-auto">
                    <a href="{{ route('categorias.edit', $c) }}" class="btn btn-sm btn-warning" title="Editar">
                        <i class="fas fa-edit"></i>
                    </a>
                    @if($c->productos_count > 0)
                        <button type="button" class="btn btn-sm btn-secondary is-locked js-blocked"
                            data-blocked-title="No se puede eliminar esta categoría"
                            data-blocked-message="&quot;{{ $c->nombre }}&quot; tiene {{ $c->productos_count }} producto(s) asociado(s). Desactívala si ya no la usas, o mueve sus productos a otra categoría antes de eliminarla.">
                            <i class="fas fa-trash"></i>
                        </button>
                    @else
                        <form action="{{ route('categorias.destroy', $c) }}" method="POST" class="js-confirm"
                            data-confirm-title="¿Eliminar esta categoría?"
                            data-confirm="&quot;{{ $c->nombre }}&quot; se borrará por completo del sistema. Esta acción no se puede deshacer.">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-danger" title="Eliminar">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    @endforeach
</div>
<div class="mt-3">{{ $categorias->links() }}</div>
@else
<div class="empty-state">
    <i class="fas fa-tags"></i>
    <p class="mb-3">Todavía no tienes categorías registradas</p>
    <a href="{{ route('categorias.create') }}" class="btn btn-primary"><i class="fas fa-plus mr-1"></i>Crear la primera</a>
</div>
@endif

@endsection
