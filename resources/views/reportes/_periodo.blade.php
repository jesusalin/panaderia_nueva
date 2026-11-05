{{-- Selector de periodo de los reportes. Variables: $ruta, $periodo, $extra (otros filtros a conservar) --}}
@php $extra = array_filter($extra ?? [], fn ($v) => $v !== null && $v !== ''); @endphp
<div class="d-flex flex-wrap align-items-center mb-3" style="gap:.5rem">
    <div class="inv-chips mb-0">
        @foreach(\App\Http\Controllers\ReportesGestionController::PERIODOS as $clave => $texto)
            <a href="{{ route($ruta, $extra + ['periodo' => $clave]) }}" class="inv-chip {{ $periodo === $clave ? 'activo' : '' }}">{{ $texto }}</a>
        @endforeach
    </div>
    <details class="ml-1" {{ $periodo === '' ? 'open' : '' }}>
        <summary class="btn btn-light btn-sm mb-0">Fechas exactas</summary>
        <form method="GET" action="{{ route($ruta) }}" class="d-inline-flex align-items-center mt-1" style="gap:.35rem">
            @foreach($extra as $k => $v)<input type="hidden" name="{{ $k }}" value="{{ $v }}">@endforeach
            <input type="date" name="desde" class="form-control form-control-sm" value="{{ request('desde') }}" aria-label="Desde">
            <span class="small text-muted">al</span>
            <input type="date" name="hasta" class="form-control form-control-sm" value="{{ request('hasta') }}" max="{{ today()->toDateString() }}" aria-label="Hasta">
            <button class="btn btn-sm btn-primary">Ver</button>
        </form>
    </details>
</div>
