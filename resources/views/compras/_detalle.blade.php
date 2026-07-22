<style>
    .compra-topbar { height: 5px; background: linear-gradient(90deg, #b5451b, #e8935f); }
    .compra-head { display: flex; align-items: center; gap: 1rem; padding: 1.4rem 1.5rem; }
    .compra-head .ch-icon {
        width: 54px; height: 54px; border-radius: 14px; display: flex; align-items: center; justify-content: center;
        font-size: 1.4rem; color: #fff; background: linear-gradient(135deg, #1a1a2e, #b5451b); flex-shrink: 0;
        box-shadow: 0 4px 10px rgba(181,69,27,.3);
    }
    .compra-head h5 { margin: 0; font-weight: 800; }
    .compra-head p { margin: .1rem 0 0; color: #8a8a9d; font-size: .85rem; }
    .info-strip { display: grid; grid-template-columns: repeat(4, 1fr); gap: 1rem; padding: 0 1.5rem 1.4rem; }
    .info-strip .is-item { border-left: 3px solid #f0e4dc; padding-left: .7rem; }
    .info-strip .is-item .is-label { font-size: .68rem; text-transform: uppercase; letter-spacing: .03em; color: #adb5bd; font-weight: 700; }
    .info-strip .is-item .is-value { font-size: .95rem; font-weight: 700; color: #1a1a2e; }
    body.dark-mode .info-strip .is-item { border-left-color: #3a2a22; }
    body.dark-mode .info-strip .is-item .is-value { color: #f0f0f7; }
    @media (max-width: 767px) { .info-strip { grid-template-columns: repeat(2, 1fr); } }

    .compra-note { background: rgba(181,69,27,.06); border-left: 3px solid #b5451b; border-radius: 8px; padding: .9rem 1.1rem; display: flex; gap: .6rem; align-items: flex-start; }
    .compra-note i { color: #b5451b; margin-top: .15rem; }
    body.dark-mode .compra-note { background: rgba(181,69,27,.14); }

    .compra-table thead th { background: rgba(181,69,27,.05) !important; }
    body.dark-mode .compra-table thead th { background: rgba(181,69,27,.1) !important; }

    .action-icon-chip {
        width: 34px; height: 34px; border-radius: 9px; display: inline-flex; align-items: center; justify-content: center;
        background: rgba(181,69,27,.12); color: #b5451b; margin-right: .6rem; flex-shrink: 0;
    }
</style>
<div class="row">
    <div class="col-lg-8">
        <div class="card prod-form-card mb-3">
            <div class="compra-topbar"></div>
            <div class="compra-head">
                <div class="ch-icon"><i class="fas fa-file-invoice"></i></div>
                <div class="flex-grow-1">
                    <h5>Compra #{{ $compra->id }}</h5>
                    <p>{{ $compra->numero_doc ?? 'Sin documento' }}</p>
                </div>
                @php
                    $badge = ['pendiente'=>'badge-soft-warning','recibida'=>'badge-soft-success','anulada'=>'badge-soft-danger'][$compra->estado];
                @endphp
                <span class="badge-soft {{ $badge }}" style="font-size:.85rem;">{{ strtoupper($compra->estado) }}</span>
            </div>

            <div class="info-strip">
                <div class="is-item">
                    <div class="is-label">Proveedor</div>
                    <div class="is-value">{{ $compra->proveedor->nombre }}</div>
                </div>
                <div class="is-item">
                    <div class="is-label">Fecha</div>
                    <div class="is-value">{{ \Carbon\Carbon::parse($compra->fecha_compra)->format('d/m/Y') }}</div>
                </div>
                <div class="is-item">
                    <div class="is-label">Registrado por</div>
                    <div class="is-value">{{ $compra->usuario->nombre }}</div>
                </div>
                <div class="is-item">
                    <div class="is-label">Total</div>
                    <div class="is-value text-warning">S/ {{ number_format($compra->total, 2) }}</div>
                </div>
            </div>

            @if($compra->observaciones)
            <div class="px-4 pb-3">
                <div class="compra-note"><i class="fas fa-comment-alt"></i><span>{{ $compra->observaciones }}</span></div>
            </div>
            @endif

            <div class="table-card m-3 mt-0" style="box-shadow:none;border:1px solid #f0f0f0;">
                <div class="table-responsive">
                <table class="table table-modern compra-table mb-0">
                    <thead>
                        <tr>
                            <th>Ingrediente</th>
                            <th class="text-center">Cantidad</th>
                            <th class="text-right">Precio Unit.</th>
                            <th class="text-right">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($compra->detalles as $d)
                        <tr>
                            <td class="row-title">{{ $d->materia->nombre }}</td>
                            <td class="text-center">{{ number_format($d->cantidad, 3) }} {{ $d->materia->unidad->abreviatura }}</td>
                            <td class="text-right">S/ {{ number_format($d->precio_unitario, 2) }}</td>
                            <td class="text-right font-weight-bold">S/ {{ number_format($d->subtotal, 2) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                </div>
                <div class="p-3" style="border-top:1px solid #f0f0f0;">
                    <div class="row justify-content-end">
                        <div class="col-md-5">
                            <div class="d-flex justify-content-between text-muted small mb-1">
                                <span>Subtotal</span><span>S/ {{ number_format($compra->subtotal, 2) }}</span>
                            </div>
                            <div class="d-flex justify-content-between text-muted small mb-2">
                                <span>IGV (18%)</span><span>S/ {{ number_format($compra->igv, 2) }}</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center" style="border-top:1px solid #e5e0da;padding-top:.5rem;">
                                <span class="font-weight-bold">Total</span>
                                <span class="font-weight-bold text-warning" style="font-size:1.2rem;">S/ {{ number_format($compra->total, 2) }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card prod-form-card">
            <div class="compra-topbar"></div>
            <div class="prod-form-body">
                <h6 class="font-weight-bold mb-3 d-flex align-items-center">
                    <span class="action-icon-chip"><i class="fas fa-bolt"></i></span>Acciones
                </h6>

                @if($compra->estado === 'pendiente')
                <form action="{{ route('compras.recibir', $compra) }}" method="POST" class="js-confirm mb-2"
                    data-confirm-title="¿Confirmar recepción?"
                    data-confirm="Esto actualizará el stock de materia prima con lo comprado en este pedido.">
                    @csrf @method('PUT')
                    <button class="btn btn-success btn-block">
                        <i class="fas fa-check-circle mr-2"></i>Marcar como Recibida
                    </button>
                </form>
                <form action="{{ route('compras.anular', $compra) }}" method="POST" class="js-confirm mb-2"
                    data-confirm-title="¿Anular esta compra?"
                    data-confirm="Se cancelará este pedido a &quot;{{ $compra->proveedor->nombre }}&quot;. Útil si se registró con el proveedor equivocado. No se puede deshacer.">
                    @csrf @method('PUT')
                    <button class="btn btn-outline-danger btn-block">
                        <i class="fas fa-ban mr-2"></i>Anular Compra
                    </button>
                </form>
                @elseif($compra->estado === 'recibida')
                <form action="{{ route('compras.anular', $compra) }}" method="POST" class="js-confirm mb-2"
                    data-confirm-title="¿Anular esta compra ya recibida?"
                    data-confirm="Esta compra ya sumó stock de materia prima. Al anularla, el sistema intentará revertir ese stock. Si parte de esos insumos ya se usó en producción, no se podrá anular. No se puede deshacer.">
                    @csrf @method('PUT')
                    <button class="btn btn-outline-danger btn-block">
                        <i class="fas fa-ban mr-2"></i>Anular Compra (revierte stock)
                    </button>
                </form>
                @else
                <div class="compra-note mb-2"><i class="fas fa-info-circle"></i><span>Esta compra ya fue procesada</span></div>
                @endif

                <hr class="detalle-divider">

                <a href="{{ route('compras.create') }}" class="btn btn-outline-secondary btn-block">
                    <i class="fas fa-plus mr-2"></i>Nueva compra
                </a>
            </div>
        </div>
    </div>
</div>
