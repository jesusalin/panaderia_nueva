{{--
    Formulario de producto (nuevo y editar).
    Variables: $producto (null al crear), $categorias, y al editar $costoReceta, $vendidos30, $tieneReceta.
--}}
@php
    $editar   = isset($producto) && $producto;
    $p        = $editar ? $producto : null;
    $catSel   = old('id_categoria', $p->id_categoria ?? '');
    $estado   = old('estado', $p->estado ?? 'activo');
    $costoReceta = $costoReceta ?? null;
@endphp

@include('partials.form-secciones')

<div class="pfm-grid" style="--cat: {{ $p->categoria->color_final ?? '#b5451b' }}" id="pfmRaiz">
    <div>
        {{-- Información --}}
        <div class="pfm-card">
            <div class="pfm-card-head"><span class="hi"><i class="fas fa-info"></i></span><h5>Información</h5></div>
            <div class="pfm-card-body">
                <div class="row">
                    <div class="col-md-7 form-group">
                        <label>Nombre <span class="text-danger">*</span></label>
                        <input type="text" name="nombre" id="inNombre" maxlength="150" class="form-control @error('nombre') is-invalid @enderror"
                               value="{{ old('nombre', $p->nombre ?? '') }}" placeholder="Ej: Pan francés" required {{ $editar ? '' : 'autofocus' }}>
                        @error('nombre')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-5 form-group">
                        <label>Categoría <span class="text-danger">*</span></label>
                        <select name="id_categoria" id="inCategoria" class="form-control @error('id_categoria') is-invalid @enderror" required>
                            <option value="">— Elegir —</option>
                            @foreach($categorias as $c)
                                <option value="{{ $c->id }}" data-icono="{{ $c->icono_final }}" data-color="{{ $c->color_final }}" {{ $catSel == $c->id ? 'selected' : '' }}>
                                    {{ $c->nombre }}{{ $c->estado !== 'activo' ? ' (inactiva)' : '' }}
                                </option>
                            @endforeach
                        </select>
                        @error('id_categoria')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
                <div class="form-group mb-0">
                    <label>Descripción <small class="text-muted font-weight-normal">(opcional)</small></label>
                    <textarea name="descripcion" class="form-control" rows="2" placeholder="Ej: Pan crocante de masa simple">{{ old('descripcion', $p->descripcion ?? '') }}</textarea>
                </div>
            </div>
        </div>

        {{-- Foto --}}
        <div class="pfm-card">
            <div class="pfm-card-head"><span class="hi"><i class="fas fa-camera"></i></span><h5>Foto</h5><small>opcional · máx. 2 MB</small></div>
            <div class="pfm-card-body">
                <div class="pfm-foto">
                    <div class="pfm-foto-vista" id="fotoVista">
                        @if($p && $p->imagen)<img src="{{ asset('storage/' . $p->imagen) }}" alt="">@else<i class="fas {{ $p->categoria->icono_final ?? 'fa-bread-slice' }}"></i>@endif
                    </div>
                    <div>
                        <label class="pfm-drop mb-2" id="fotoDrop">
                            <input type="file" name="imagen" id="inImagen" accept="image/*">
                            <i class="fas fa-cloud-upload-alt"></i>
                            <span id="fotoTexto">{{ $p && $p->imagen ? 'Haz clic o arrastra otra foto para cambiarla' : 'Haz clic o arrastra una foto del producto' }}</span>
                        </label>
                        @error('imagen')<div class="text-danger small">{{ $message }}</div>@enderror
                        @if($p && $p->imagen)
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" class="custom-control-input" id="quitarImagen" name="quitar_imagen" value="1">
                                <label class="custom-control-label text-danger small" for="quitarImagen">Quitar la foto actual</label>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Precio y ganancia --}}
        <div class="pfm-card">
            <div class="pfm-card-head"><span class="hi"><i class="fas fa-tags"></i></span><h5>Precio y ganancia</h5></div>
            <div class="pfm-card-body">
                <div class="pfm-precios">
                    <div>
                        <label>Precio de venta <span class="text-danger">*</span></label>
                        <div class="pfm-money"><span>S/</span>
                            <input type="number" name="precio_venta" id="inPrecio" step="0.01" min="0" required
                                   class="form-control @error('precio_venta') is-invalid @enderror" value="{{ old('precio_venta', $p->precio_venta ?? '') }}" placeholder="0.00">
                        </div>
                        @error('precio_venta')<div class="text-danger small">{{ $message }}</div>@enderror
                    </div>
                    <div>
                        <label>Costo de producción <small class="text-muted font-weight-normal">(por unidad)</small></label>
                        <div class="pfm-money"><span>S/</span>
                            <input type="number" name="costo_produccion" id="inCosto" step="0.01" min="0"
                                   class="form-control" value="{{ old('costo_produccion', $p->costo_produccion ?? '') }}" placeholder="0.00">
                        </div>
                        @if($costoReceta !== null)
                            <div class="pfm-receta-link">
                                Según su receta cuesta <strong>S/ {{ number_format($costoReceta, 2) }}</strong>.
                                <a href="#" id="usarCostoReceta" data-costo="{{ number_format($costoReceta, 2, '.', '') }}">Usar ese costo</a>
                            </div>
                        @endif
                    </div>
                </div>
                <div class="pfm-ganancia vacia" id="ganancia">
                    <div class="g-top"><span id="ganTxt">Ganancia por unidad</span><b id="ganValor">—</b></div>
                    <div class="g-barra"><span id="ganBarra" style="width:0"></span></div>
                    <div class="small text-muted mt-1" id="ganNota">Escribe el precio y el costo para ver cuánto ganas.</div>
                </div>
            </div>
        </div>

        {{-- Stock --}}
        <div class="pfm-card">
            <div class="pfm-card-head"><span class="hi"><i class="fas fa-boxes"></i></span><h5>Stock</h5></div>
            <div class="pfm-card-body">
                <div class="pfm-stock">
                    @if($editar)
                        <div>
                            <label>Stock actual</label>
                            <div class="pfm-stock-actual">
                                <span><b id="inStockTxt">{{ $p->stock_actual }}</b> und.</span>
                                <a href="{{ route('productos.ajuste', $p) }}" class="btn btn-sm btn-outline-secondary"><i class="fas fa-balance-scale mr-1"></i>Ajustar</a>
                            </div>
                            <input type="hidden" id="inStock" value="{{ $p->stock_actual }}">
                            <small class="text-muted">Cambia con producción, ventas o un ajuste, así el kardex siempre cuadra.</small>
                        </div>
                    @else
                        <div>
                            <label>Stock inicial <span class="text-danger">*</span></label>
                            <input type="number" name="stock_actual" id="inStock" min="0" required class="form-control @error('stock_actual') is-invalid @enderror"
                                   value="{{ old('stock_actual', 0) }}">
                            <small class="text-muted">Queda registrado en el kardex como entrada inicial.</small>
                            @error('stock_actual')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                    @endif
                    <div>
                        <label>Stock mínimo <span class="text-danger">*</span></label>
                        <input type="number" name="stock_minimo" id="inStockMin" min="0" required class="form-control @error('stock_minimo') is-invalid @enderror"
                               value="{{ old('stock_minimo', $p->stock_minimo ?? 0) }}">
                        <small class="text-muted">Por debajo de esto avisa "stock bajo" y lo sugiere en "¿Qué producir hoy?".</small>
                        @error('stock_minimo')<div class="text-danger small">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>
        </div>

        {{-- Estado --}}
        <div class="pfm-card">
            <div class="pfm-card-body pfm-switch">
                <div class="sw-txt">
                    <b>Disponible para la venta</b>
                    <span>Si lo apagas, no aparece para vender ni producir, pero se conserva su historial.</span>
                </div>
                <input type="hidden" name="estado" id="inEstado" value="{{ $estado }}">
                <label class="pfm-toggle" title="Activo / inactivo">
                    <input type="checkbox" id="swEstado" {{ $estado === 'activo' ? 'checked' : '' }} aria-label="Producto activo">
                    <span></span>
                </label>
            </div>
        </div>
    </div>

    {{-- Lado derecho: vista previa y lo que falta --}}
    <div class="pfm-lado">
        <span class="preview-label"><i class="fas fa-eye mr-1"></i>Así se verá en el catálogo</span>
        <div class="item-card">
            <div class="item-card-media" id="prevMedia">
                @if($p && $p->imagen)<img src="{{ asset('storage/' . $p->imagen) }}" alt="">@else<i class="fas {{ $p->categoria->icono_final ?? 'fa-bread-slice' }}"></i>@endif
            </div>
            <div class="item-card-body">
                <div class="item-card-cat" id="prevCat">{{ $p->categoria->nombre ?? 'Categoría' }}</div>
                <h3 class="item-card-title" id="prevNombre">{{ $p->nombre ?? 'Nombre del producto' }}</h3>
                <div class="item-card-price">S/ <span id="prevPrecio">0.00</span> <span class="ic-cost" id="prevMargen"></span></div>
                <div class="item-card-stockrow">
                    <span>Stock</span>
                    <span class="badge-soft badge-soft-success" id="prevStock">0 uds</span>
                </div>
            </div>
        </div>

        <div class="pfm-card">
            <div class="pfm-card-body">
                <ul class="pfm-check" id="checklist">
                    <li id="ckPrecio"><i class="fas fa-circle"></i><span>Tiene precio de venta</span></li>
                    <li id="ckCosto"><i class="fas fa-circle"></i><span>Tiene costo (para saber cuánto ganas)</span></li>
                    @if($editar)
                        <li class="{{ $tieneReceta ? 'ok' : 'no' }}"><i class="fas {{ $tieneReceta ? 'fa-check-circle' : 'fa-exclamation-circle' }}"></i>
                            <span>{{ $tieneReceta ? 'Tiene receta: se puede producir' : 'Sin receta: no se puede producir todavía' }}
                            @if(auth()->user()->hasPermiso('produccion.recetas'))
                                · <a href="{{ route('produccion.receta.editar', $p) }}">{{ $tieneReceta ? 'ver receta' : 'crear receta' }}</a>
                            @endif
                            </span></li>
                        <li class="ok"><i class="fas fa-fire"></i><span>Vendidos en 30 días: <b>{{ $vendidos30 }}</b></span></li>
                    @else
                        <li class="no"><i class="fas fa-info-circle"></i><span>Después de guardarlo, crea su receta para poder producirlo.</span></li>
                    @endif
                </ul>
                <button type="submit" class="btn {{ $editar ? 'btn-warning' : 'btn-primary' }} pfm-guardar mt-2">
                    <i class="fas fa-save mr-1"></i>{{ $editar ? 'Guardar cambios' : 'Crear producto' }}
                </button>
                <a href="{{ $editar ? route('productos.show', $p) : route('productos.index') }}" class="btn btn-link btn-block btn-sm text-muted">Cancelar</a>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
