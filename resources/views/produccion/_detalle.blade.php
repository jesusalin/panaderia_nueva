<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-industry mr-2 text-warning"></i>Producción #{{ $produccion->id }}</h5>
                <span class="badge badge-success badge-lg">{{ $produccion->cantidad }} unidades</span>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <p class="mb-1 text-muted small">Producto</p>
                        <p class="font-weight-bold h5">{{ $produccion->producto->nombre }}</p>
                    </div>
                    <div class="col-md-3">
                        <p class="mb-1 text-muted small">Fecha</p>
                        <p class="font-weight-bold">{{ $produccion->fecha->format('d/m/Y') }}</p>
                    </div>
                    <div class="col-md-3">
                        <p class="mb-1 text-muted small">Registrado por</p>
                        <p class="font-weight-bold">{{ $produccion->usuario->nombre ?? '—' }}</p>
                    </div>
                </div>

                @if($produccion->observacion)
                <div class="alert alert-light border">
                    <i class="fas fa-comment mr-2 text-muted"></i>{{ $produccion->observacion }}
                </div>
                @endif

                @if($produccion->producto->receta)
                <h6 class="font-weight-bold mt-3 mb-2">Materia prima consumida</h6>
                @php
                    $receta = $produccion->producto->receta;
                    $lotes  = $produccion->cantidad / max($receta->rendimiento, 1);
                @endphp
                <table class="table table-sm">
                    <thead class="bg-light">
                        <tr>
                            <th>Ingrediente</th>
                            <th class="text-right">Por lote</th>
                            <th class="text-right">Total consumido</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($receta->detalles as $d)
                        <tr>
                            <td>{{ $d->materia->nombre }}</td>
                            <td class="text-right">{{ $d->cantidad }} {{ $d->materia->unidad->abreviatura }}</td>
                            <td class="text-right font-weight-bold">
                                {{ round($d->cantidad * $lotes, 3) }} {{ $d->materia->unidad->abreviatura }}
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                @endif
            </div>
            <div class="card-footer text-right">
                <a href="{{ route('produccion.index') }}" class="btn btn-light">
                    <i class="fas fa-arrow-left mr-1"></i>Volver
                </a>
                @if($produccion->producto->stock_actual >= $produccion->cantidad)
                    <form action="{{ route('produccion.destroy', $produccion) }}" method="POST" class="js-confirm d-inline"
                        data-confirm-title="¿Eliminar esta producción?"
                        data-confirm="Se quitarán {{ $produccion->cantidad }} unidades de &quot;{{ $produccion->producto->nombre }}&quot; del stock y se devolverán los insumos que se habían descontado. Esta acción NO se puede deshacer.">
                        @csrf @method('DELETE')
                        <button class="btn btn-danger"><i class="fas fa-trash-alt mr-1"></i>Eliminar</button>
                    </form>
                @else
                    <button type="button" class="btn btn-secondary is-locked js-blocked"
                        data-blocked-title="No se puede eliminar esta producción"
                        data-blocked-message="Ya se vendieron o movieron unidades de &quot;{{ $produccion->producto->nombre }}&quot; desde que se registró. Eliminarla dejaría el stock en negativo.">
                        <i class="fas fa-trash-alt mr-1"></i>Eliminar
                    </button>
                @endif
            </div>
        </div>
    </div>
</div>
