@extends('layouts.app')
@section('title', ($producto->receta ? 'Editar' : 'Crear') . ' receta')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('produccion.recetas') }}">Recetas</a></li>
    <li class="breadcrumb-item active">{{ $producto->nombre }}</li>
@endsection

@push('styles')
<style>
    .re { --cat: {{ $producto->categoria->color_final ?? '#b5451b' }}; }
    .re-hero { display: flex; align-items: center; gap: 1rem; flex-wrap: wrap; padding: 1.2rem 1.4rem; border-radius: 14px; color: #fff; margin-bottom: 1.2rem;
        background: linear-gradient(135deg, #1a1a2e 0%, #23233d 55%, var(--cat) 150%); }
    .re-hero .rh-foto { width: 64px; height: 64px; border-radius: 14px; background: rgba(255,255,255,.13); border: 2px solid rgba(255,255,255,.25);
        display: flex; align-items: center; justify-content: center; font-size: 1.6rem; overflow: hidden; flex-shrink: 0; }
    .re-hero .rh-foto img { width: 100%; height: 100%; object-fit: cover; }
    .re-hero small { opacity: .75; font-weight: 700; letter-spacing: .05em; text-transform: uppercase; font-size: .72rem; }
    .re-hero h3 { margin: 0; font-weight: 800; font-size: 1.4rem; }
    .re-hero .rh-precio { margin-left: auto; text-align: right; }
    .re-hero .rh-precio b { display: block; font-size: 1.5rem; }
    .re-hero .rh-precio span { font-size: .78rem; opacity: .8; }

    .re-grid { display: grid; grid-template-columns: minmax(0, 1.6fr) minmax(0, 1fr); gap: 1.2rem; align-items: start; }
    @media (max-width: 991px) { .re-grid { grid-template-columns: 1fr; } }
    .re-card { background: #fff; border-radius: 14px; box-shadow: 0 2px 12px rgba(0,0,0,.05); }
    body.dark-mode .re-card { background: #1f1f33; }
    .re-card-head { display: flex; align-items: center; gap: .6rem; padding: 1rem 1.3rem; border-bottom: 1px solid #f3eee9; flex-wrap: wrap; }
    .re-card-head h5 { margin: 0; font-weight: 800; font-size: 1rem; color: #1a1a2e; }
    body.dark-mode .re-card-head { border-bottom-color: #2c2c44; }
    body.dark-mode .re-card-head h5 { color: #f0f0f7; }
    .re-card-body { padding: 1rem 1.3rem; }

    .re-copiar { display: flex; align-items: center; gap: .5rem; margin-left: auto; font-size: .82rem; color: #8a8a9d; }
    .re-copiar select { width: auto; min-width: 190px; }

    .re-cols, .re-fila { display: grid; grid-template-columns: 30px minmax(0, 2.2fr) minmax(0, 1fr) 82px 86px 36px; gap: .5rem; align-items: center; }
    .re-cols { font-size: .68rem; font-weight: 800; letter-spacing: .04em; text-transform: uppercase; color: #8a8a9d; padding: 0 .55rem .4rem; }
    .re-fila { background: #f9f7f5; border: 1.5px solid transparent; border-radius: 12px; padding: .5rem .55rem; margin-bottom: .5rem; }
    .re-fila .rf-num { width: 26px; height: 26px; border-radius: 50%; background: #fff; color: #8a8a9d; font-size: .75rem; font-weight: 800; display: flex; align-items: center; justify-content: center; }
    .re-fila .rf-costo { font-size: .8rem; font-weight: 700; color: #495057; text-align: right; }
    .re-fila .rf-stock { grid-column: 2 / -1; font-size: .72rem; color: #adb5bd; margin-top: -.2rem; }
    .re-fila.repetido { border-color: #e74c3c; }
    .re-fila.repetido .rf-stock { color: #c0392b; font-weight: 700; }
    body.dark-mode .re-fila { background: #24243b; }
    body.dark-mode .re-fila .rf-num { background: #1f1f33; }
    body.dark-mode .re-fila .rf-costo { color: #d5d5e2; }
    @media (max-width: 767px) {
        .re-cols { display: none; }
        .re-fila { grid-template-columns: 26px 1fr 1fr 70px 32px; }
        .re-fila .rf-costo { grid-column: 2 / 4; text-align: left; }
    }
    .re-vacio { text-align: center; color: #adb5bd; padding: 1.6rem 1rem; border: 2px dashed #eee3da; border-radius: 12px; }
    .re-agregar { width: 100%; border: 2px dashed #e2d3c6; background: none; border-radius: 12px; padding: .6rem; color: #b5451b; font-weight: 700; }
    .re-agregar:hover { background: #fffaf7; border-color: #b5451b; }
    body.dark-mode .re-agregar { border-color: #4a3a33; }

    .re-resumen { position: sticky; top: 1rem; }
    .re-dato { display: flex; justify-content: space-between; align-items: baseline; padding: .45rem 0; border-bottom: 1px dashed #eee; font-size: .88rem; color: #6c757d; }
    .re-dato:last-of-type { border-bottom: none; }
    .re-dato b { color: #1a1a2e; font-size: 1rem; }
    body.dark-mode .re-dato { border-bottom-color: #33334d; }
    body.dark-mode .re-dato b { color: #f0f0f7; }
    .re-ganancia { margin: .8rem 0 .4rem; padding: .8rem .9rem; border-radius: 12px; background: rgba(46,204,113,.1); }
    .re-ganancia .rg-top { display: flex; justify-content: space-between; align-items: baseline; }
    .re-ganancia .rg-top span { font-size: .78rem; font-weight: 800; text-transform: uppercase; letter-spacing: .04em; color: #1e8e5a; }
    .re-ganancia .rg-top b { font-size: 1.5rem; color: #1e8e5a; }
    .re-ganancia .rg-barra { height: 8px; border-radius: 6px; background: rgba(0,0,0,.07); overflow: hidden; margin-top: .4rem; }
    .re-ganancia .rg-barra span { display: block; height: 100%; background: #2ecc71; border-radius: 6px; transition: width .2s; }
    .re-ganancia.baja { background: rgba(243,156,18,.12); } .re-ganancia.baja span, .re-ganancia.baja b { color: #a5680d !important; } .re-ganancia.baja .rg-barra span { background: #f39c12; }
    .re-ganancia.perdida { background: rgba(231,76,60,.1); } .re-ganancia.perdida span, .re-ganancia.perdida b { color: #c0392b !important; } .re-ganancia.perdida .rg-barra span { background: #e74c3c; }
    .re-aviso { font-size: .78rem; color: #b3261e; background: rgba(231,76,60,.08); border-radius: 9px; padding: .45rem .7rem; margin-top: .5rem; }
    .re-guardar { width: 100%; padding: .7rem; font-weight: 800; margin-top: .9rem; }
</style>
@endpush

@section('content')
<div class="re">
    <div class="re-hero">
        <div class="rh-foto">
            @if($producto->imagen)<img src="{{ asset('storage/' . $producto->imagen) }}" alt="">@else<i class="fas {{ $producto->categoria->icono_final ?? 'fa-bread-slice' }}"></i>@endif
        </div>
        <div>
            <small>{{ $producto->receta ? 'Editar receta' : 'Nueva receta' }} · {{ $producto->categoria->nombre ?? 'Sin categoría' }}</small>
            <h3>{{ $producto->nombre }}</h3>
        </div>
        <div class="rh-precio"><b>S/ {{ number_format($producto->precio_venta, 2) }}</b><span>precio de venta</span></div>
    </div>

    <form action="{{ route('produccion.guardar-receta') }}" method="POST" id="formReceta">
        @csrf
        <input type="hidden" name="id_producto" value="{{ $producto->id }}">

        <div class="re-grid">
            <div class="re-card">
                <div class="re-card-head">
                    <h5><i class="fas fa-list-ul mr-1 text-muted"></i>Ingredientes</h5>
                    @if($otras->isNotEmpty())
                    <div class="re-copiar">
                        <i class="fas fa-copy"></i><span>Copiar de</span>
                        <select id="copiarDe" class="form-control form-control-sm">
                            <option value="">— otro producto —</option>
                            @foreach($otras as $o)<option value="{{ $o->id }}">{{ $o->nombre }}</option>@endforeach
                        </select>
                    </div>
                    @endif
                </div>
                <div class="re-card-body">
                    @if($errors->any())
                        <div class="re-aviso mb-3" style="margin-top:0;"><i class="fas fa-exclamation-circle mr-1"></i>{{ $errors->first() }}</div>
                    @endif
                    <div class="re-cols"><span></span><span>Insumo</span><span>Cantidad</span><span>Unidad</span><span class="text-right">Costo</span><span></span></div>
                    <div id="filas"></div>
                    <div class="re-vacio" id="vacio" style="display:none;"><i class="fas fa-flask d-block mb-2" style="font-size:1.5rem;opacity:.5"></i>Agrega los insumos que lleva una tanda.</div>
                    <button type="button" class="re-agregar mt-1" id="btnAgregar"><i class="fas fa-plus mr-1"></i>Agregar insumo</button>
                    <p class="text-muted small mt-2 mb-0"><i class="fas fa-info-circle mr-1"></i>Puedes escribir en g o mL: se guarda en kg o L automáticamente.</p>
                </div>
            </div>

            <div class="re-card re-resumen">
                <div class="re-card-head"><h5><i class="fas fa-calculator mr-1 text-muted"></i>Tanda y costos</h5></div>
                <div class="re-card-body">
                    <div class="form-group">
                        <label class="small font-weight-bold">¿Cuántas unidades salen por tanda? <span class="text-danger">*</span></label>
                        <input type="number" name="rendimiento" id="rendimiento" class="form-control form-control-lg @error('rendimiento') is-invalid @enderror"
                               min="1" required value="{{ old('rendimiento', $producto->receta->rendimiento ?? 1) }}">
                    </div>
                    <div class="form-group">
                        <label class="small font-weight-bold">Nota <span class="font-weight-normal text-muted">(opcional)</span></label>
                        <input type="text" name="descripcion" class="form-control" maxlength="500" placeholder="Ej: horno a 180 °C, 25 min"
                               value="{{ old('descripcion', $producto->receta->descripcion ?? '') }}">
                    </div>

                    <div class="re-dato"><span>Costo de la tanda</span><b id="rTanda">S/ 0.00</b></div>
                    <div class="re-dato"><span>Costo por unidad</span><b id="rUnidad">S/ 0.00</b></div>
                    <div class="re-dato"><span>Precio de venta</span><b>S/ {{ number_format($producto->precio_venta, 2) }}</b></div>

                    <div class="re-ganancia" id="rGanancia">
                        <div class="rg-top"><span id="rGanTxt">Ganas</span><b id="rGanPct">—</b></div>
                        <div class="rg-barra"><span id="rGanBarra" style="width:0"></span></div>
                        <div class="small mt-1 text-muted" id="rGanSoles"></div>
                    </div>
                    <div id="rAvisos"></div>

                    <button type="submit" class="btn btn-warning re-guardar"><i class="fas fa-save mr-1"></i>Guardar receta</button>
                    <a href="{{ route('produccion.recetas') }}" class="btn btn-link btn-block btn-sm text-muted">Cancelar</a>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
(function () {
    const materias = @json($materias);
    const filasIniciales = @json($filas);
    const precio = {{ (float) $producto->precio_venta }};
    const cont = document.getElementById('filas');
    const porId = id => materias.find(m => String(m.id) === String(id));
    const fmtS = n => 'S/ ' + Number(n).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    let n = 0;

    function agregar(fila) {
        fila = fila || {};
        const i = n++;
        const opciones = materias.map(m => `<option value="${m.id}"${String(m.id) === String(fila.id_materia) ? ' selected' : ''}>${m.nombre}</option>`).join('');
        cont.insertAdjacentHTML('beforeend', `<div class="re-fila">
            <span class="rf-num"></span>
            <select name="ingredientes[${i}][id_materia]" class="form-control form-control-sm rf-materia" required>
                <option value="">— Elegir insumo —</option>${opciones}
            </select>
            <input type="number" name="ingredientes[${i}][cantidad]" class="form-control form-control-sm rf-cant" step="any" min="0.001" required placeholder="0">
            <select name="ingredientes[${i}][unidad]" class="form-control form-control-sm rf-unidad"></select>
            <span class="rf-costo">—</span>
            <button type="button" class="btn btn-sm btn-outline-danger rf-quitar" title="Quitar"><i class="fas fa-times"></i></button>
            <span class="rf-stock"></span>
        </div>`);
        const el = cont.lastElementChild;
        unidades(el, fila);
        el.querySelector('.rf-materia').addEventListener('change', () => { unidades(el, {}); recalcular(); });
        el.querySelector('.rf-cant').addEventListener('input', recalcular);
        el.querySelector('.rf-unidad').addEventListener('change', recalcular);
        el.querySelector('.rf-quitar').addEventListener('click', () => { el.remove(); recalcular(); });
        return el;
    }

    // Unidad del insumo y su unidad pequeña (kg/g, L/mL). Una cantidad guardada menor a 1 se muestra en g/mL.
    function unidades(el, fila) {
        const m = porId(el.querySelector('.rf-materia').value);
        const sel = el.querySelector('.rf-unidad');
        const inp = el.querySelector('.rf-cant');
        if (!m) { sel.innerHTML = '<option value="base">—</option>'; return; }
        sel.innerHTML = `<option value="base">${m.abreviatura}</option>` + (m.subunidad ? `<option value="sub">${m.subunidad}</option>` : '');
        if (fila.cantidad !== undefined && fila.cantidad !== '' && fila.cantidad !== null) {
            if (fila.unidad) { sel.value = fila.unidad; inp.value = fila.cantidad; }
            else if (m.subunidad && Number(fila.cantidad) < 1) { sel.value = 'sub'; inp.value = +(Number(fila.cantidad) * 1000).toFixed(3); }
            else { sel.value = 'base'; inp.value = +Number(fila.cantidad).toFixed(3); }
        } else if (m.subunidad) {
            sel.value = 'sub';
        }
    }

    function recalcular() {
        let total = 0; const vistos = {}; let repetido = false; const avisos = [];
        const filas = cont.querySelectorAll('.re-fila');
        document.getElementById('vacio').style.display = filas.length ? 'none' : '';
        filas.forEach((el, k) => {
            el.querySelector('.rf-num').textContent = k + 1;
            const id = el.querySelector('.rf-materia').value;
            const m = porId(id);
            let cant = parseFloat(el.querySelector('.rf-cant').value) || 0;
            if (el.querySelector('.rf-unidad').value === 'sub') cant /= 1000;
            const costo = m ? cant * m.costo : 0;
            total += costo;
            el.querySelector('.rf-costo').textContent = m && cant ? fmtS(costo) : '—';
            const rep = !!(id && vistos[id]);
            el.classList.toggle('repetido', rep);
            if (rep) repetido = true;
            el.querySelector('.rf-stock').textContent = !m ? '' : rep ? 'Insumo repetido: júntalo en una sola fila' : `En almacén: ${fmtCantU(m.stock, m.abreviatura)} · S/ ${m.costo.toFixed(2)} por ${m.abreviatura}`;
            if (m && m.costo <= 0) avisos.push(`"${m.nombre}" no tiene costo: el costo de la receta saldrá menor.`);
            if (id) vistos[id] = true;
        });
        const rend = Math.max(1, parseInt(document.getElementById('rendimiento').value) || 1);
        const unidad = total / rend;
        document.getElementById('rTanda').textContent = fmtS(total);
        document.getElementById('rUnidad').textContent = fmtS(unidad);

        const caja = document.getElementById('rGanancia');
        if (total > 0 && precio > 0) {
            const pct = (precio - unidad) / precio * 100;
            caja.className = 're-ganancia' + (pct < 0 ? ' perdida' : pct < 20 ? ' baja' : '');
            document.getElementById('rGanTxt').textContent = pct < 0 ? 'Pierdes' : 'Ganas';
            document.getElementById('rGanPct').textContent = Math.abs(pct).toFixed(1) + '%';
            document.getElementById('rGanBarra').style.width = Math.min(100, Math.max(0, pct)) + '%';
            document.getElementById('rGanSoles').textContent = `${fmtS(precio - unidad)} por unidad · ${fmtS((precio - unidad) * rend)} por tanda`;
        } else {
            caja.className = 're-ganancia';
            document.getElementById('rGanPct').textContent = '—';
            document.getElementById('rGanBarra').style.width = '0';
            document.getElementById('rGanSoles').textContent = 'Agrega insumos para calcularla.';
        }
        if (repetido) avisos.unshift('Hay un insumo repetido.');
        document.getElementById('rAvisos').innerHTML = avisos.map(a => `<div class="re-aviso"><i class="fas fa-exclamation-triangle mr-1"></i>${a}</div>`).join('');
        return !repetido && filas.length > 0;
    }

    document.getElementById('btnAgregar').addEventListener('click', () => { agregar().querySelector('.rf-materia').focus(); recalcular(); });
    document.getElementById('rendimiento').addEventListener('input', recalcular);
    document.getElementById('formReceta').addEventListener('submit', e => { if (!recalcular()) e.preventDefault(); });

    const copiar = document.getElementById('copiarDe');
    if (copiar) copiar.addEventListener('change', function () {
        if (!this.value) return;
        fetch(`{{ url('produccion/ingredientes') }}/${this.value}`).then(r => r.json()).then(data => {
            if (!data) return;
            cont.innerHTML = '';
            data.detalles.forEach(d => agregar({ id_materia: d.id_materia, cantidad: d.cantidad }));
            document.getElementById('rendimiento').value = data.rendimiento;
            recalcular();
        });
        this.value = '';
    });

    if (filasIniciales.length) filasIniciales.forEach(f => agregar(f)); else agregar();
    recalcular();
})();
</script>
@endpush