(function () {
    const $ = id => document.getElementById(id);
    const fmtS = n => 'S/ ' + Number(n).toFixed(2);

    function categoria() {
        const opt = $('inCategoria').selectedOptions[0];
        return opt && opt.value ? { nombre: opt.text.trim(), icono: opt.dataset.icono, color: opt.dataset.color } : null;
    }

    function pintar() {
        const precio = parseFloat($('inPrecio').value) || 0;
        const costo  = parseFloat($('inCosto').value) || 0;
        const cat = categoria();
        if (cat) $('pfmRaiz').style.setProperty('--cat', cat.color);

        // Ganancia
        const caja = $('ganancia');
        if (precio > 0 && costo > 0) {
            const gan = precio - costo, pct = gan / precio * 100;
            caja.className = 'pfm-ganancia' + (pct < 0 ? ' perdida' : pct < 20 ? ' baja' : '');
            $('ganTxt').textContent = pct < 0 ? 'Pierdes por unidad' : 'Ganas por unidad';
            $('ganValor').textContent = fmtS(Math.abs(gan)) + ' (' + Math.abs(pct).toFixed(1) + '%)';
            $('ganBarra').style.width = Math.min(100, Math.max(0, pct)) + '%';
            $('ganNota').textContent = pct < 0 ? 'El costo es mayor que el precio: revisa uno de los dos.'
                : pct < 20 ? 'Ganancia baja: menos del 20% del precio.' : 'Por cada 100 vendidos ganas ' + fmtS(gan * 100) + '.';
            $('prevMargen').textContent = (pct < 0 ? 'pierdes ' : 'ganas ') + Math.abs(pct).toFixed(0) + '%';
        } else {
            caja.className = 'pfm-ganancia vacia';
            $('ganTxt').textContent = 'Ganancia por unidad';
            $('ganValor').textContent = '—';
            $('ganBarra').style.width = '0';
            $('ganNota').textContent = 'Escribe el precio y el costo para ver cuánto ganas.';
            $('prevMargen').textContent = costo > 0 ? '' : 'sin costo';
        }

        // Vista previa
        $('prevNombre').textContent = $('inNombre').value.trim() || 'Nombre del producto';
        $('prevCat').textContent = cat ? cat.nombre.replace(' (inactiva)', '') : 'Categoría';
        $('prevPrecio').textContent = precio.toFixed(2);
        const stock = parseInt($('inStock').value) || 0, min = parseInt($('inStockMin').value) || 0;
        $('prevStock').textContent = stock + ' uds';
        $('prevStock').className = 'badge-soft ' + (stock <= min ? 'badge-soft-danger' : 'badge-soft-success');
        if (!$('prevMedia').querySelector('img') && cat) {
            $('prevMedia').innerHTML = `<i class="fas ${cat.icono}"></i>`;
            if (!$('fotoVista').querySelector('img')) $('fotoVista').innerHTML = `<i class="fas ${cat.icono}"></i>`;
        }

        // Lista de lo que falta
        const marca = (id, ok) => { const li = $(id); li.className = ok ? 'ok' : 'no'; li.querySelector('i').className = 'fas ' + (ok ? 'fa-check-circle' : 'fa-exclamation-circle'); };
        marca('ckPrecio', precio > 0);
        marca('ckCosto', costo > 0);
    }

    ['inNombre', 'inCategoria', 'inPrecio', 'inCosto', 'inStock', 'inStockMin'].forEach(id => {
        const el = $(id); if (!el) return;
        el.addEventListener('input', pintar); el.addEventListener('change', pintar);
    });
    $('swEstado').addEventListener('change', function () { $('inEstado').value = this.checked ? 'activo' : 'inactivo'; });

    const usar = $('usarCostoReceta');
    if (usar) usar.addEventListener('click', e => { e.preventDefault(); $('inCosto').value = usar.dataset.costo; pintar(); });

    // Foto: vista previa al elegir o arrastrar
    const drop = $('fotoDrop');
    ['dragenter', 'dragover'].forEach(ev => drop.addEventListener(ev, () => drop.classList.add('sobre')));
    ['dragleave', 'drop'].forEach(ev => drop.addEventListener(ev, () => drop.classList.remove('sobre')));
    $('inImagen').addEventListener('change', function () {
        const f = this.files[0];
        if (!f) return;
        if (f.size > 2 * 1024 * 1024) { $('fotoTexto').textContent = 'Esa foto pesa más de 2 MB: elige una más liviana.'; this.value = ''; return; }
        const r = new FileReader();
        r.onload = e => {
            $('fotoVista').innerHTML = `<img src="${e.target.result}" alt="">`;
            $('prevMedia').innerHTML = `<img src="${e.target.result}" alt="">`;
            $('fotoTexto').textContent = f.name + ' — se guardará al pulsar Guardar';
            const q = $('quitarImagen'); if (q) q.checked = false;
        };
        r.readAsDataURL(f);
    });

    pintar();
})();
</script>
@endpush
