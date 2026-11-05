{{-- Historial de órdenes (convertidas, descartadas o todas) --}}
@php $C = \App\Support\Cantidad::class; @endphp
@if($ordenes->count() > 0)
<div class="list-rows">
    @foreach($ordenes as $o)
        @php
            $u = $o->materia->unidad->abreviatura ?? '';
            $icono = ['pendiente' => ['warning', 'fa-clock'], 'convertida' => ['success', 'fa-check'], 'descartada' => ['', 'fa-ban']][$o->estado];
            $badge = ['pendiente' => 'badge-soft-warning', 'convertida' => 'badge-soft-success', 'descartada' => 'badge-soft-secondary'][$o->estado];
            $texto = ['pendiente' => 'Pendiente', 'convertida' => 'Convertida en compra', 'descartada' => 'Descartada'][$o->estado];
        @endphp
        <div class="list-row">
            <div class="lr-icon {{ $icono[0] }}"><i class="fas {{ $icono[1] }}"></i></div>
            <div class="lr-main">
                <div class="lr-title">{{ $o->materia->nombre ?? '—' }} <small class="text-muted font-weight-normal">orden #{{ $o->id }}</small></div>
                <div class="lr-subtitle">
                    @if($o->proveedor)<i class="fas fa-truck mr-1"></i>{{ $o->proveedor->nombre }}@else<span class="text-danger">Sin proveedor</span>@endif
                    · creada {{ $o->created_at->format('d/m/Y H:i') }}
                    @if($o->estado !== 'pendiente') · {{ strtolower($texto) }} {{ $o->updated_at->format('d/m/Y H:i') }}@endif
                </div>
            </div>
            <div class="lr-meta">
                <div class="lm-item"><span class="lm-label">Stock al detectar</span><span class="lm-value text-danger">{{ $C::conUnidad($o->stock_al_generar, $u) }}</span></div>
                <div class="lm-item"><span class="lm-label">Mínimo</span><span class="lm-value">{{ $C::conUnidad($o->stock_minimo, $u) }}</span></div>
                <div class="lm-item"><span class="lm-label">{{ $o->estado === 'convertida' ? 'Pedido' : 'Sugerido' }}</span><span class="lm-value">{{ $C::conUnidad($o->cantidad_sugerida, $u) }}</span></div>
            </div>
            <div class="lr-side">
                <span class="badge-soft {{ $badge }}">{{ $texto }}</span>
                @if($o->estado === 'convertida' && $o->id_compra && auth()->user()->hasPermiso('compras.compras'))
                    <a href="{{ route('compras.show', $o->id_compra) }}" class="btn btn-sm btn-light"><i class="fas fa-eye mr-1"></i>Compra {{ $o->compra->codigo ?? '#' . $o->id_compra }}</a>
                @elseif($o->estado === 'pendiente')
                    <a href="{{ route('ordenes-automaticas.index') }}" class="btn btn-sm btn-light">Ir a pendientes</a>
                @endif
            </div>
        </div>
    @endforeach
</div>
<div class="mt-3">{{ $ordenes->links() }}</div>
@else
<div class="empty-state">
    <i class="fas fa-robot"></i>
    <p>No hay órdenes en esta lista.</p>
</div>
@endif
