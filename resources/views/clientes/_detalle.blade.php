<style>
    .cli-head { display: flex; align-items: center; gap: 1rem; padding: 1.4rem 1.5rem; }
    .cli-head .ch-icon {
        width: 60px; height: 60px; border-radius: 50%; display: flex; align-items: center; justify-content: center;
        font-size: 1.5rem; color: #fff; background: linear-gradient(135deg, #1a1a2e, #b5451b); flex-shrink: 0;
    }
    .cli-head h5 { margin: 0 0 .3rem; font-weight: 800; }
    .cli-head .cli-badges { display: flex; gap: .4rem; flex-wrap: wrap; }

    .cli-info-grid { padding: 0 1.5rem 1.4rem; display: grid; grid-template-columns: 1fr 1fr; gap: .9rem 1.2rem; }
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
</style>

<div class="row">
    <div class="col-md-4">
        <div class="card prod-form-card mb-3 mb-md-0">
            <div class="cli-head flex-column text-center">
                @php
                    $tipo = $cliente->tipo ?? 'particular';
                    $icono = ['bodega'=>'fa-store','colegio'=>'fa-school','restaurante'=>'fa-utensils','supermercado'=>'fa-cart-shopping'][$tipo] ?? 'fa-user';
                @endphp
                <div class="ch-icon"><i class="fas {{ $icono }}"></i></div>
                <h5>{{ $cliente->nombre }}</h5>
                <div class="cli-badges justify-content-center">
                    <span class="badge-soft badge-soft-info">{{ ucfirst($tipo) }}</span>
                    <span class="badge-soft {{ ($cliente->estado ?? 'activo') === 'activo' ? 'badge-soft-success' : 'badge-soft-secondary' }}">
                        {{ ($cliente->estado ?? 'activo') === 'activo' ? 'Activo' : 'Inactivo' }}
                    </span>
                </div>
            </div>

            <div class="cli-info-grid">
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
            <div class="p-3 pb-0"><h6 class="font-weight-bold mb-0"><i class="fas fa-receipt mr-2 text-primary"></i>Historial de Ventas</h6></div>
            <table class="table table-modern mb-0">
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
            @if($ventas->hasPages())
            <div class="p-3" style="border-top:1px solid #f0f0f0;">{{ $ventas->links() }}</div>
            @endif
        </div>
    </div>
</div>
