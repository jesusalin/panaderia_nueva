@if($lotes->count() > 0)
<div class="list-rows">
    @foreach($lotes as $l)
        @php
            $clase = $l->exactitud_promedio >= 90 ? 'good' : ($l->exactitud_promedio >= 70 ? 'mid' : 'bad');
        @endphp
        <a href="{{ route('conteo-fisico.detalle', $l->lote) }}" class="list-row" style="text-decoration:none;">
            <div class="lr-icon info"><i class="fas fa-clipboard-list"></i></div>

            <div class="lr-main">
                <div class="lr-title">Conteo del {{ \Carbon\Carbon::parse($l->fecha)->locale('es')->translatedFormat('d \d\e F, Y') }}</div>
                <div class="lr-subtitle">
                    <i class="fas fa-user mr-1"></i>{{ $l->usuario->apodo ?? $l->usuario->nombre }}
                    &middot; {{ \Carbon\Carbon::parse($l->fecha)->format('H:i') }}
                </div>
            </div>

            <div class="lr-meta">
                <div class="lm-item">
                    <span class="lm-label">Insumos</span>
                    <span class="lm-value">{{ $l->total_items }}</span>
                </div>
                <div class="lm-item">
                    <span class="lm-label">Con ajuste</span>
                    <span class="lm-value {{ $l->total_ajustados > 0 ? 'text-danger' : 'text-success' }}">{{ $l->total_ajustados }}</span>
                </div>
            </div>

            <div class="lr-side">
                <span class="cf-exact-badge {{ $clase }}">{{ $l->exactitud_promedio }}%</span>
            </div>
        </a>
    @endforeach
</div>
<div class="mt-3">{{ $lotes->links() }}</div>
@else
<div class="empty-state">
    <i class="fas fa-clipboard-list"></i>
    <p>{{ request('filtro') ? 'No hay conteos en este filtro' : 'Todavía no has registrado ningún conteo físico.' }}</p>
    @if(!request('filtro'))
        <a href="{{ route('conteo-fisico.index') }}" class="btn btn-primary"><i class="fas fa-plus mr-1"></i>Hacer el primero</a>
    @endif
</div>
@endif
