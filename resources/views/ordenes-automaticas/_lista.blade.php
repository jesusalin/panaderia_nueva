@if($ordenes->count() > 0)
<div class="list-rows">
    @foreach($ordenes as $o)
        @php
            $iconClase = ['pendiente' => 'danger', 'convertida' => 'success', 'descartada' => ''][$o->estado];
            $badgeClase = ['pendiente'=>'badge-soft-warning','convertida'=>'badge-soft-success','descartada'=>'badge-soft-secondary'][$o->estado];
            $costoEstimado = $o->cantidad_sugerida * ($o->materia->costo_unitario ?? 0);
            $referencia = max($o->stock_minimo * 2, 0.001);
            $porcentaje = min(100, round(($o->stock_al_generar / $referencia) * 100));
        @endphp
        <div class="list-row">
            <div class="lr-icon {{ $iconClase }}"><i class="fas fa-wheat-awn"></i></div>

            <div class="lr-main">
                <div class="lr-title">{{ $o->materia->nombre }}</div>
                <div class="lr-subtitle">
                    @if($o->proveedor)
                        <i class="fas fa-truck mr-1"></i>{{ $o->proveedor->nombre }}
                    @else
                        <span class="oa-sinprov"><i class="fas fa-exclamation-triangle mr-1"></i>Sin proveedor asignado</span>
                    @endif
                    &middot; {{ $o->created_at->format('d/m/Y H:i') }}
                </div>
                <div class="stock-gauge" style="max-width:220px;">
                    <div class="sg-track"><div class="sg-fill bajo" style="width: {{ $porcentaje }}%;"></div></div>
                </div>
            </div>

            <div class="lr-meta">
                <div class="lm-item">
                    <span class="lm-label">Stock al generar</span>
                    <span class="lm-value text-danger">{{ number_format($o->stock_al_generar, 2) }} {{ $o->materia->unidad->abreviatura }}</span>
                </div>
                <div class="lm-item">
                    <span class="lm-label">Mínimo</span>
                    <span class="lm-value">{{ number_format($o->stock_minimo, 2) }} {{ $o->materia->unidad->abreviatura }}</span>
                </div>
                <div class="lm-item">
                    <span class="lm-label">Sugerido</span>
                    <span class="lm-value text-success">{{ number_format($o->cantidad_sugerida, 2) }} {{ $o->materia->unidad->abreviatura }}</span>
                </div>
            </div>

            <div class="lr-side">
                <div class="text-right">
                    <span class="badge-soft {{ $badgeClase }} d-block mb-1">{{ ucfirst($o->estado) }}</span>
                    @if($costoEstimado > 0)
                        <span class="lr-amount" style="font-size:.85rem;">S/ {{ number_format($costoEstimado, 2) }}</span>
                    @endif
                </div>

                @if($o->estado === 'pendiente')
                    <div class="btn-icon-group">
                        <form action="{{ route('ordenes-automaticas.convertir', $o) }}" method="POST" class="js-confirm"
                            data-confirm-title="¿Generar compra?"
                            data-confirm="Se creará una compra a {{ $o->proveedor->nombre ?? 'este proveedor' }} por {{ number_format($o->cantidad_sugerida, 2) }} {{ $o->materia->unidad->abreviatura }} de {{ $o->materia->nombre }}.">
                            @csrf
                            <button class="btn btn-icon btn-success" title="Convertir en compra">
                                <i class="fas fa-shopping-cart"></i>
                            </button>
                        </form>
                        <form action="{{ route('ordenes-automaticas.descartar', $o) }}" method="POST" class="js-confirm"
                            data-confirm-title="¿Descartar orden?"
                            data-confirm="No se generará ninguna compra para {{ $o->materia->nombre }} por ahora.">
                            @csrf
                            <button class="btn btn-icon btn-secondary" title="Descartar">
                                <i class="fas fa-times"></i>
                            </button>
                        </form>
                    </div>
                @elseif($o->estado === 'convertida')
                    <a href="{{ route('compras.show', $o->id_compra) }}" class="btn btn-sm btn-info">
                        <i class="fas fa-eye mr-1"></i>Ver compra
                    </a>
                @endif
            </div>
        </div>
    @endforeach
</div>
<div class="mt-3">{{ $ordenes->links() }}</div>
@else
<div class="empty-state">
    <i class="fas fa-robot"></i>
    <p>{{ request('estado') ? 'No hay órdenes en este estado' : 'No hay órdenes automáticas generadas todavía' }}</p>
</div>
@endif
