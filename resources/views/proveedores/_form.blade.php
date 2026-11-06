{{-- Formulario de proveedor (nuevo y editar). Variables: $proveedor (null al crear), $nombres y $rucs (de los otros proveedores) --}}
@php
    $editar = isset($proveedor) && $proveedor;
    $p = $editar ? $proveedor : null;
    $estado = old('estado', $p->estado ?? 'activo');
@endphp
@include('partials.form-secciones')
@include('partials.compras-estilos')

@once
@push('styles')
<style>
    .pf-aviso { display: none; gap: .5rem; align-items: flex-start; border-radius: 10px; padding: .5rem .75rem; font-size: .8rem; margin-top: .45rem; }
    .pf-aviso.ver { display: flex; }
    .pf-aviso i { margin-top: .15rem; }
    .pf-aviso.mal { background: #fdecea; color: #a93226; }
    .pf-aviso.ok { background: #e9f8ef; color: #1e7a4c; }
    body.dark-mode .pf-aviso.mal { background: rgba(231,76,60,.15); color: #f1a097; }
    body.dark-mode .pf-aviso.ok { background: rgba(46,204,113,.12); color: #6ee7a5; }
    .pf-vista { background: #fff; border-radius: 14px; box-shadow: 0 2px 12px rgba(0,0,0,.06); padding: 1rem; }
    body.dark-mode .pf-vista { background: #1f1f33; }
    .pf-vista .pv-cab { display: flex; gap: .7rem; align-items: center; margin-bottom: .6rem; }
    .pf-vista b.nm { display: block; font-weight: 800; color: #1a1a2e; line-height: 1.2; }
    body.dark-mode .pf-vista b.nm { color: #f0f0f7; }
</style>
@endpush
@endonce

<div class="pfm-grid" id="pfRaiz">
    <div>
        <div class="pfm-card">
            <div class="pfm-card-head"><span class="hi"><i class="fas fa-building"></i></span><h5>Datos de la empresa</h5></div>
            <div class="pfm-card-body">
                <div class="form-group">
                    <label for="inNombre">Nombre o razón social <span class="text-danger">*</span></label>
                    <input type="text" name="nombre" id="inNombre" maxlength="150" autocomplete="off" class="form-control @error('nombre') is-invalid @enderror"
                           value="{{ old('nombre', $p->nombre ?? '') }}" placeholder="Ej: Molinos del Sur S.A.C." required {{ $editar ? '' : 'autofocus' }}>
                    @error('nombre')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    <div class="pf-aviso mal" id="avNombre"><i class="fas fa-times-circle"></i><span></span></div>
                </div>
                <div class="form-group mb-0">
                    <label for="inRuc">RUC <small class="text-muted font-weight-normal">(opcional, 11 números)</small></label>
                    <input type="text" name="ruc" id="inRuc" inputmode="numeric" maxlength="13" autocomplete="off" class="form-control @error('ruc') is-invalid @enderror"
                           value="{{ old('ruc', $p->ruc ?? '') }}" placeholder="20123456789" style="max-width:260px">
                    @error('ruc')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    <div class="pf-aviso" id="avRuc"><i class="fas"></i><span></span></div>
                </div>
            </div>
        </div>

        <div class="pfm-card">
            <div class="pfm-card-head"><span class="hi"><i class="fas fa-address-book"></i></span><h5>¿Cómo lo contactamos?</h5></div>
            <div class="pfm-card-body">
                <div class="row">
                    <div class="col-md-6 form-group">
                        <label for="inContacto">Persona de contacto</label>
                        <input type="text" name="contacto" id="inContacto" maxlength="100" class="form-control" value="{{ old('contacto', $p->contacto ?? '') }}" placeholder="Ej: Rosa Quispe (ventas)">
                    </div>
                    <div class="col-md-6 form-group">
                        <label for="inTelefono">Teléfono / celular</label>
                        <input type="tel" name="telefono" id="inTelefono" maxlength="20" class="form-control @error('telefono') is-invalid @enderror" value="{{ old('telefono', $p->telefono ?? '') }}" placeholder="987 654 321">
                        @error('telefono')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <small class="text-muted" id="hintTel">Si es un celular de 9 números, se podrá escribir por WhatsApp.</small>
                    </div>
                    <div class="col-md-6 form-group mb-md-0">
                        <label for="inEmail">Correo</label>
                        <input type="email" name="email" id="inEmail" maxlength="150" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', $p->email ?? '') }}" placeholder="ventas@empresa.com">
                        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6 form-group mb-0">
                        <label for="inDireccion">Dirección</label>
                        <input type="text" name="direccion" id="inDireccion" maxlength="255" class="form-control" value="{{ old('direccion', $p->direccion ?? '') }}" placeholder="Av. Ejemplo 123, Chorrillos">
                    </div>
                </div>
            </div>
        </div>

        <div class="pfm-card">
            <div class="pfm-card-body">
                <div class="pfm-switch">
                    <div class="sw-txt">
                        <b>Activo</b>
                        <span>Si lo apagas, no aparece para nuevas compras, pero se conserva todo su historial.</span>
                    </div>
                    <input type="hidden" name="estado" id="inEstado" value="{{ $estado }}">
                    <label class="pfm-toggle"><input type="checkbox" id="swEstado" {{ $estado === 'activo' ? 'checked' : '' }} aria-label="Proveedor activo"><span></span></label>
                </div>
                @if($editar && ($p->insumos_count || $p->compras_count))
                    <p class="small text-muted mb-0 mt-2"><i class="fas fa-info-circle mr-1"></i>Tiene {{ $p->insumos_count }} insumo(s) asignado(s) y {{ $p->compras_count }} compra(s) registradas.</p>
                @endif
            </div>
        </div>
    </div>

    <div class="pfm-lado">
        <span class="preview-label"><i class="fas fa-eye mr-1"></i>Así se verá</span>
        <div class="pf-vista mb-3">
            <div class="pv-cab">
                <div class="pv-avatar" id="pvIni">?</div>
                <div><b class="nm" id="pvNombre">Nombre del proveedor</b><small class="text-muted" id="pvSub">Sin RUC</small></div>
            </div>
            <div class="pv-contacto" id="pvContacto"></div>
        </div>
        <div class="pfm-card">
            <div class="pfm-card-body">
                <ul class="pfm-check mb-2">
                    <li data-chk="nombre"><i class="fas fa-circle"></i><span>Nombre sin repetir</span></li>
                    <li data-chk="ruc"><i class="fas fa-circle"></i><span>RUC correcto (opcional)</span></li>
                    <li data-chk="contacto"><i class="fas fa-circle"></i><span>Teléfono o correo (recomendado)</span></li>
                </ul>
                <button type="submit" id="btnGuardarProv" class="btn {{ $editar ? 'btn-warning' : 'btn-primary' }} pfm-guardar">
                    <i class="fas fa-save mr-1"></i>{{ $editar ? 'Guardar cambios' : 'Crear proveedor' }}
                </button>
                <a href="{{ $volver ?? route('proveedores.index') }}" class="btn btn-link btn-block btn-sm text-muted">Cancelar</a>
            </div>
        </div>
    </div>
</div>

<script type="application/json" id="pfNombres">@json($nombres ?? [])</script>
<script type="application/json" id="pfRucs">@json($rucs ?? (object) [])</script>

@push('scripts')
<script>
(function () {
    const $ = id => document.getElementById(id);
    const norm = t => (t || '').normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase().replace(/\s+/g, ' ').trim();
    const esc = t => String(t).replace(/[&<>"]/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]));
    const nombres = JSON.parse($('pfNombres').textContent || '[]'), rucs = JSON.parse($('pfRucs').textContent || '{}');
    const tiposRuc = { '10': 'persona con negocio', '15': 'persona natural', '17': 'persona natural (extranjero)', '20': 'empresa' };

    function iniciales(n) {
        const p = n.replace(/\b(S\.?A\.?C?\.?|E\.?I\.?R\.?L\.?|S\.?R\.?L\.?)\b/gi, '').trim().split(/\s+/);
        return ((p[0] || '?')[0] + ((p[1] || '')[0] || '')).toUpperCase();
    }
    function aviso(id, clase, icono, html) {
        const a = $(id); a.className = 'pf-aviso ' + (clase ? clase + ' ver' : '');
        if (a.querySelector('i')) a.querySelector('i').className = 'fas ' + icono;
        a.querySelector('span').innerHTML = html || '';
    }
    function pintar() {
        const nombre = $('inNombre').value.trim();
        const igual = nombre && nombres.find(n => norm(n) === norm(nombre));
        aviso('avNombre', igual ? 'mal' : '', 'fa-times-circle', igual ? 'Ya existe un proveedor llamado <b>' + esc(igual) + '</b>.' : '');

        const ruc = $('inRuc').value.replace(/\D/g, '');
        let rucOk = true;
        if (!ruc) aviso('avRuc', '', '', '');
        else if (ruc.length !== 11) { rucOk = false; aviso('avRuc', 'mal', 'fa-times-circle', 'Lleva ' + ruc.length + ' de 11 números.'); }
        else if (!tiposRuc[ruc.slice(0, 2)]) { rucOk = false; aviso('avRuc', 'mal', 'fa-times-circle', 'Un RUC empieza con 10, 15, 17 o 20.'); }
        else if (rucs[ruc]) { rucOk = false; aviso('avRuc', 'mal', 'fa-times-circle', 'Este RUC ya es de <b>' + esc(rucs[ruc]) + '</b>.'); }
        else aviso('avRuc', 'ok', 'fa-check-circle', 'RUC de ' + tiposRuc[ruc.slice(0, 2)] + '.');

        const tel = $('inTelefono').value.replace(/\D/g, ''), cel = /^9\d{8}$/.test(tel) || /^519\d{8}$/.test(tel);
        $('hintTel').innerHTML = !tel ? 'Si es un celular de 9 números, se podrá escribir por WhatsApp.' : (cel ? '<span class="text-success"><i class="fab fa-whatsapp"></i> Tendrá botón de WhatsApp.</span>' : 'Teléfono fijo: solo se podrá llamar.');

        $('pvIni').textContent = nombre ? iniciales(nombre) : '?';
        $('pvIni').classList.toggle('inactivo', !$('swEstado').checked);
        $('pvNombre').textContent = nombre || 'Nombre del proveedor';
        const contacto = $('inContacto').value.trim();
        $('pvSub').textContent = (ruc ? 'RUC ' + ruc : 'Sin RUC') + (contacto ? ' · ' + contacto : '') + ($('swEstado').checked ? '' : ' · inactivo');
        $('pvContacto').innerHTML = [
            tel ? '<a><i class="fas fa-phone"></i>' + esc($('inTelefono').value.trim()) + '</a>' : '',
            cel ? '<a class="wa"><i class="fab fa-whatsapp"></i>WhatsApp</a>' : '',
            $('inEmail').value.trim() ? '<a><i class="fas fa-envelope"></i>Correo</a>' : '',
        ].join('') || '<span class="small text-muted">Sin datos de contacto</span>';

        const marcar = (k, ok, opcional, mal) => {
            const li = document.querySelector('#pfRaiz [data-chk=' + k + ']'); li.className = ok ? 'ok' : 'no';
            li.querySelector('i').className = 'fas ' + (ok ? 'fa-check-circle' : (mal ? 'fa-times-circle text-danger' : (opcional ? 'fa-info-circle' : 'fa-circle')));
        };
        marcar('nombre', nombre && !igual, false, !!igual);
        marcar('ruc', rucOk && !!ruc, true, !rucOk);
        marcar('contacto', !!(tel || $('inEmail').value.trim()), true);
        $('btnGuardarProv').disabled = !nombre || !!igual || !rucOk;
    }
    $('pfRaiz').addEventListener('input', pintar);
    $('swEstado').addEventListener('change', function () { $('inEstado').value = this.checked ? 'activo' : 'inactivo'; pintar(); });
    pintar();
})();
</script>
@endpush
