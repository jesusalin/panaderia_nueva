@extends('layouts.app')
@section('title', 'Ajuste de Inventario')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('materia-prima.index') }}">Materia Prima</a></li>
    <li class="breadcrumb-item active">Ajuste de Inventario</li>
@endsection

@section('content')
<div class="row justify-content-center">
    <div class="col-md-7">
        <div class="card">
            <div class="card-header bg-white">
                <h5 class="mb-0"><i class="fas fa-balance-scale mr-2 text-warning"></i>
                    Ajuste de Inventario — {{ $materiaPrima->nombre }}
                </h5>
            </div>
            <div class="card-body">

                @if($errors->any())
                    <div class="alert alert-danger">
                        @foreach($errors->all() as $error)
                            <p class="mb-0">{{ $error }}</p>
                        @endforeach
                    </div>
                @endif

                <div class="alert alert-info alert-dismissible fade show">
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <i class="fas fa-info-circle mr-1"></i>
                    Usa este formulario cuando el stock físico (contado a mano) sea diferente
                    al stock que muestra el sistema. El ajuste quedará registrado en el Kardex
                    como un movimiento de tipo <strong>Ajuste</strong> para mantener la trazabilidad.
                </div>

                <div class="row mb-4">
                    <div class="col-6">
                        <div class="text-center p-3 bg-light rounded">
                            <small class="text-muted d-block">Stock en el Sistema</small>
                            <h3 class="mb-0 text-primary">
                                {{ number_format($materiaPrima->stock_actual, 3) }}
                                <small class="text-muted">{{ $materiaPrima->unidad->abreviatura }}</small>
                            </h3>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="text-center p-3 bg-light rounded">
                            <small class="text-muted d-block">Stock Mínimo</small>
                            <h3 class="mb-0 text-secondary">
                                {{ number_format($materiaPrima->stock_minimo, 3) }}
                                <small class="text-muted">{{ $materiaPrima->unidad->abreviatura }}</small>
                            </h3>
                        </div>
                    </div>
                </div>

                <form action="{{ route('materia-prima.ajuste.store', $materiaPrima) }}" method="POST">
                    @csrf
                    <div class="form-group">
                        <label class="font-weight-bold">Stock Real Contado *</label>
                        <div class="input-group">
                            <input type="number" step="0.001" min="0" name="stock_real"
                                   class="form-control form-control-lg @error('stock_real') is-invalid @enderror"
                                   value="{{ old('stock_real', $materiaPrima->stock_actual) }}"
                                   id="stockReal" oninput="calcularDiferencia()">
                            <div class="input-group-append">
                                <span class="input-group-text">{{ $materiaPrima->unidad->abreviatura }}</span>
                            </div>
                        </div>
                        @error('stock_real')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>

                    <div class="form-group">
                        <label id="diferenciaLabel" class="font-weight-bold"></label>
                    </div>

                    <div class="form-group">
                        <label class="font-weight-bold">Motivo del Ajuste *</label>
                        <textarea name="observacion" rows="3"
                                  class="form-control @error('observacion') is-invalid @enderror"
                                  placeholder="Ej: Diferencia encontrada en inventario físico mensual, merma por humedad, error de registro anterior...">{{ old('observacion') }}</textarea>
                        @error('observacion')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-warning">
                            <i class="fas fa-check mr-1"></i>Confirmar Ajuste
                        </button>
                        <a href="{{ route('materia-prima.index') }}" class="btn btn-secondary">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function calcularDiferencia() {
    const stockSistema = {{ $materiaPrima->stock_actual }};
    const stockReal = parseFloat(document.getElementById('stockReal').value) || 0;
    const diferencia = stockReal - stockSistema;
    const label = document.getElementById('diferenciaLabel');

    if (diferencia === 0) {
        label.innerHTML = '<span class="text-muted">Sin diferencia</span>';
    } else if (diferencia > 0) {
        label.innerHTML = '<span class="text-success"><i class="fas fa-arrow-up mr-1"></i>Diferencia: +' + diferencia.toFixed(3) + ' {{ $materiaPrima->unidad->abreviatura }} (sobrante)</span>';
    } else {
        label.innerHTML = '<span class="text-danger"><i class="fas fa-arrow-down mr-1"></i>Diferencia: ' + diferencia.toFixed(3) + ' {{ $materiaPrima->unidad->abreviatura }} (faltante)</span>';
    }
}
calcularDiferencia();
</script>
@endsection
