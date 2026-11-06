{{-- Formulario de cliente (nuevo y editar). Variables: $cliente (null al crear), $nombres, $documentos y $distritos --}}
@php
    $editar = isset($cliente) && $cliente;
    $c = $editar ? $cliente : null;
    $general = $editar && $c->esGeneral();
    $estado = old('estado', $c->estado ?? 'activo');
    $tipoSel = old('tipo', $c->tipo ?? 'bodega');
    $T = \App\Models\Cliente::TIPOS;
@endphp
@include('partials.form-secciones')
@include('partials.compras-estilos')

@once
@push('styles')
<style>
    .cf-aviso { display: none; gap: .5rem; align-items: flex-start; border-radius: 10px; padding: .5rem .75rem; font-size: .8rem; margin-top: .45rem; }
    .cf-aviso.ver { display: flex; }
    .cf-aviso i { margin-top: .15rem; }
    .cf-aviso.mal { background: #fdecea; color: #a93226; }
    .cf-aviso.ok { background: #e9f8ef; color: #1e7a4c; }
    body.dark-mode .cf-aviso.mal { background: rgba(231,76,60,.15); color: #f1a097; }
    body.dark-mode .cf-aviso.ok { background: rgba(46,204,113,.12); color: #6ee7a5; }

    .cf-tipos { display: grid; grid-template-columns: repeat(auto-fill, minmax(118px, 1fr)); gap: .45rem; }
    .cf-tipos input { position: absolute; opacity: 0; pointer-events: none; }
    .cf-tipos label { margin: 0; border: 1.5px solid #eee5dd; border-radius: 12px; padding: .55rem .4rem; text-align: center; cursor: pointer; font-size: .78rem; font-weight: 700;
        color: #555; background: #fff; transition: border-color .12s, background .12s; }
    .cf-tipos label i { display: block; font-size: 1.15rem; margin-bottom: .25rem; color: var(--tc); }
    .cf-tipos label:hover { border-color: var(--tc); }
    .cf-tipos input:checked + label { border-color: var(--tc); background: color-mix(in srgb, var(--tc) 11%, #fff); color: #1a1a2e; box-shadow: 0 0 0 1px var(--tc) inset; }
    .cf-tipos input:focus-visible + label { outline: 2px solid var(--tc); outline-offset: 2px; }
    body.dark-mode .cf-tipos label { background: #24243b; border-color: #33334d; color: #ccc; }
    body.dark-mode .cf-tipos input:checked + label { background: #2c2c44; color: #fff; }

    .cf-doc { display: flex; gap: .5rem; align-items: stretch; }
    .cf-doc select { width: auto; flex-shrink: 0; }
    .cf-vista { background: #fff; border-radius: 14px; box-shadow: 0 2px 12px rgba(0,0,0,.06); padding: 1rem; border-top: 4px solid var(--tc, #b5451b); }
    body.dark-mode .cf-vista { background: #1f1f33; }
    .cf-vista .cv-cab { display: flex; gap: .7rem; align-items: center; margin-bottom: .6rem; }
    .cf-vista .cv-av { width: 46px; height: 46px; border-radius: 12px; color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 800; flex-shrink: 0;
        background: linear-gradient(135deg, color-mix(in srgb, var(--tc) 70%, #000), var(--tc)); }
    .cf-vista .cv-av.inactivo { background: #adb5bd; }
    .cf-vista b.nm { display: block; font-weight: 800; color: #1a1a2e; line-height: 1.2; }
    body.dark-mode .cf-vista b.nm { color: #f0f0f7; }
</style>
@endpush
@endonce

<div class="pfm-grid" id="cfRaiz">
    <div>
        <div class="pfm-card">
            <div class="pfm-card-head"><span class="hi"><i class="fas fa-store"></i></span><h5>¿Quién es?</h5></div>
            <div class="pfm-card-body">
                <div class="form-group">
                    <label for="inNombre">Nombre o razón social <span class="text-danger">*</span></label>
                    <input type="text" name="nombre" id="inNombre" maxlength="150" autocomplete="off" class="form-control @error('nombre') is-invalid @enderror"
                           value="{{ old('nombre', $c->nombre ?? '') }}" placeholder="Ej: Bodega Doña Rosa" required {{ $editar ? '' : 'autofocus' }} {{ $general ? 'readonly' : '' }}>
                    @error('nombre')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    @if($general)<small class="text-muted">Es el cliente de las ventas de mostrador: su nombre no se cambia.</small>@endif
                    <div class="cf-aviso mal" id="avNombre"><i class="fas fa-times-circle"></i><span></span></div>
                </div>
                <label>Tipo de cliente <span class="text-danger">*</span></label>
                <div class="cf-tipos" role="radiogroup" aria-label="Tipo de cliente">
                    @foreach($T as $clave => $t)
                        <input type="radio" name="tipo" id="tipo-{{ $clave }}" value="{{ $clave }}" {{ $tipoSel === $clave ? 'checked' : '' }} data-color="{{ $t['color'] }}" data-icono="{{ $t['icono'] }}" data-texto="{{ $t['label'] }}">
                        <label for="tipo-{{ $clave }}" style="--tc: {{ $t['color'] }}"><i class="fas {{ $t['icono'] }}"></i>{{ $t['label'] }}</label>
                    @endforeach
                </div>
                @error('tipo')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
            </div>
        </div>

        <div class="pfm-card">
            <div class="pfm-card-head"><span class="hi"><i class="fas fa-id-card"></i></span><h5>Documento</h5><small>opcional · para boletas y facturas</small></div>
            <div class="pfm-card-body">
                <div class="row">
                    <div class="col-md-6 form-group mb-md-0">
                        <label for="inRuc">RUC <small class="text-muted font-weight-normal">(11 números)</small></label>
                        <input type="text" name="ruc" id="inRuc" inputmode="numeric" maxlength="13" autocomplete="off" class="form-control @error('ruc') is-invalid @enderror"
                               value="{{ old('ruc', $c->ruc ?? '') }}" placeholder="20123456789">
                        @error('ruc')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <div class="cf-aviso" id="avRuc"><i class="fas"></i><span></span></div>
                    </div>
                    <div class="col-md-6 form-group mb-0">
                        <label for="inDni">DNI <small class="text-muted font-weight-normal">(8 números)</small></label>
                        <input type="text" name="dni" id="inDni" inputmode="numeric" maxlength="10" autocomplete="off" class="form-control @error('dni') is-invalid @enderror"
                               value="{{ old('dni', $c->dni ?? '') }}" placeholder="12345678">
                        @error('dni')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <div class="cf-aviso" id="avDni"><i class="fas"></i><span></span></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="pfm-card">
            <div class="pfm-card-head"><span class="hi"><i class="fas fa-address-book"></i></span><h5>¿Cómo lo contactamos?</h5></div>
            <div class="pfm-card-body">
                <div class="row">
                    <div class="col-md-6 form-group">
                        <label for="inContacto">Persona de contacto</label>
                        <input type="text" name="contacto" id="inContacto" maxlength="100" class="form-control" value="{{ old('contacto', $c->contacto ?? '') }}" placeholder="Ej: Sra. Rosa (dueña)">
                    </div>
                    <div class="col-md-6 form-group">
                        <label for="inTelefono">Teléfono / celular</label>
                        <input type="tel" name="telefono" id="inTelefono" maxlength="20" class="form-control @error('telefono') is-invalid @enderror" value="{{ old('telefono', $c->telefono ?? '') }}" placeholder="987 654 321">
                        @error('telefono')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <small class="text-muted" id="hintTel">Si es un celular de 9 números, se podrá escribir por WhatsApp.</small>
                    </div>
                    <div class="col-md-6 form-group mb-md-0">
                        <label for="inEmail">Correo</label>
                        <input type="email" name="email" id="inEmail" maxlength="150" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', $c->email ?? '') }}" placeholder="pedidos@cliente.com">
                        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>
        </div>

        <div class="pfm-card">
            <div class="pfm-card-head"><span class="hi"><i class="fas fa-map-marker-alt"></i></span><h5>¿Dónde se entrega?</h5></div>
            <div class="pfm-card-body">
                <div class="row">
                    <div class="col-md-8 form-group">
                        <label for="inDireccion">Dirección</label>
                        <input type="text" name="direccion" id="inDireccion" maxlength="255" class="form-control" value="{{ old('direccion', $c->direccion ?? '') }}" placeholder="Jr. Las Flores 456">
                    </div>
                    <div class="col-md-4 form-group">
                        <label for="inDistrito">Distrito</label>
                        <input type="text" name="distrito" id="inDistrito" maxlength="100" class="form-control" list="listaDistritos" value="{{ old('distrito', $c->distrito ?? '') }}" placeholder="Chorrillos">
                        <datalist id="listaDistritos">@foreach($distritos as $d)<option value="{{ $d }}">@endforeach</datalist>
                    </div>
                    <div class="col-12 form-group">
                        <label for="inReferencia">Referencia</label>
                        <input type="text" name="referencia" id="inReferencia" maxlength="255" class="form-control" value="{{ old('referencia', $c->referencia ?? '') }}" placeholder="Ej: frente al parque, puerta verde">
                    </div>
                    <div class="col-12 form-group mb-0">
                        <label for="inNotas">Notas del cliente</label>
                        <textarea name="notas" id="inNotas" maxlength="300" rows="2" class="form-control" placeholder="Ej: pide 200 panes de lunes a viernes a las 6 am · paga los viernes">{{ old('notas', $c->notas ?? '') }}</textarea>
                        <small class="text-muted"><span id="cuentaNotas">0</span>/300 · se ven en su tarjeta</small>
                    </div>
                </div>
            </div>
        </div>

        @unless($general)
        <div class="pfm-card">
            <div class="pfm-card-body">
                <div class="pfm-switch">
                    <div class="sw-txt">
                        <b>Activo</b>
                        <span>Si lo apagas, no aparece en la venta rápida, pero se conserva todo su historial.</span>
                    </div>
                    <input type="hidden" name="estado" id="inEstado" value="{{ $estado }}">
                    <label class="pfm-toggle"><input type="checkbox" id="swEstado" {{ $estado === 'activo' ? 'checked' : '' }} aria-label="Cliente activo"><span></span></label>
                </div>
                @if($editar && $c->compras_count)
                    <p class="small text-muted mb-0 mt-2"><i class="fas fa-info-circle mr-1"></i>Tiene {{ $c->compras_count }} compra(s) registradas.</p>
                @endif
            </div>
        </div>
        @endunless
    </div>

    <div class="pfm-lado">
        <span class="preview-label"><i class="fas fa-eye mr-1"></i>Así se verá</span>
        <div class="cf-vista mb-3" id="cvTarjeta">
            <div class="cv-cab">
                <div class="cv-av" id="cvIni">?</div>
                <div style="min-width:0"><b class="nm" id="cvNombre">Nombre del cliente</b><small class="text-muted" id="cvSub">Tipo</small></div>
            </div>
            <div class="small text-muted mb-2" id="cvDoc"></div>
            <div class="pv-contacto" id="cvContacto"></div>
        </div>
        <div class="pfm-card">
            <div class="pfm-card-body">
                <ul class="pfm-check mb-2">
                    <li data-chk="nombre"><i class="fas fa-circle"></i><span>Nombre sin repetir</span></li>
                    <li data-chk="doc"><i class="fas fa-circle"></i><span>RUC o DNI correcto (opcional)</span></li>
                    <li data-chk="contacto"><i class="fas fa-circle"></i><span>Teléfono o correo (recomendado)</span></li>
                </ul>
                <button type="submit" id="btnGuardarCli" class="btn {{ $editar ? 'btn-warning' : 'btn-primary' }} pfm-guardar">
                    <i class="fas fa-save mr-1"></i>{{ $editar ? 'Guardar cambios' : 'Registrar cliente' }}
                </button>
                <a href="{{ $volver ?? \App\Support\Volver::url(route('clientes.index')) }}" class="btn btn-link btn-block btn-sm text-muted">Cancelar</a>
            </div>
        </div>
    </div>
</div>

<script type="application/json" id="cfNombres">@json($nombres ?? [])</script>
<script type="application/json" id="cfDocs">@json((object) ($documentos ?? []))</script>

@push('scripts')
<script>
(function () {
    const $ = id => document.getElementById(id);
    const norm = t => (t || '').normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase().replace(/\s+/g, ' ').trim();
    const esc = t => String(t).replace(/[&<>"]/g, ch => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[ch]));
    const nombres = JSON.parse($('cfNombres').textContent || '[]'), docs = JSON.parse($('cfDocs').textContent || '{}');
    const tiposRuc = { '10': 'persona con negocio', '15': 'persona natural', '17': 'persona natural (extranjero)', '20': 'empresa' };
    const general = @json($general);

    function iniciales(n) {
        const p = n.replace(/\b(S\.?A\.?C?\.?|E\.?I\.?R\.?L\.?|S\.?R\.?L\.?|I\.?E\.?P?\.?)(?=\s|$)/gi, '').trim().split(/\s+/);
        return ((p[0] || '?')[0] + ((p[1] || '')[0] || '')).toUpperCase();
    }
    function aviso(id, clase, icono, html) {
        const a = $(id); a.className = 'cf-aviso ' + (clase ? clase + ' ver' : '');
        if (a.querySelector('i')) a.querySelector('i').className = 'fas ' + icono;
        a.querySelector('span').innerHTML = html || '';
    }
    function pintar() {
        const nombre = $('inNombre').value.trim();
        const igual = !general && nombre && nombres.find(n => norm(n) === norm(nombre));
        aviso('avNombre', igual ? 'mal' : '', 'fa-times-circle', igual ? 'Ya existe un cliente llamado <b>' + esc(igual) + '</b>.' : '');

        const ruc = $('inRuc').value.replace(/\D/g, '');
        let rucOk = true;
        if (!ruc) aviso('avRuc', '', '', '');
        else if (ruc.length !== 11) { rucOk = false; aviso('avRuc', 'mal', 'fa-times-circle', 'Lleva ' + ruc.length + ' de 11 números.'); }
        else if (!tiposRuc[ruc.slice(0, 2)]) { rucOk = false; aviso('avRuc', 'mal', 'fa-times-circle', 'Un RUC empieza con 10, 15, 17 o 20.'); }
        else if (docs[ruc]) { rucOk = false; aviso('avRuc', 'mal', 'fa-times-circle', 'Este RUC ya es de <b>' + esc(docs[ruc]) + '</b>.'); }
        else aviso('avRuc', 'ok', 'fa-check-circle', 'RUC de ' + tiposRuc[ruc.slice(0, 2)] + '.');

        const dni = $('inDni').value.replace(/\D/g, '');
        let dniOk = true;
        if (!dni) aviso('avDni', '', '', '');
        else if (dni.length !== 8) { dniOk = false; aviso('avDni', 'mal', 'fa-times-circle', 'Lleva ' + dni.length + ' de 8 números.'); }
        else if (docs[dni]) { dniOk = false; aviso('avDni', 'mal', 'fa-times-circle', 'Este DNI ya es de <b>' + esc(docs[dni]) + '</b>.'); }
        else aviso('avDni', 'ok', 'fa-check-circle', 'DNI correcto.');

        const tel = $('inTelefono').value.replace(/\D/g, ''), cel = /^9\d{8}$/.test(tel) || /^519\d{8}$/.test(tel);
        $('hintTel').innerHTML = !tel ? 'Si es un celular de 9 números, se podrá escribir por WhatsApp.' : (cel ? '<span class="text-success"><i class="fab fa-whatsapp"></i> Tendrá botón de WhatsApp.</span>' : 'Teléfono fijo: solo se podrá llamar.');

        const t = document.querySelector('#cfRaiz input[name=tipo]:checked');
        const activo = !$('swEstado') || $('swEstado').checked;
        $('cvTarjeta').style.setProperty('--tc', t ? t.dataset.color : '#b5451b');
        $('cvIni').textContent = nombre ? iniciales(nombre) : '?';
        $('cvIni').classList.toggle('inactivo', !activo);
        $('cvNombre').textContent = nombre || 'Nombre del cliente';
        const dist = $('inDistrito').value.trim();
        $('cvSub').textContent = (t ? t.dataset.texto : 'Tipo') + (dist ? ' · ' + dist : '') + (activo ? '' : ' · inactivo');
        const contacto = $('inContacto').value.trim();
        $('cvDoc').textContent = (ruc ? 'RUC ' + ruc : (dni ? 'DNI ' + dni : 'Sin RUC ni DNI')) + (contacto ? ' · ' + contacto : '');
        $('cvContacto').innerHTML = [
            cel ? '<a class="wa"><i class="fab fa-whatsapp"></i>WhatsApp</a>' : '',
            tel ? '<a><i class="fas fa-phone"></i>' + esc($('inTelefono').value.trim()) + '</a>' : '',
            $('inEmail').value.trim() ? '<a><i class="fas fa-envelope"></i>Correo</a>' : '',
        ].join('') || '<span class="small text-muted">Sin datos de contacto</span>';
        $('cuentaNotas').textContent = $('inNotas').value.length;

        const marcar = (k, ok, opcional, mal) => {
            const li = document.querySelector('#cfRaiz [data-chk=' + k + ']'); li.className = ok ? 'ok' : 'no';
            li.querySelector('i').className = 'fas ' + (ok ? 'fa-check-circle' : (mal ? 'fa-times-circle text-danger' : (opcional ? 'fa-info-circle' : 'fa-circle')));
        };
        marcar('nombre', nombre && !igual, false, !!igual);
        marcar('doc', rucOk && dniOk && !!(ruc || dni), true, !(rucOk && dniOk));
        marcar('contacto', !!(tel || $('inEmail').value.trim()), true);
        $('btnGuardarCli').disabled = !nombre || !!igual || !rucOk || !dniOk;
    }
    $('cfRaiz').addEventListener('input', pintar);
    $('cfRaiz').addEventListener('change', pintar);
    if ($('swEstado')) $('swEstado').addEventListener('change', function () { $('inEstado').value = this.checked ? 'activo' : 'inactivo'; pintar(); });
    pintar();
})();
</script>
@endpush
