<style>
    .venta-topbar { height: 5px; background: linear-gradient(90deg, #1e8e5a, #6ee7a5); }
    .venta-head { display: flex; align-items: center; gap: 1rem; padding: 1.4rem 1.5rem; }
    .venta-head .ch-icon {
        width: 54px; height: 54px; border-radius: 14px; display: flex; align-items: center; justify-content: center;
        font-size: 1.4rem; color: #fff; background: linear-gradient(135deg, #1a1a2e, #1e8e5a); flex-shrink: 0;
        box-shadow: 0 4px 10px rgba(30,142,90,.3);
    }
    .venta-head h5 { margin: 0; font-weight: 800; }
    .venta-head p { margin: .1rem 0 0; color: #8a8a9d; font-size: .85rem; }
    .info-strip { display: grid; grid-template-columns: repeat(4, 1fr); gap: 1rem; padding: 0 1.5rem 1.4rem; }
    .info-strip .is-item { border-left: 3px solid #dcf0e4; padding-left: .7rem; }
    .info-strip .is-item .is-label { font-size: .68rem; text-transform: uppercase; letter-spacing: .03em; color: #adb5bd; font-weight: 700; }
    .info-strip .is-item .is-value { font-size: .95rem; font-weight: 700; color: #1a1a2e; }
    body.dark-mode .info-strip .is-item { border-left-color: #1e3a2c; }
    body.dark-mode .info-strip .is-item .is-value { color: #f0f0f7; }
    @media (max-width: 767px) { .info-strip { grid-template-columns: repeat(2, 1fr); } }

    .venta-note { background: rgba(30,142,90,.06); border-left: 3px solid #1e8e5a; border-radius: 8px; padding: .9rem 1.1rem; display: flex; gap: .6rem; align-items: flex-start; }
    .venta-note i { color: #1e8e5a; margin-top: .15rem; }
    body.dark-mode .venta-note { background: rgba(30,142,90,.14); }

    .venta-table thead th { background: rgba(30,142,90,.05) !important; }
    body.dark-mode .venta-table thead th { background: rgba(30,142,90,.1) !important; }

    .action-icon-chip {
        width: 34px; height: 34px; border-radius: 9px; display: inline-flex; align-items: center; justify-content: center;
        background: rgba(30,142,90,.12); color: #1e8e5a; margin-right: .6rem; flex-shrink: 0;
    }

    .prod-form-card { border: none; border-radius: 14px; box-shadow: 0 4px 24px rgba(0,0,0,.06); overflow: hidden; }
    .prod-form-body { padding: 1.5rem; }

    @media print {
        .col-lg-8.venta-print-col { flex: 0 0 100% !important; max-width: 100% !important; }
        .venta-topbar { background: #000 !important; height: 2px !important; }
        .ch-icon { background: #f0f0f0 !important; color: #000 !important; box-shadow: none !important; }
    }
</style>
<div class="row">
    <div class="col-lg-8 venta-print-col">
        <div class="card prod-form-card mb-3">
            <div class="venta-topbar"></div>
            <div class="venta-head">
                <div class="ch-icon"><i class="fas fa-file-invoice"></i></div>
                <div class="flex-grow-1">
                    <h5>Comprobante {{ $venta->numero_venta }}</h5>
                    <p>{{ $venta->cliente->nombre ?? 'Cliente general' }}</p>
                </div>
                <span class="badge-soft {{ $venta->estado === 'completada' ? 'badge-soft-success' : 'badge-soft-danger' }}" style="font-size:.85rem;">
                    {{ strtoupper($venta->estado) }}
                </span>
            </div>

            <div class="info-strip">
                <div class="is-item">
                    <div class="is-label">Cliente</div>
                    <div class="is-value">{{ $venta->cliente->nombre ?? 'General' }}</div>
                </div>
                <div class="is-item">
                    <div class="is-label">Fecha</div>
                    <div class="is-value">{{ $venta->fecha_venta->format('d/m/Y H:i') }}</div>
                </div>
                <div class="is-item">
                    <div class="is-label">Vendedor</div>
                    <div class="is-value">{{ $venta->usuario->nombre }}</div>
                </div>
                <div class="is-item">
                    <div class="is-label">Total</div>
                    <div class="is-value text-success">S/ {{ number_format($venta->total, 2) }}</div>
                </div>
            </div>

            <div class="table-card m-3 mt-0" style="box-shadow:none;border:1px solid #f0f0f0;">
                <div class="table-responsive">
                <table class="table table-modern venta-table mb-0">
                    <thead>
                        <tr>
                            <th>Producto</th>
                            <th class="text-center">Cant.</th>
                            <th class="text-right">P. Unit.</th>
                            <th class="text-right">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($venta->detalles as $d)
                        <tr>
                            <td class="row-title">{{ $d->producto->nombre }}</td>
                            <td class="text-center">{{ $d->cantidad }}</td>
                            <td class="text-right">S/ {{ number_format($d->precio_unitario, 2) }}</td>
                            <td class="text-right font-weight-bold">S/ {{ number_format($d->subtotal, 2) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                </div>
                <div class="p-3" style="border-top:1px solid #f0f0f0;">
                    <div class="row justify-content-end">
                        <div class="col-md-6">
                            <div class="d-flex justify-content-between text-muted small mb-1">
                                <span>Subtotal</span><span>S/ {{ number_format($venta->subtotal, 2) }}</span>
                            </div>
                            <div class="d-flex justify-content-between text-muted small mb-2">
                                <span>IGV (18%)</span><span>S/ {{ number_format($venta->igv, 2) }}</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center" style="border-top:1px solid #e5e0da;padding-top:.5rem;">
                                <span class="font-weight-bold">Total</span>
                                <span class="font-weight-bold text-success" style="font-size:1.2rem;">S/ {{ number_format($venta->total, 2) }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4 no-print">
        <div class="card prod-form-card">
            <div class="venta-topbar"></div>
            <div class="prod-form-body">
                <h6 class="font-weight-bold mb-3 d-flex align-items-center">
                    <span class="action-icon-chip"><i class="fas fa-bolt"></i></span>Acciones
                </h6>

                @if($venta->estado === 'completada')
                <form action="{{ route('ventas.anular', $venta) }}" method="POST" class="js-confirm mb-2"
                    data-confirm-title="¿Anular esta venta?"
                    data-confirm="Esta acción no se puede deshacer.">
                    @csrf @method('PUT')
                    <button class="btn btn-outline-danger btn-block">
                        <i class="fas fa-ban mr-2"></i>Anular Venta
                    </button>
                </form>
                @else
                <div class="venta-note mb-2"><i class="fas fa-info-circle"></i><span>Esta venta ya fue anulada</span></div>
                @endif

                <hr class="detalle-divider">

                <div class="dropdown mb-2">
                    <button type="button" class="btn btn-outline-secondary btn-block dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <i class="fas fa-print mr-2"></i>Imprimir
                    </button>
                    <div class="dropdown-menu w-100">
                        <a class="dropdown-item" href="#" onclick="imprimirDetalle('a4'); return false;">
                            <i class="fas fa-file mr-2 text-muted"></i>Hoja completa (A4)
                        </a>
                        <a class="dropdown-item" href="#" onclick="imprimirDetalle('boleta'); return false;">
                            <i class="fas fa-receipt mr-2 text-muted"></i>Boleta / Ticket angosto
                        </a>
                    </div>
                </div>
                <a href="{{ route('ventas.create') }}" class="btn btn-success btn-block">
                    <i class="fas fa-plus mr-2"></i>Nueva venta
                </a>
            </div>
        </div>
    </div>
</div>
