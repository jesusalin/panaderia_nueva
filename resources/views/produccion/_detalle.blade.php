<style>
    .prodx-topbar { height: 5px; background: linear-gradient(90deg, #b9770e, #ffc673); }
    .prodx-head { display: flex; align-items: center; gap: 1rem; padding: 1.4rem 1.5rem; }
    .prodx-head .ch-icon {
        width: 54px; height: 54px; border-radius: 14px; display: flex; align-items: center; justify-content: center;
        font-size: 1.4rem; color: #fff; background: linear-gradient(135deg, #1a1a2e, #b9770e); flex-shrink: 0;
        box-shadow: 0 4px 10px rgba(185,119,14,.3);
    }
    .prodx-head h5 { margin: 0; font-weight: 800; }
    .prodx-head p { margin: .1rem 0 0; color: #8a8a9d; font-size: .85rem; }
    .info-strip { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem; padding: 0 1.5rem 1.4rem; }
    .info-strip .is-item { border-left: 3px solid #f5e7cd; padding-left: .7rem; }
    .info-strip .is-item .is-label { font-size: .68rem; text-transform: uppercase; letter-spacing: .03em; color: #adb5bd; font-weight: 700; }
    .info-strip .is-item .is-value { font-size: .95rem; font-weight: 700; color: #1a1a2e; }
    body.dark-mode .info-strip .is-item { border-left-color: #3a2f1a; }
    body.dark-mode .info-strip .is-item .is-value { color: #f0f0f7; }
    @media (max-width: 767px) { .info-strip { grid-template-columns: repeat(2, 1fr); } }

    .prodx-note { background: rgba(185,119,14,.07); border-left: 3px solid #b9770e; border-radius: 8px; padding: .9rem 1.1rem; display: flex; gap: .6rem; align-items: flex-start; }
    .prodx-note i { color: #b9770e; margin-top: .15rem; }
    body.dark-mode .prodx-note { background: rgba(185,119,14,.16); }

    .prodx-table thead th { background: rgba(185,119,14,.06) !important; }
    body.dark-mode .prodx-table thead th { background: rgba(185,119,14,.12) !important; }

    .action-icon-chip {
        width: 34px; height: 34px; border-radius: 9px; display: inline-flex; align-items: center; justify-content: center;
        background: rgba(52,152,219,.14); color: #2170a3; margin-right: .6rem; flex-shrink: 0;
    }
</style>
<div class="row justify-content-center">
    <div class="col-lg-9">
        <div class="card prod-form-card mb-3">
            <div class="prodx-topbar"></div>
            <div class="prodx-head">
                <div class="ch-icon"><i class="fas fa-industry"></i></div>
                <div class="flex-grow-1">
                    <h5>Producción #{{ $produccion->id }}</h5>
                    <p>{{ $produccion->producto->nombre }}</p>
                </div>
                <span class="badge-soft badge-soft-success" style="font-size:.85rem;">{{ $produccion->cantidad }} unidades</span>
            </div>

            <div class="info-strip">
                <div class="is-item">
                    <div class="is-label">Producto</div>
                    <div class="is-value">{{ $produccion->producto->nombre }}</div>
                </div>
                <div class="is-item">
                    <div class="is-label">Fecha</div>
                    <div class="is-value">{{ $produccion->fecha->format('d/m/Y') }}</div>
                </div>
                <div class="is-item">
                    <div class="is-label">Registrado por</div>
                    <div class="is-value">{{ $produccion->usuario->nombre ?? '—' }}</div>
                </div>
            </div>

            @if($produccion->observacion)
            <div class="px-4 pb-3">
                <div class="prodx-note"><i class="fas fa-comment-alt"></i><span>{{ $produccion->observacion }}</span></div>
            </div>
            @endif

            @if($produccion->producto->receta)
            @php
                $receta = $produccion->producto->receta;
                $lotes  = $produccion->cantidad / max($receta->rendimiento, 1);
            @endphp
            <div class="px-3 pb-2">
                <h6 class="font-weight-bold mb-2 px-1 d-flex align-items-center">
                    <span class="action-icon-chip"><i class="fas fa-flask"></i></span>Materia prima consumida
                </h6>
                <div class="table-card" style="box-shadow:none;border:1px solid #f0f0f0;">
                    <div class="table-responsive">
                    <table class="table table-modern prodx-table mb-0">
                        <thead>
                            <tr>
                                <th>Ingrediente</th>
                                <th class="text-right">Por lote</th>
                                <th class="text-right">Total consumido</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($receta->detalles as $d)
                            <tr>
                                <td class="row-title">{{ $d->materia->nombre }}</td>
                                <td class="text-right">{{ $d->cantidad }} {{ $d->materia->unidad->abreviatura }}</td>
                                <td class="text-right font-weight-bold">
                                    {{ round($d->cantidad * $lotes, 3) }} {{ $d->materia->unidad->abreviatura }}
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                    </div>
                </div>
            </div>
            @endif

            <div class="px-3 pb-3 pt-2 d-flex justify-content-end" style="gap:.5rem;">
                @if($produccion->producto->stock_actual >= $produccion->cantidad)
                    <form action="{{ route('produccion.destroy', $produccion) }}" method="POST" class="js-confirm d-inline"
                        data-confirm-title="¿Eliminar esta producción?"
                        data-confirm="Se quitarán {{ $produccion->cantidad }} unidades de &quot;{{ $produccion->producto->nombre }}&quot; del stock y se devolverán los insumos que se habían descontado. Esta acción NO se puede deshacer.">
                        @csrf @method('DELETE')
                        <button class="btn btn-outline-danger"><i class="fas fa-trash-alt mr-1"></i>Eliminar</button>
                    </form>
                @else
                    <button type="button" class="btn btn-outline-secondary is-locked js-blocked"
                        data-blocked-title="No se puede eliminar esta producción"
                        data-blocked-message="Ya se vendieron o movieron unidades de &quot;{{ $produccion->producto->nombre }}&quot; desde que se registró. Eliminarla dejaría el stock en negativo.">
                        <i class="fas fa-trash-alt mr-1"></i>Eliminar
                    </button>
                @endif
            </div>
        </div>
    </div>
</div>
