{{--
    Lista de movimientos agrupada por día (Movimientos de materia prima y de productos).
    Variables: $movimientos (paginados), $tipoItem ('materia'|'producto'), $motivos (clave => texto)
--}}
@php
    $C = \App\Support\Cantidad::class;
    $iconos = ['compra' => 'fa-truck', 'produccion' => 'fa-industry', 'merma' => 'fa-trash-alt', 'ajuste_manual' => 'fa-balance-scale', 'devolucion' => 'fa-undo', 'venta' => 'fa-cash-register'];
@endphp
@if($movimientos->count())
    @foreach($movimientos->groupBy(fn ($mv) => $mv->created_at->toDateString()) as $fecha => $delDia)
        @php $f = \Carbon\Carbon::parse($fecha); @endphp
        @php
            $suben = $delDia->filter(fn ($mv) => $mv->stock_despues > $mv->stock_antes)->count();
            $bajan = $delDia->filter(fn ($mv) => $mv->stock_despues < $mv->stock_antes)->count();
        @endphp
        <div class="inv-dia">
            <span><i class="far fa-calendar-alt mr-1"></i>{{ $f->isToday() ? 'Hoy · ' : ($f->isYesterday() ? 'Ayer · ' : '') }}{{ $f->locale('es')->translatedFormat('l d \d\e F') }}</span>
            <span class="id-flechas">
                @if($suben)<span class="sube" title="Movimientos que subieron el stock"><i class="fas fa-arrow-up"></i>{{ $suben }}</span>@endif
                @if($bajan)<span class="baja" title="Movimientos que bajaron el stock"><i class="fas fa-arrow-down"></i>{{ $bajan }}</span>@endif
                <span>{{ $delDia->count() }} movimiento{{ $delDia->count() == 1 ? '' : 's' }}</span>
            </span>
        </div>
        <div class="inv-lista">
            @foreach($delDia as $mv)
                @php
                    $item = $tipoItem === 'materia' ? $mv->materia : $mv->producto;
                    $u = $tipoItem === 'materia' ? ($item->unidad->abreviatura ?? '') : 'und';
                    $delta = $mv->stock_despues - $mv->stock_antes;
                    $clase = $mv->tipo === 'ajuste' ? 'ajuste' : ($delta >= 0 ? 'entrada' : 'salida');
                    $ruta = $item ? ($tipoItem === 'materia'
                        ? (auth()->user()->hasPermiso('inventario.materia_prima') ? route('materia-prima.show', $item) : null)
                        : (auth()->user()->hasPermiso('catalogo.productos') ? route('productos.show', $item) : null)) : null;
                    // Al tocar el movimiento se abre lo que lo originó (compra, venta, producción) o, si no, la ficha
                    $u_ = auth()->user(); $ref = $mv->referencia_id; $destino = null; $enVentana = false; $textoDestino = '';
                    if ($ref && $mv->motivo === 'produccion' && $u_->hasPermiso('produccion.produccion')) {
                        $destino = route('produccion.show', $ref); $enVentana = true; $textoDestino = 'Ver la producción';
                    } elseif ($ref && $tipoItem === 'materia' && in_array($mv->motivo, ['compra', 'devolucion'], true) && $u_->hasPermiso('compras.compras')) {
                        $destino = route('compras.show', $ref); $textoDestino = 'Ver la compra';
                    } elseif ($ref && $tipoItem === 'producto' && in_array($mv->motivo, ['venta', 'devolucion'], true) && $u_->hasPermiso('ventas')) {
                        $destino = route('ventas.show', $ref); $enVentana = true; $textoDestino = 'Ver la venta';
                    } elseif ($ruta) {
                        $destino = $ruta; $textoDestino = 'Ver la ficha de ' . $item->nombre;
                    }
                @endphp
                <div class="inv-mov {{ $destino ? 'clic-fila' : '' }}" @if($destino) data-href="{{ $destino }}" @if($enVentana) data-clic=".js-ver-detalle" @endif tabindex="0" title="{{ $textoDestino }}" aria-label="{{ $textoDestino }}" @endif>
                    @if($destino && $enVentana)<a href="{{ $destino }}" class="js-ver-detalle d-none" data-titulo-detalle="{{ $textoDestino }}" tabindex="-1" aria-hidden="true"></a>@endif
                    <span class="im-hora">{{ $mv->created_at->format('H:i') }}</span>
                    <span class="im-ic {{ $clase }}" title="{{ ucfirst($mv->tipo) }}"><i class="fas {{ $iconos[$mv->motivo] ?? 'fa-exchange-alt' }}"></i></span>
                    <span class="im-nombre">
                        @if($ruta)<a href="{{ $ruta }}">{{ $item->nombre }}</a>@else<b>{{ $item->nombre ?? '—' }}</b>@endif
                        <small>{{ $mv->usuario->nombre ?? '—' }}</small>
                    </span>
                    <span class="im-motivo">{{ $motivos[$mv->motivo] ?? ucfirst(str_replace('_', ' ', $mv->motivo)) }}
                        <small>{{ $mv->tipo === 'ajuste' ? 'Ajuste' : ucfirst($mv->tipo) }}</small></span>
                    @php $dir = $mv->tipo === 'ajuste' ? 'ajuste' : ($delta >= 0 ? 'sube' : 'baja'); @endphp
                    <span class="im-cant {{ $delta >= 0 ? 'inv-mas' : 'inv-menos' }}">
                        <span class="im-flecha {{ $dir }}" title="{{ $dir === 'ajuste' ? 'Ajuste' : ($dir === 'sube' ? 'El stock subió' : 'El stock bajó') }}">
                            <i class="fas {{ $dir === 'ajuste' ? 'fa-exchange-alt fa-rotate-90' : ($dir === 'sube' ? 'fa-arrow-up' : 'fa-arrow-down') }}"></i>
                        </span>{{ $C::conUnidad($delta, $u, true) }}
                    </span>
                    <span class="im-saldo">{{ $C::conUnidad($mv->stock_antes, $u) }} <i class="fas fa-long-arrow-alt-right im-paso {{ $dir }}"></i> <b>{{ $C::conUnidad($mv->stock_despues, $u) }}</b></span>
                    <span class="im-obs" title="{{ $mv->observacion }}">{{ $mv->observacion ?: '—' }}</span>
                </div>
            @endforeach
        </div>
    @endforeach
    <div class="mt-3">{{ $movimientos->links() }}</div>
@else
    <div class="empty-state">
        <i class="fas fa-exchange-alt"></i>
        <p>No hay movimientos con esos filtros. Prueba con otro periodo.</p>
    </div>
@endif
