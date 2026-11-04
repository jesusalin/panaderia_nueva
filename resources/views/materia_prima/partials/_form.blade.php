{{--
    Formulario de insumo (nuevo y editar).
    Variables: $materiaPrima (null al crear), $unidades, $proveedores, $nombres (otros insumos),
    y al editar también $usos y $porDia (consumo diario de los últimos 14 días).
--}}
@php
    $editar = isset($materiaPrima) && $materiaPrima;
    $m = $editar ? $materiaPrima : null;
    $estado = old('estado', $m->estado ?? 'activo');
    $C = \App\Support\Cantidad::class;
    $porDia = $porDia ?? null;
    $usos = $usos ?? ['recetas' => 0, 'compras' => 0, 'movimientos' => 0];
    $idUnidad = old('id_unidad', $m->id_unidad ?? ($unidades->firstWhere('abreviatura', 'kg')->id ?? ''));
    // Unidades agrupadas para elegirlas con un clic
    $grupos = [
        'Peso'      => ['kg', 'g'],
        'Líquidos'  => ['L', 'mL'],
        'Por pieza' => [],
    ];
    foreach ($unidades as $un) {
        if (!in_array($un->abreviatura, ['kg', 'g', 'L', 'mL'], true)) $grupos['Por pieza'][] = $un->abreviatura;
    }
    $porAbrev = $unidades->keyBy('abreviatura');
@endphp
@include('partials.form-secciones')

