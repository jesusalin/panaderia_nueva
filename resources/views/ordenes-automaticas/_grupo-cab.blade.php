{{-- Abre un grupo de órdenes de un proveedor (el </div> lo cierra quien lo incluye) --}}
<div class="oa-grupo" data-grupo="{{ $gid }}">
    <div class="oa-cab">
        <label class="mb-0" title="Marcar / desmarcar todas"><input type="checkbox" class="oa-todas" checked></label>
        <div class="pv-avatar">{{ $gini }}</div>
        <div>
            <div class="oc-nombre">{{ $gnombre }}</div>
            <div class="oc-sub"><span data-n-grupo></span>{{ $gtel ? ' · ' . $gtel : '' }}</div>
        </div>
        <div class="oc-der">
            <span class="oc-total" data-total-grupo>S/ 0.00</span>
            <button type="button" class="btn btn-sm btn-success oa-solo"><i class="fas fa-cart-plus mr-1"></i>Pedir solo a este proveedor</button>
        </div>
    </div>
