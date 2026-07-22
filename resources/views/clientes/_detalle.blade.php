<style>
    .cli-topbar { height: 5px; background: {{ $cliente->color_tipo }}; }
    .cli-head { display: flex; align-items: center; gap: 1rem; padding: 1.4rem 1.5rem; }
    .cli-head .ch-icon {
        width: 60px; height: 60px; border-radius: 50%; display: flex; align-items: center; justify-content: center;
        font-size: 1.5rem; color: #fff; flex-shrink: 0; box-shadow: 0 4px 10px {{ $cliente->color_tipo }}55;
    }
    .cli-head h5 { margin: 0 0 .3rem; font-weight: 800; }
    .cli-head .cli-badges { display: flex; gap: .4rem; flex-wrap: wrap; }

    .cli-info-grid { padding: 0 1.5rem 1.4rem; display: grid; grid-template-columns: 1fr 1fr; gap: .9rem 1.2rem; }
    .cli-info-grid .ci-item { border-left: 3px solid {{ $cliente->color_tipo }}22; padding-left: .6rem; }
    .cli-info-grid .ci-item .ci-label { font-size: .68rem; text-transform: uppercase; letter-spacing: .03em; color: #adb5bd; font-weight: 700; }
    .cli-info-grid .ci-item .ci-value { font-size: .9rem; font-weight: 700; color: #1a1a2e; }
    body.dark-mode .cli-info-grid .ci-item .ci-value { color: #f0f0f7; }
    @media (max-width: 575px) { .cli-info-grid { grid-template-columns: 1fr; } }

    .cli-total-box {
        margin: 0 1.5rem 1.5rem; background: #f7f5f3; border-radius: 10px; padding: 1rem 1.2rem;
        display: flex; justify-content: space-between; align-items: center;
    }
    body.dark-mode .cli-total-box { background: #24243b; }
    .cli-total-box .ct-label { font-size: .78rem; color: #8a8a9d; font-weight: 700; }
    .cli-total-box .ct-value { font-weight: 800; font-size: 1.35rem; color: #1e8e5a; }
    body.dark-mode .cli-total-box .ct-value { color: #6ee7a5; }

    .cli-mini-stats { padding: 0 1.5rem 1.4rem; display: grid; grid-template-columns: 1fr 1fr; gap: .7rem; }
    .cli-mini-stats .cms-item { background: {{ $cliente->color_tipo }}10; border-radius: 10px; padding: .7rem .9rem; text-align: center; }
    body.dark-mode .cli-mini-stats .cms-item { background: {{ $cliente->color_tipo }}22; }
    .cli-mini-stats .cms-value { font-weight: 800; font-size: 1.05rem; color: #1a1a2e; }
    body.dark-mode .cli-mini-stats .cms-value { color: #f0f0f7; }
    .cli-mini-stats .cms-label { font-size: .68rem; color: #8a8a9d; font-weight: 700; text-transform: uppercase; letter-spacing: .03em; }

    .clientes-table thead th { background: {{ $cliente->color_tipo }}12 !important; }
    body.dark-mode .clientes-table thead th { background: {{ $cliente->color_tipo }}25 !important; }

    .action-icon-chip {
        width: 34px; height: 34px; border-radius: 9px; display: inline-flex; align-items: center; justify-content: center;
        background: {{ $cliente->color_tipo }}20; color: {{ $cliente->color_tipo }}; margin-right: .6rem; flex-shrink: 0;
    }
</style>

<div class="row">
    <div class="col-md-4">
        <div class="card prod-form-card mb-3 mb-md-0">
            <div class="cli-topbar"></div>
            <div class="cli-head flex-column text-center">
                <div class="ch-icon" style="background:{{ $cliente->color_tipo }};"><i class="fas {{ $cliente->icono }}"></i></div>
                <h5>{{ $cliente->nombre }}</h5>
                <div class="cli-badges justify-content-center">
                    <span class="badge-soft badge-soft-info">{{ $cliente->tipo_label }}</span>
                    <span class="badge-soft {{ ($cliente->estado ?? 'activo') === 'activo' ? 'badge-soft-success' : 'badge-soft-secondary' }}">
                        {{ ($cliente->estado ?? 'activo') === 'activo' ? 'Activo' : 'Inactivo' }}
                    </span>
                </div>
            </div>

            <div class="cli-mini-stats">
                <div class="cms-item">
                    <div class="cms-value">{{ $ventasCompletadas ?? $cliente->ventas_count ?? 0 }}</div>
                    <div class="cms-label">Ventas</div>
                </div>
                <div class="cms-item">
                    <div class="cms-value">S/ {{ number_format($ticketPromedio ?? 0, 2) }}</div>
                    <div class="cms-label">Ticket promedio</div>
                </div>
            </div>

            <div class="cli-info-grid">
                <div class="ci-item"><div class="ci-label">Cliente desde</div><div class="ci-value">{{ $cliente->created_at?->format('d/m/Y') ?? '—' }}</div></div>
                @if($cliente->ruc)
                <div class="ci-item"><div class="ci-label">RUC</div><div class="ci-value">{{ $cliente->ruc }}</div></div>
                @endif
                @if($cliente->dni)
                <div class="ci-item"><div class="ci-label">DNI</div><div class="ci-value">{{ $cliente->dni }}</div></div>
                @endif
                @if($cliente->telefono)
                <div class="ci-item"><div class="ci-label">Teléfono</div><div class="ci-value">{{ $cliente->telefono }}</div></div>
                @endif
                @if($cliente->email)
                <div class="ci-item"><div class="ci-label">Email</div><div class="ci-value">{{ $cliente->email }}</div></div>
                @endif
                @if($cliente->distrito)
                <div class="ci-item"><div class="ci-label">Distrito</div><div class="ci-value">{{ $cliente->distrito }}</div></div>
                @endif
                @if($cliente->direccion)
                <div class="ci-item" style="grid-column:1/-1;"><div class="ci-label">Dirección</div><div class="ci-value">{{ $cliente->direccion }}</div></div>
                @endif
                @if($cliente->referencia)
                <div class="ci-item" style="grid-column:1/-1;"><div class="ci-label">Referencia</div><div class="ci-value">{{ $cliente->referencia }}</div></div>
                @endif
            </div>

            <div class="cli-total-box">
                <span class="ct-label">Total comprado</span>
                <span class="ct-value">S/ {{ number_format($totalComprado, 2) }}</span>
            </div>

            <div class="px-3 pb-3">
                <a href="{{ route('clientes.edit', $cliente) }}" class="btn btn-warning btn-block">
                    <i class="fas fa-edit mr-1"></i>Editar
                </a>
            </div>
        </div>
    </div>

    <div class="col-md-8">
        <div class="table-card" style="box-shadow:none;border:1px solid #f0f0f0;">
            <div class="cli-topbar"></div>
            <div class="p-3 pb-0">
                <h6 class="font-weight-bold mb-0 d-flex align-items-center">
                    <span class="action-icon-chip"><i class="fas fa-receipt"></i></span>Historial de Ventas
                </h6>
            </div>
            <div class="table-responsive">
            <table class="table table-modern clientes-table mb-0">
                <thead>
                    <tr>
                        <th>N° Venta</th>
                        <th>Fecha</th>
                        <th class="text-right">Total</th>
                        <th>Pago</th>
                        <th class="text-center">Estado</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($ventas as $venta)
                    <tr>
                        <td class="row-title">{{ $venta->numero_venta }}</td>
                        <td>{{ \Carbon\Carbon::parse($venta->fecha_venta)->format('d/m/Y') }}</td>
                        <td class="text-right font-weight-bold">S/ {{ number_format($venta->total, 2) }}</td>
                        <td>{{ ucfirst($venta->tipo_pago) }}</td>
                        <td class="text-center">
                            <span class="badge-soft {{ $venta->estado === 'completada' ? 'badge-soft-success' : 'badge-soft-danger' }}">
                                {{ ucfirst($venta->estado) }}
                            </span>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="text-center text-muted py-4">Sin ventas registradas</td></tr>
                    @endforelse
                </tbody>
            </table>
            </div>
            @if($ventas->hasPages())
            <div class="p-3" style="border-top:1px solid #f0f0f0;">{{ $ventas->links() }}</div>
            @endif
        </div>
    </div>
</div>
