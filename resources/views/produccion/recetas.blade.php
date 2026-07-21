@extends('layouts.app')
@section('title', 'Recetas')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('produccion.index') }}">Producción</a></li>
    <li class="breadcrumb-item active">Recetas</li>
@endsection

@section('content')

@include('partials.tabs-productos')

<div class="row mb-3">
    <div class="col-12 d-flex justify-content-between align-items-center">
        <h4 class="mb-0"><i class="fas fa-book mr-2 text-warning"></i>Recetas de Producción</h4>
        <button class="btn btn-warning" data-toggle="modal" data-target="#modalReceta">
            <i class="fas fa-plus mr-1"></i>Nueva / Editar Receta
        </button>
    </div>
</div>

<div class="row">
    @forelse($productos as $producto)
    <div class="col-md-6 col-lg-4">
        <div class="card mb-3">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <span class="font-weight-bold">{{ $producto->nombre }}</span>
                @if($producto->receta)
                    <span class="badge badge-success">Receta OK</span>
                @else
                    <span class="badge badge-warning">Sin receta</span>
                @endif
            </div>
            <div class="card-body p-0">
                @if($producto->receta)
                <table class="table table-sm mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th>Ingrediente</th>
                            <th class="text-right">Cantidad</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($producto->receta->detalles as $d)
                        <tr>
                            <td>{{ $d->materia->nombre }}</td>
                            <td class="text-right">{{ $d->cantidad }} {{ $d->materia->unidad->abreviatura }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                <div class="px-3 py-2 text-muted small border-top">
                    Rinde: <strong>{{ $producto->receta->rendimiento }} unidades</strong>
                    @if($producto->receta->descripcion)
                        — {{ $producto->receta->descripcion }}
                    @endif
                </div>
                @else
                <p class="text-muted text-center py-3 mb-0 small">Sin receta registrada.</p>
                @endif
            </div>
            <div class="card-footer text-right p-2">
                <button class="btn btn-sm btn-outline-warning btn-editar-receta"
                    data-id="{{ $producto->id }}"
                    data-nombre="{{ $producto->nombre }}"
                    data-rendimiento="{{ $producto->receta->rendimiento ?? 1 }}"
                    data-descripcion="{{ $producto->receta->descripcion ?? '' }}">
                    <i class="fas fa-edit mr-1"></i>{{ $producto->receta ? 'Editar' : 'Crear' }} receta
                </button>
            </div>
        </div>
    </div>
    @empty
    <div class="col-12">
        <div class="alert alert-warning">No hay productos activos. <a href="{{ route('productos.create') }}">Crear producto</a></div>
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

                    <table class="table table-sm" id="tablaIngredientes">
                        <thead class="bg-light">
                            <tr>
                                <th>Materia Prima</th>
                                <th style="width:180px">Cantidad</th>
                                <th style="width:40px"></th>
                            </tr>
                        </thead>
                        <tbody id="bodyIngredientes"></tbody>
                    </table>
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

    const html = '<tr class="fila-ing">'
        + '<td><select name="ingredientes[' + ingIndex + '][id_materia]" class="form-control form-control-sm" required>'
        + '<option value="">-- Seleccionar --</option>' + options
        + '</select></td>'
        + '<td><input type="number" name="ingredientes[' + ingIndex + '][cantidad]" '
        + 'class="form-control form-control-sm" step="0.001" min="0.001" value="' + cantidad + '" required></td>'
        + '<td><button type="button" class="btn btn-sm btn-outline-danger btn-quitar-ing"><i class="fas fa-times"></i></button></td>'
        + '</tr>';

    document.getElementById('bodyIngredientes').insertAdjacentHTML('beforeend', html);
    document.querySelector('#bodyIngredientes tr:last-child .btn-quitar-ing')
        .addEventListener('click', function() { this.closest('tr').remove(); });
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
