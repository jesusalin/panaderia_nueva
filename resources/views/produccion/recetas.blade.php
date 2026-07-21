@extends('layouts.app')
@section('title', 'Recetas')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('produccion.index') }}">Producción</a></li>
    <li class="breadcrumb-item active">Recetas</li>
@endsection

@push('styles')
<style>
    .rec-stats { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem; margin-bottom: 1.25rem; }
    .rec-stat {
        background: #fff; border-radius: 12px; padding: 1rem 1.1rem; box-shadow: 0 2px 12px rgba(0,0,0,.05);
        border-left: 4px solid #dee2e6; display: flex; align-items: center; gap: .8rem;
    }
    body.dark-mode .rec-stat { background: #1f1f33; box-shadow: 0 2px 12px rgba(0,0,0,.3); }
    .rec-stat .rst-icon {
        width: 42px; height: 42px; border-radius: 10px; display: flex; align-items: center; justify-content: center;
        font-size: 1.05rem; flex-shrink: 0;
    }
    .rec-stat .rst-value { font-size: 1.35rem; font-weight: 800; color: #1a1a2e; line-height: 1; }
    body.dark-mode .rec-stat .rst-value { color: #f0f0f7; }
    .rec-stat .rst-label { font-size: .74rem; color: #8a8a9d; font-weight: 700; text-transform: uppercase; letter-spacing: .03em; margin-top: .2rem; }
    .rec-stat.total    { border-left-color: #b5451b; }
    .rec-stat.total .rst-icon    { background: rgba(181,69,27,.12); color: #b5451b; }
    .rec-stat.con      { border-left-color: #2ecc71; }
    .rec-stat.con .rst-icon      { background: rgba(46,204,113,.14); color: #1e8e5a; }
    .rec-stat.sin      { border-left-color: #f39c12; }
    .rec-stat.sin .rst-icon      { background: rgba(243,156,18,.14); color: #b9770e; }

    /* Tarjeta de receta (reusa .item-card pero con el ícono de producto en vez de foto grande) */
    .rec-card-media {
        height: 88px; background: #f7f5f3; display: flex; align-items: center; justify-content: center;
        position: relative; overflow: hidden;
    }
    body.dark-mode .rec-card-media { background: #24243b; }
    .rec-card-media img { width: 100%; height: 100%; object-fit: cover; }
    .rec-card-media i { font-size: 1.8rem; color: #b5451b; }
    body.dark-mode .rec-card-media i { color: #ff9d6e; }
    .rec-card-media .ic-badge { position: absolute; top: .5rem; right: .5rem; }

    .rec-ing-list { padding: .3rem 1.1rem .8rem; }
    .rec-ing-row { display: flex; justify-content: space-between; align-items: center; padding: .3rem 0; font-size: .82rem; border-bottom: 1px dashed #eee; }
    .rec-ing-row:last-child { border-bottom: none; }
    body.dark-mode .rec-ing-row { border-bottom-color: #33334d; }
    .rec-ing-row .ri-nombre { color: #495057; }
    body.dark-mode .rec-ing-row .ri-nombre { color: #c8c8d4; }
    .rec-ing-row .ri-cant { font-weight: 700; color: #1a1a2e; }
    body.dark-mode .rec-ing-row .ri-cant { color: #f0f0f7; }

    .rec-rendimiento { padding: .6rem 1.1rem; border-top: 1px solid #f2f2f2; font-size: .78rem; color: #8a8a9d; }
    body.dark-mode .rec-rendimiento { border-top-color: #33334d; color: #9a9ac0; }
    .rec-rendimiento strong { color: #1a1a2e; }
    body.dark-mode .rec-rendimiento strong { color: #f0f0f7; }

    .rec-sin-receta { text-align: center; padding: 1.6rem 1rem; color: #adb5bd; font-size: .85rem; }
    .rec-sin-receta i { font-size: 1.6rem; display: block; margin-bottom: .5rem; opacity: .5; }

    /* Modal */
    #modalReceta .modal-content { border-radius: 14px; overflow: hidden; border: none; }
    #modalReceta .modal-header {
        background: linear-gradient(135deg, #b5451b, #8a3213); color: #fff; border: none;
    }
    #modalReceta .modal-header .close { color: #fff; opacity: .8; text-shadow: none; }
    #modalReceta .modal-header .close:hover { opacity: 1; }
    body.dark-mode #modalReceta .modal-content { background: #1f1f33; }
    body.dark-mode #modalReceta .modal-body label { color: #d5d5e2; }
    body.dark-mode #modalReceta .modal-footer { border-top-color: #33334d; }

    .modal-ing-row {
        display: flex; align-items: center; gap: .5rem; padding: .5rem; background: #f7f5f3; border-radius: 10px; margin-bottom: .5rem;
    }
    body.dark-mode .modal-ing-row { background: #24243b; }
    .modal-ing-row select { flex: 2; }
    .modal-ing-row input { flex: 1; }

    @media (max-width: 768px) { .rec-stats { grid-template-columns: 1fr; } }
</style>
@endpush

@section('content')

@include('partials.tabs-productos')

<div class="page-toolbar">
    <div>
        <h2><i class="fas fa-book mr-2 text-warning"></i>Recetas de Producción</h2>
        <p>Define los insumos y el rendimiento de cada producto para poder producirlo</p>
    </div>
</div>

<div class="rec-stats">
    <div class="rec-stat total">
        <div class="rst-icon"><i class="fas fa-bread-slice"></i></div>
        <div><div class="rst-value">{{ $stats['total'] }}</div><div class="rst-label">Productos activos</div></div>
    </div>
    <div class="rec-stat con">
        <div class="rst-icon"><i class="fas fa-check-circle"></i></div>
        <div><div class="rst-value">{{ $stats['con_receta'] }}</div><div class="rst-label">Con receta</div></div>
    </div>
    <div class="rec-stat sin">
        <div class="rst-icon"><i class="fas fa-exclamation-triangle"></i></div>
        <div><div class="rst-value">{{ $stats['sin_receta'] }}</div><div class="rst-label">Sin receta</div></div>
    </div>
</div>

<div class="card-grid">
    @forelse($productos as $producto)
    @php
        $catLower = mb_strtolower($producto->categoria->nombre ?? '');
        $iconoP = 'fa-bread-slice';
        if (str_contains($catLower, 'pastel') || str_contains($catLower, 'torta')) $iconoP = 'fa-birthday-cake';
        elseif (str_contains($catLower, 'galleta'))  $iconoP = 'fa-cookie';
        elseif (str_contains($catLower, 'empanada')) $iconoP = 'fa-cheese';
        elseif (str_contains($catLower, 'bebida') || str_contains($catLower, 'café')) $iconoP = 'fa-mug-hot';
    @endphp
    <div class="item-card">
        <div class="rec-card-media">
            @if($producto->imagen)
                <img src="{{ asset('storage/'.$producto->imagen) }}" alt="{{ $producto->nombre }}">
            @else
                <i class="fas {{ $iconoP }}"></i>
            @endif
            <span class="ic-badge">
                @if($producto->receta)
                    <span class="badge-soft badge-soft-success"><i class="fas fa-check mr-1"></i>Receta OK</span>
                @else
                    <span class="badge-soft badge-soft-secondary">Sin receta</span>
                @endif
            </span>
        </div>

        <div class="item-card-body" style="padding-bottom:0;">
            <div class="item-card-cat">{{ $producto->categoria->nombre ?? 'Sin categoría' }}</div>
            <h3 class="item-card-title" style="font-size:.95rem;">{{ $producto->nombre }}</h3>
        </div>

        @if($producto->receta)
            <div class="rec-ing-list">
                @foreach($producto->receta->detalles as $d)
                <div class="rec-ing-row">
                    <span class="ri-nombre">{{ $d->materia->nombre }}</span>
                    <span class="ri-cant">{{ $d->cantidad }} {{ $d->materia->unidad->abreviatura }}</span>
                </div>
                @endforeach
            </div>
            <div class="rec-rendimiento">
                Rinde: <strong>{{ $producto->receta->rendimiento }} unidades</strong>
                @if($producto->receta->descripcion)
                    — {{ $producto->receta->descripcion }}
                @endif
            </div>
        @else
            <div class="rec-sin-receta">
                <i class="fas fa-flask"></i>
                Sin receta registrada
            </div>
        @endif

        <div class="item-card-footer">
            <button class="btn btn-sm btn-outline-warning btn-block btn-editar-receta"
                data-id="{{ $producto->id }}"
                data-nombre="{{ $producto->nombre }}"
                data-rendimiento="{{ $producto->receta->rendimiento ?? 1 }}"
                data-descripcion="{{ $producto->receta->descripcion ?? '' }}">
                <i class="fas fa-edit mr-1"></i>{{ $producto->receta ? 'Editar' : 'Crear' }} receta
            </button>
        </div>
    </div>
    @empty
    <div class="empty-state" style="grid-column: 1 / -1;">
        <i class="fas fa-book"></i>
        <p>No hay productos activos.</p>
        <a href="{{ route('productos.create') }}" class="btn btn-primary"><i class="fas fa-plus mr-1"></i>Crear producto</a>
    </div>
    @endforelse
</div>

{{-- Modal Receta --}}
<div class="modal fade" id="modalReceta" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="{{ route('produccion.guardar-receta') }}" method="POST" id="formReceta">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-book mr-2"></i>Receta: <span id="tituloProducto">—</span></h5>
                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id_producto" id="modalIdProducto">

                    <div class="row">
                        <div class="col-md-4 form-group">
                            <label>Rendimiento (unidades) <span class="text-danger">*</span></label>
                            <input type="number" name="rendimiento" id="modalRendimiento"
                                class="form-control" min="1" value="1" required>
                            <small class="text-muted">¿Cuántas unidades produce esta receta?</small>
                        </div>
                        <div class="col-md-8 form-group">
                            <label>Descripción</label>
                            <input type="text" name="descripcion" id="modalDescripcion"
                                class="form-control" placeholder="Ej: Receta para pan francés clásico">
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <strong>Ingredientes</strong>
                        <button type="button" class="btn btn-sm btn-success" id="btnAgregarIng">
                            <i class="fas fa-plus mr-1"></i>Agregar ingrediente
                        </button>
                    </div>

                    <div id="bodyIngredientes"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-save mr-1"></i>Guardar Receta
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@php
    $materiasJs = \App\Models\MateriaPrima::where('estado', 'activo')
        ->with('unidad')
        ->orderBy('nombre')
        ->get()
        ->map(function($m) {
            return [
                'id'          => $m->id,
                'nombre'      => $m->nombre,
                'abreviatura' => $m->unidad->abreviatura,
            ];
        });
@endphp

<script>
const materias = @json($materiasJs);
let ingIndex = 0;

function agregarIngrediente(idMateria, cantidad) {
    idMateria = idMateria || '';
    cantidad  = cantidad  || '';

    let options = materias.map(function(m) {
        return '<option value="' + m.id + '"' + (m.id == idMateria ? ' selected' : '') + '>' + m.nombre + ' (' + m.abreviatura + ')</option>';
    }).join('');

    const html = '<div class="modal-ing-row fila-ing">'
        + '<select name="ingredientes[' + ingIndex + '][id_materia]" class="form-control form-control-sm" required>'
        + '<option value="">-- Seleccionar --</option>' + options
        + '</select>'
        + '<input type="number" name="ingredientes[' + ingIndex + '][cantidad]" '
        + 'class="form-control form-control-sm" step="0.001" min="0.001" value="' + cantidad + '" required placeholder="Cantidad">'
        + '<button type="button" class="btn btn-sm btn-outline-danger btn-quitar-ing"><i class="fas fa-times"></i></button>'
        + '</div>';

    document.getElementById('bodyIngredientes').insertAdjacentHTML('beforeend', html);
    document.querySelector('#bodyIngredientes .fila-ing:last-child .btn-quitar-ing')
        .addEventListener('click', function() { this.closest('.fila-ing').remove(); });
    ingIndex++;
}

document.getElementById('btnAgregarIng').addEventListener('click', function() {
    agregarIngrediente('', '');
});

document.querySelectorAll('.btn-editar-receta').forEach(function(btn) {
    btn.addEventListener('click', function() {
        const id          = this.dataset.id;
        const nombre      = this.dataset.nombre;
        const rendimiento = this.dataset.rendimiento;
        const descripcion = this.dataset.descripcion;

        document.getElementById('tituloProducto').textContent = nombre;
        document.getElementById('modalIdProducto').value      = id;
        document.getElementById('modalRendimiento').value     = rendimiento;
        document.getElementById('modalDescripcion').value     = descripcion;
        document.getElementById('bodyIngredientes').innerHTML = '';
        ingIndex = 0;

        fetch('/produccion/ingredientes/' + id)
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data && data.detalles && data.detalles.length > 0) {
                    data.detalles.forEach(function(d) {
                        const materia = materias.find(function(m) { return m.nombre === d.nombre; });
                        agregarIngrediente(materia ? materia.id : '', d.cantidad);
                    });
                } else {
                    agregarIngrediente('', '');
                }
            })
            .catch(function() {
                agregarIngrediente('', '');
            });

        $('#modalReceta').modal('show');
    });
});
</script>
@endsection
