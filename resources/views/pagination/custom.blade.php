@if ($paginator->hasPages())
<nav aria-label="Paginación" class="pg-nav">
    <ul class="pg-list">
        {{-- Anterior --}}
        @if ($paginator->onFirstPage())
            <li class="pg-item disabled" aria-disabled="true"><span class="pg-link"><i class="fas fa-chevron-left"></i></span></li>
        @else
            <li class="pg-item"><a class="pg-link" href="{{ $paginator->previousPageUrl() }}" rel="prev"><i class="fas fa-chevron-left"></i></a></li>
        @endif

        {{-- Números de página --}}
        @foreach ($elements as $element)
            @if (is_string($element))
                <li class="pg-item disabled"><span class="pg-link pg-dots">{{ $element }}</span></li>
            @endif

            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page == $paginator->currentPage())
                        <li class="pg-item active"><span class="pg-link">{{ $page }}</span></li>
                    @else
                        <li class="pg-item"><a class="pg-link" href="{{ $url }}">{{ $page }}</a></li>
                    @endif
                @endforeach
            @endif
        @endforeach

        {{-- Siguiente --}}
        @if ($paginator->hasMorePages())
            <li class="pg-item"><a class="pg-link" href="{{ $paginator->nextPageUrl() }}" rel="next"><i class="fas fa-chevron-right"></i></a></li>
        @else
            <li class="pg-item disabled" aria-disabled="true"><span class="pg-link"><i class="fas fa-chevron-right"></i></span></li>
        @endif
    </ul>

    <span class="pg-summary">
        Mostrando <strong>{{ $paginator->firstItem() }}</strong>–<strong>{{ $paginator->lastItem() }}</strong>
        de <strong>{{ $paginator->total() }}</strong> resultados
    </span>
</nav>
@endif