@once
@push('styles')
<style>
    .mp-unidades { display: flex; flex-wrap: wrap; gap: 1rem; }
    .mp-ugrupo small { display: block; font-size: .68rem; font-weight: 800; letter-spacing: .05em; text-transform: uppercase; color: #8a8a9d; margin-bottom: .3rem; }
    .mp-ugrupo .chips { display: flex; gap: .35rem; flex-wrap: wrap; }
    .mp-uchip { margin: 0; cursor: pointer; }
    .mp-uchip input { position: absolute; opacity: 0; }
    .mp-uchip span { display: inline-flex; flex-direction: column; align-items: center; min-width: 64px; border: 2px solid #e9ecef; border-radius: 10px; padding: .35rem .6rem; line-height: 1.15; }
    .mp-uchip span b { font-size: 1rem; color: #1a1a2e; }
    .mp-uchip span em { font-style: normal; font-size: .68rem; color: #8a8a9d; }
    .mp-uchip input:checked + span { border-color: #b5451b; background: #fffaf7; }
    .mp-uchip input:checked + span b { color: #b5451b; }
    .mp-uchip input:focus-visible + span { box-shadow: 0 0 0 3px rgba(181,69,27,.2); }
    body.dark-mode .mp-uchip span { border-color: #33334d; }
    body.dark-mode .mp-uchip span b { color: #f0f0f7; }
    body.dark-mode .mp-uchip input:checked + span { background: rgba(181,69,27,.12); }

    .mp-aviso { display: none; gap: .55rem; align-items: flex-start; border-radius: 10px; padding: .55rem .75rem; font-size: .8rem; margin-top: .55rem; }
    .mp-aviso.ver { display: flex; }
    .mp-aviso i { margin-top: .15rem; }
    .mp-aviso.mal { background: #fdecea; color: #a93226; }
    .mp-aviso.ojo { background: #fff6e5; color: #8a5a00; }
    body.dark-mode .mp-aviso.mal { background: rgba(231,76,60,.15); color: #f1a097; }
    body.dark-mode .mp-aviso.ojo { background: rgba(212,136,15,.15); color: #f0c674; }
    .mp-aviso a { color: inherit; text-decoration: underline; cursor: pointer; }

    .mp-conv { display: block; min-height: 1.1em; font-size: .78rem; font-weight: 700; color: #b5451b; margin-top: .2rem; }
    .mp-sugerir { display: inline-flex; align-items: center; gap: .3rem; margin-top: .35rem; border: 1px dashed #d4a98f; background: #fffaf7; color: #a33d17; border-radius: 20px; padding: .2rem .7rem; font-size: .76rem; font-weight: 700; cursor: pointer; }
    .mp-sugerir:hover { background: #fdeee5; }
    body.dark-mode .mp-sugerir { background: rgba(181,69,27,.1); color: #f0a07a; border-color: #6d3a22; }
    .mp-nota { font-size: .8rem; color: #6c757d; background: #f7f5f3; border-radius: 10px; padding: .55rem .75rem; margin-top: .9rem; }
    .mp-nota b { color: #1a1a2e; }
    body.dark-mode .mp-nota { background: #24243b; color: #b0b0cc; }
    body.dark-mode .mp-nota b { color: #f0f0f7; }
    .prev-badges { position: absolute; top: .5rem; left: .5rem; display: flex; gap: .3rem; }
    .prev-badges span { background: rgba(0,0,0,.55); color: #fff; font-size: .66rem; font-weight: 700; border-radius: 20px; padding: .12rem .55rem; }
</style>
@endpush
@endonce

<div class="pfm-grid" id="pfmRaiz">
    <div>
        {{-- 1. Qué es --}}
        <div class="pfm-card">
            <div class="pfm-card-head"><span class="hi"><i class="fas fa-info"></i></span><h5>¿Qué insumo es?</h5></div>
            <div class="pfm-card-body">
                <div class="form-group">
                    <label>Nombre <span class="text-danger">*</span></label>
                    <input type="text" name="nombre" id="inNombre" maxlength="150" autocomplete="off" class="form-control @error('nombre') is-invalid @enderror"
                           value="{{ old('nombre', $m->nombre ?? '') }}" placeholder="Ej: Harina de trigo panadera" required {{ $editar ? '' : 'autofocus' }}>
                    @error('nombre')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    <div class="mp-aviso mal" id="avisoDuplicado"><i class="fas fa-times-circle"></i><span></span></div>
                    <div class="mp-aviso ojo" id="avisoParecido"><i class="fas fa-search"></i><span></span></div>
                </div>

                <label>¿En qué se mide? <span class="text-danger">*</span></label>
                <div class="mp-unidades">
                    @foreach($grupos as $titulo => $abrevs)
                        @if(count($abrevs))
                        <div class="mp-ugrupo">
                            <small>{{ $titulo }}</small>
                            <div class="chips">
                                @foreach($abrevs as $ab)
                                    @php $un = $porAbrev[$ab] ?? null; @endphp
                                    @if($un)
                                    <label class="mp-uchip">
                                        <input type="radio" name="id_unidad" value="{{ $un->id }}" data-abrev="{{ $un->abreviatura }}" {{ (string) $idUnidad === (string) $un->id ? 'checked' : '' }} required>
                                        <span><b>{{ $un->abreviatura }}</b><em>{{ $un->nombre }}</em></span>
                                    </label>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                        @endif
                    @endforeach
                </div>
                @error('id_unidad')<div class="text-danger small">{{ $message }}</div>@enderror
                <small class="text-muted d-block mt-2">Para harina, azúcar o leche conviene <b>kg</b> o <b>L</b>: luego puedes escribir 500 g o 250 mL y el sistema lo convierte.</small>
                @if($editar && ($usos['recetas'] || $usos['movimientos']))
                    <div class="mp-aviso ojo" id="avisoUnidad">
                        <i class="fas fa-exclamation-triangle"></i>
                        <span>Este insumo ya tiene {{ $usos['recetas'] }} receta(s) y {{ $usos['movimientos'] }} movimiento(s) en <b>{{ $m->unidad->abreviatura ?? '' }}</b>.
                        Si cambias la unidad, esas cantidades <b>no se convierten</b> (por ejemplo 2 kg pasaría a ser 2 g).
                        <a data-volver-unidad>Dejar {{ $m->unidad->abreviatura ?? '' }}</a></span>
                    </div>
                @endif
            </div>
        </div>

        {{-- 2. Costo --}}
        <div class="pfm-card">
            <div class="pfm-card-head"><span class="hi"><i class="fas fa-tags"></i></span><h5>¿Cuánto cuesta?</h5></div>
            <div class="pfm-card-body">
                <div class="pfm-precios">
                    <div>
                        <label>Costo por <span class="lbl-unidad">unidad</span> <span class="text-danger">*</span></label>
                        <div class="pfm-money"><span>S/</span>
                            <input type="number" name="costo_unitario" id="inCosto" step="0.01" min="0" required class="form-control @error('costo_unitario') is-invalid @enderror"
                                   value="{{ old('costo_unitario', $m->costo_unitario ?? '') }}" placeholder="0.00">
                        </div>
                        @error('costo_unitario')<div class="text-danger small">{{ $message }}</div>@enderror
                        <span class="mp-conv" id="convCosto"></span>
                        @if($editar)
                            <small class="text-muted">Se actualiza solo al recibir una compra con otro precio.</small>
                        @else
                            <small class="text-muted">Lo que pagas al proveedor por 1 <span class="lbl-unidad">unidad</span>.</small>
                        @endif
                    </div>
                    <div>
                        <label>Valor en almacén</label>
                        <div class="pfm-stock-actual"><span><b id="valorTxt">S/ 0.00</b></span><small class="text-muted">stock × costo</small></div>
                    </div>
                </div>
            </div>
        </div>

        {{-- 3. Stock --}}
        <div class="pfm-card">
            <div class="pfm-card-head"><span class="hi"><i class="fas fa-boxes"></i></span><h5>Stock</h5></div>
            <div class="pfm-card-body">
                <div class="pfm-stock">
                    @if($editar)
                        <div>
                            <label>Stock actual</label>
                            <div class="pfm-stock-actual">
                                <span><b>{{ $C::conUnidad($m->stock_actual, $m->unidad->abreviatura ?? '') }}</b></span>
                                <a href="{{ route('materia-prima.ajuste', $m) }}" class="btn btn-sm btn-outline-secondary"><i class="fas fa-balance-scale mr-1"></i>Ajustar</a>
                            </div>
                            <input type="hidden" id="inStock" value="{{ $m->stock_actual }}">
                            <small class="text-muted">Cambia con compras, producción, ajustes o el conteo físico, así el kardex siempre cuadra.</small>
                        </div>
                    @else
                        <div>
                            <label>¿Cuánto hay ahora? (en <span class="lbl-unidad">unidad</span>) <span class="text-danger">*</span></label>
                            <input type="number" name="stock_actual" id="inStock" step="0.001" min="0" required class="form-control @error('stock_actual') is-invalid @enderror"
                                   value="{{ old('stock_actual', 0) }}">
                            @error('stock_actual')<div class="text-danger small">{{ $message }}</div>@enderror
                            <span class="mp-conv" data-conv="inStock"></span>
                            <small class="text-muted">Queda registrado como entrada inicial en el kardex.</small>
                        </div>
                    @endif
                    <div>
                        <label>Avisar cuando baje de (en <span class="lbl-unidad">unidad</span>) <span class="text-danger">*</span></label>
                        <input type="number" name="stock_minimo" id="inStockMin" step="0.001" min="0" required class="form-control @error('stock_minimo') is-invalid @enderror"
                               value="{{ old('stock_minimo', $m->stock_minimo ?? 0) }}">
                        @error('stock_minimo')<div class="text-danger small">{{ $message }}</div>@enderror
                        <span class="mp-conv" data-conv="inStockMin"></span>
                        <small class="text-muted">Stock mínimo: al llegar aquí sale "stock bajo" y se genera la orden automática de compra.</small>
                        @if($porDia)
                            <div><button type="button" class="mp-sugerir" data-poner="inStockMin" data-valor="{{ round($porDia * 3, 3) }}">
                                <i class="fas fa-magic"></i>Sugerido: 3 días de consumo ≈ {{ $C::conUnidad(round($porDia * 3, 2), $m->unidad->abreviatura ?? '') }}
                            </button></div>
                        @endif
                    </div>
                </div>
                @if($porDia)
                    <div class="mp-nota"><i class="fas fa-chart-line mr-1"></i>En los últimos 14 días se usaron ≈ <b>{{ $C::conUnidad(round($porDia, $porDia >= 1 ? 2 : 3), $m->unidad->abreviatura ?? '') }}</b> al día.</div>
                @endif
            </div>
        </div>

        {{-- 4. Reposición --}}
        <div class="pfm-card">
            <div class="pfm-card-head"><span class="hi"><i class="fas fa-truck"></i></span><h5>¿A quién y cuánto se compra?</h5></div>
            <div class="pfm-card-body">
                <div class="pfm-stock">
                    <div>
                        <label>Proveedor principal</label>
                        <select name="id_proveedor" id="inProveedor" class="form-control">
                            <option value="">— Sin asignar —</option>
                            @foreach($proveedores as $prov)
                                <option value="{{ $prov->id }}" {{ old('id_proveedor', $m->id_proveedor ?? '') == $prov->id ? 'selected' : '' }}>{{ $prov->nombre }}</option>
                            @endforeach
                        </select>
                        <small class="text-muted">Necesario para que la orden automática se convierta en compra.</small>
                    </div>
                    <div>
                        <label>Cantidad que se compra (en <span class="lbl-unidad">unidad</span>)</label>
                        <input type="number" name="cantidad_reposicion" id="inRepo" step="0.001" min="0" class="form-control"
                               value="{{ old('cantidad_reposicion', $m->cantidad_reposicion ?? '') }}" placeholder="Ej: 50 (un saco)">
                        <span class="mp-conv" id="convRepo"></span>
                        <small class="text-muted">Si lo dejas vacío, se pide el doble del mínimo.</small>
                    </div>
                </div>
            </div>
        </div>

        {{-- 5. Opciones --}}
        <div class="pfm-card">
            <div class="pfm-card-body">
                <div class="pfm-switch mb-3">
                    <div class="sw-txt">
                        <b>Insumo de control (conteo del PER)</b>
                        <span>Se cuenta en el conteo rápido que mide los errores de registro. Marca solo los más importantes.</span>
                    </div>
                    <input type="hidden" name="es_control" value="0">
                    <label class="pfm-toggle"><input type="checkbox" name="es_control" id="swControl" value="1" {{ old('es_control', $m->es_control ?? false) ? 'checked' : '' }} aria-label="Insumo de control"><span></span></label>
                </div>
                <div class="pfm-switch">
                    <div class="sw-txt">
                        <b>Activo</b>
                        <span>Si lo apagas, no aparece en recetas, compras ni conteos, pero se conserva su historial.</span>
                    </div>
                    <input type="hidden" name="estado" id="inEstado" value="{{ $estado }}">
                    <label class="pfm-toggle"><input type="checkbox" id="swEstado" {{ $estado === 'activo' ? 'checked' : '' }} aria-label="Insumo activo"><span></span></label>
                </div>
            </div>
        </div>
    </div>

    {{-- Lado: vista previa, lista de revisión y guardar --}}
    <div class="pfm-lado">
        <span class="preview-label"><i class="fas fa-eye mr-1"></i>Así se verá en el listado</span>
        <div class="item-card">
            <div class="item-card-media" style="height:74px; position:relative;">
                <div class="prev-badges"><span id="prevControl">Control</span><span id="prevInactivo">Inactivo</span></div>
                <i class="fas fa-wheat-awn" style="color:#b5451b"></i>
            </div>
            <div class="item-card-body">
                <div class="item-card-cat" id="prevProveedor">{{ $m->proveedor->nombre ?? 'Sin proveedor asignado' }}</div>
                <h3 class="item-card-title" id="prevNombre">{{ $m->nombre ?? 'Nombre del insumo' }}</h3>
                <div class="stock-gauge">
                    <div class="sg-track"><div class="sg-fill ok" id="prevGauge" style="width:0%;"></div></div>
                    <div class="sg-labels"><span>Hay: <strong id="prevStock">0</strong></span><span>Mín.: <span id="prevMin">0</span></span></div>
                </div>
                <div class="item-card-price" style="font-size:1.05rem;">S/ <span id="prevCosto">0.00</span> <span class="ic-cost">por <span class="lbl-unidad">unidad</span></span></div>
                <div class="mp-valor" id="prevDias" style="font-size:.78rem;color:#8a8a9d;"></div>
            </div>
        </div>
        <div class="pfm-card">
            <div class="pfm-card-body">
                <ul class="pfm-check mb-2" id="checklist">
                    <li data-chk="nombre"><i class="fas"></i><span>Nombre sin repetir</span></li>
                    <li data-chk="costo"><i class="fas"></i><span>Costo escrito</span></li>
                    <li data-chk="minimo"><i class="fas"></i><span>Stock mínimo para el aviso</span></li>
                    <li data-chk="proveedor"><i class="fas"></i><span>Proveedor (recomendado)</span></li>
                    @if($editar)
                        <li class="{{ $usos['recetas'] ? 'ok' : 'no' }}"><i class="fas {{ $usos['recetas'] ? 'fa-check-circle' : 'fa-info-circle' }}"></i><span>Se usa en {{ $usos['recetas'] }} receta(s)</span></li>
                        <li class="{{ $usos['compras'] ? 'ok' : 'no' }}"><i class="fas {{ $usos['compras'] ? 'fa-check-circle' : 'fa-info-circle' }}"></i><span>{{ $usos['compras'] }} compra(s) registradas</span></li>
                    @endif
                </ul>
                <button type="submit" id="btnGuardarInsumo" class="btn {{ $editar ? 'btn-warning' : 'btn-primary' }} pfm-guardar">
                    <i class="fas fa-save mr-1"></i>{{ $editar ? 'Guardar cambios' : 'Crear insumo' }}
                </button>
                <a href="{{ $editar ? route('materia-prima.show', $m) : route('materia-prima.index') }}" class="btn btn-link btn-block btn-sm text-muted">Cancelar</a>
            </div>
        </div>
    </div>
</div>

<script type="application/json" id="mpNombres">@json($nombres ?? [])</script>

@push('scripts')
<script>
(function () {
    const $ = id => document.getElementById(id);
    const raiz = $('pfmRaiz');
    const norm = t => (t || '').normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase().replace(/\s+/g, ' ').trim();
    const nombres = JSON.parse($('mpNombres').textContent || '[]');
    const porDia = {{ $porDia ? (float) $porDia : 'null' }};
    const unidadOriginal = @json($editar ? (string) $m->id_unidad : null);
    const esc = t => t.replace(/[&<>"]/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]));

    const unidadSel = () => raiz.querySelector('input[name=id_unidad]:checked');
    const abrev = () => (unidadSel() || {}).dataset ? unidadSel().dataset.abrev : '';
    const sub = { kg: 'g', L: 'mL' };

    // Nombre: repetido o parecido a otro insumo
    function revisarNombre() {
        const q = norm($('inNombre').value);
        const igual = q && nombres.find(n => norm(n) === q);
        const parecidos = q.length >= 3 && !igual ? nombres.filter(n => norm(n).includes(q) || (norm(n).length >= 4 && q.includes(norm(n)))).slice(0, 3) : [];
        $('avisoDuplicado').classList.toggle('ver', !!igual);
        $('avisoDuplicado').querySelector('span').innerHTML = igual ? 'Ya existe un insumo llamado <b>' + esc(igual) + '</b>. Usa otro nombre o edita ese.' : '';
        $('avisoParecido').classList.toggle('ver', parecidos.length > 0);
        $('avisoParecido').querySelector('span').innerHTML = parecidos.length ? 'Parecido a: <b>' + parecidos.map(esc).join('</b>, <b>') + '</b>. Revisa que no sea el mismo.' : '';
        return !igual;
    }

    // "= 500 g" debajo de los números cuando conviene leerlo en otra unidad
    function conversion(valor, u) {
        if (!valor || !sub[u]) return '';
        const txt = fmtCantU(valor, u);
        return txt === fmtCant(valor) + ' ' + u ? '' : '= ' + txt;
    }

    function pintar() {
        const u = abrev();
        raiz.querySelectorAll('.lbl-unidad').forEach(el => el.textContent = u || 'unidad');
        const stock = parseFloat($('inStock').value) || 0, min = parseFloat($('inStockMin').value) || 0;
        const costo = parseFloat($('inCosto').value) || 0, repo = parseFloat($('inRepo').value) || 0;

        raiz.querySelectorAll('[data-conv]').forEach(el => el.textContent = conversion(parseFloat($(el.dataset.conv).value) || 0, u));
        $('convCosto').textContent = costo && sub[u] ? '= S/ ' + (costo / 1000).toFixed(4) + ' por ' + sub[u] : '';
        const repoReal = repo || min * 2;
        $('convRepo').textContent = (conversion(repo, u) ? conversion(repo, u) + ' · ' : '') + (repoReal && costo ? 'cada compra ≈ S/ ' + (repoReal * costo).toFixed(2) : '');

        $('prevNombre').textContent = $('inNombre').value.trim() || 'Nombre del insumo';
        const prov = $('inProveedor').selectedOptions[0];
        $('prevProveedor').textContent = prov && prov.value ? prov.text.trim() : 'Sin proveedor asignado';
        $('prevStock').textContent = fmtCantU(stock, u);
        $('prevMin').textContent = fmtCantU(min, u);
        $('prevCosto').textContent = costo.toFixed(2);
        $('prevGauge').style.width = Math.min(100, Math.round(stock / Math.max(min * 2, 0.001) * 100)) + '%';
        $('prevGauge').className = 'sg-fill ' + (stock <= min ? 'bajo' : 'ok');
        $('prevControl').style.display = $('swControl').checked ? '' : 'none';
        $('prevInactivo').style.display = $('swEstado').checked ? 'none' : '';
        $('prevDias').textContent = porDia ? 'Alcanza para ' + Math.floor(stock / porDia) + ' días' : '';
        $('valorTxt').textContent = 'S/ ' + (stock * costo).toFixed(2);

        const aviso = $('avisoUnidad');
        if (aviso) aviso.classList.toggle('ver', unidadSel() && unidadSel().value !== unidadOriginal);

        const nombreOk = revisarNombre() && $('inNombre').value.trim() !== '';
        const marcar = (k, ok, opcional, mal) => {
            const li = raiz.querySelector('[data-chk=' + k + ']');
            li.className = ok ? 'ok' : 'no';
            li.querySelector('i').className = 'fas ' + (ok ? 'fa-check-circle' : (mal ? 'fa-times-circle text-danger' : (opcional ? 'fa-info-circle' : 'fa-circle')));
        };
        marcar('nombre', nombreOk, false, $('avisoDuplicado').classList.contains('ver'));
        marcar('costo', $('inCosto').value !== '' && costo >= 0);
        marcar('minimo', min > 0);
        marcar('proveedor', !!(prov && prov.value), true);
        $('btnGuardarInsumo').disabled = !nombreOk;
    }

    raiz.addEventListener('input', pintar);
    raiz.addEventListener('change', pintar);
    $('swEstado').addEventListener('change', function () { $('inEstado').value = this.checked ? 'activo' : 'inactivo'; });
    raiz.querySelectorAll('[data-poner]').forEach(b => b.addEventListener('click', () => { $(b.dataset.poner).value = b.dataset.valor; pintar(); }));
    raiz.querySelectorAll('[data-volver-unidad]').forEach(a => a.addEventListener('click', () => {
        const r = raiz.querySelector('input[name=id_unidad][value="' + unidadOriginal + '"]'); if (r) { r.checked = true; pintar(); }
    }));
    pintar();
})();
</script>
@endpush
