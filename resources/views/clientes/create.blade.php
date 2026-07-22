@extends('layouts.app')
@section('title', 'Nuevo Cliente')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('clientes.index') }}">Clientes</a></li>
    <li class="breadcrumb-item active">Nuevo</li>
@endsection

@include('productos.partials._styles')

@section('content')
<div class="row">
    <div class="col-lg-8">
        <div class="card prod-form-card">
            <div class="prod-form-header">
                <div class="prod-form-icon"><i class="fas fa-user-plus"></i></div>
                <div>
                    <h5>Nuevo Cliente</h5>
                    <p>Bodega, colegio, restaurante o cualquier punto al que distribuyas</p>
                </div>
            </div>
            <div class="prod-form-body">
                <form action="{{ route('clientes.store') }}" method="POST" id="formCliente">
                    @csrf

                    <label class="section-label"><i class="fas fa-store mr-1"></i> Datos del cliente</label>
                    <div class="row">
                        <div class="col-md-8 form-group">
                            <label>Nombre / Razón Social <span class="text-danger">*</span></label>
                            <input type="text" name="nombre" id="inNombre" class="form-control @error('nombre') is-invalid @enderror"
                                value="{{ old('nombre') }}" placeholder="Ej: Bodega Doña Rosa" required>
                            @error('nombre')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4 form-group">
                            <label>Tipo de Cliente <span class="text-danger">*</span></label>
                            <select name="tipo" id="inTipo" class="form-control @error('tipo') is-invalid @enderror" required>
                                <option value="">Seleccionar...</option>
                                <option value="bodega"       {{ old('tipo')=='bodega'       ? 'selected':'' }}>Bodega</option>
                                <option value="supermercado" {{ old('tipo')=='supermercado' ? 'selected':'' }}>Supermercado</option>
                                <option value="colegio"      {{ old('tipo')=='colegio'      ? 'selected':'' }}>Colegio</option>
                                <option value="restaurante"  {{ old('tipo')=='restaurante'  ? 'selected':'' }}>Restaurante</option>
                                <option value="panaderia"    {{ old('tipo')=='panaderia'    ? 'selected':'' }}>Otra Panadería</option>
                                <option value="particular"   {{ old('tipo')=='particular'   ? 'selected':'' }}>Particular</option>
                                <option value="otro"         {{ old('tipo')=='otro'         ? 'selected':'' }}>Otro</option>
                            </select>
                            @error('tipo')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <label class="section-label"><i class="fas fa-id-card mr-1"></i> Identificación</label>
                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label>RUC</label>
                            <input type="text" name="ruc" id="inRuc" class="form-control" value="{{ old('ruc') }}" placeholder="20123456789">
                        </div>
                        <div class="col-md-6 form-group">
                            <label>DNI</label>
                            <input type="text" name="dni" id="inDni" class="form-control" value="{{ old('dni') }}" placeholder="12345678">
                        </div>
                    </div>

                    <label class="section-label"><i class="fas fa-address-book mr-1"></i> Contacto</label>
                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label>Teléfono</label>
                            <input type="text" name="telefono" id="inTelefono" class="form-control" value="{{ old('telefono') }}" placeholder="999 999 999">
                        </div>
                        <div class="col-md-6 form-group">
                            <label>Email</label>
                            <input type="email" name="email" id="inEmail" class="form-control @error('email') is-invalid @enderror" value="{{ old('email') }}" placeholder="contacto@cliente.com">
                            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <label class="section-label"><i class="fas fa-map-marker-alt mr-1"></i> Ubicación</label>
                    <div class="row">
                        <div class="col-md-8 form-group">
                            <label>Dirección</label>
                            <input type="text" name="direccion" id="inDireccion" class="form-control" value="{{ old('direccion') }}">
                        </div>
                        <div class="col-md-4 form-group">
                            <label>Distrito</label>
                            <input type="text" name="distrito" id="inDistrito" class="form-control" value="{{ old('distrito') }}">
                        </div>
                        <div class="col-12 form-group">
                            <label>Referencia</label>
                            <input type="text" name="referencia" id="inReferencia" class="form-control" value="{{ old('referencia') }}" placeholder="Ej: Frente al parque, cerca al mercado...">
                        </div>
                    </div>

                    <div class="form-actions">
                        <a href="{{ route('clientes.index') }}" class="btn btn-light">Cancelar</a>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i>Guardar Cliente</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Vista previa en vivo: así se va a ver la fila del cliente en el listado --}}
    <div class="col-lg-4">
        <div class="preview-wrap">
            <span class="preview-label"><i class="fas fa-eye mr-1"></i>Así se verá en tu lista</span>
            <div class="item-card">
                <div class="item-card-body" style="padding:1.1rem 1.1rem .3rem;">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="row-icon"><i class="fas fa-user" id="prevIcono"></i></div>
                        <div class="ml-2">
                            <div class="row-title" id="prevNombre">Nombre del cliente</div>
                            <div class="row-subtitle" id="prevDistrito">Sin distrito</div>
                        </div>
                    </div>
                    <span class="badge-soft badge-soft-info" id="prevTipo">Tipo de cliente</span>

                    <div class="mt-3">
                        <div class="d-flex align-items-center gap-2 text-muted" style="font-size:.83rem; padding:.28rem 0;" id="prevTelefonoRow">
                            <i class="fas fa-phone-alt" style="width:16px;color:#adb5bd;"></i><span id="prevTelefono">—</span>
                        </div>
                        <div class="d-flex align-items-center gap-2 text-muted" style="font-size:.83rem; padding:.28rem 0;" id="prevEmailRow">
                            <i class="fas fa-envelope" style="width:16px;color:#adb5bd;"></i><span id="prevEmail">—</span>
                        </div>
                    </div>

                    <div class="item-card-stockrow mt-2">
                        <span>Ventas registradas</span>
                        <span class="badge-soft badge-soft-info">0</span>
                    </div>
                </div>
                <div class="item-card-footer">
                    <span class="badge-soft badge-soft-success">Activo</span>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    const $ = id => document.getElementById(id);
    const iconosPorTipo = {
        bodega: 'fa-store', supermercado: 'fa-cart-shopping', colegio: 'fa-school',
        restaurante: 'fa-utensils', panaderia: 'fa-bread-slice', particular: 'fa-user', otro: 'fa-building',
    };

    function actualizarPreview() {
        $('prevNombre').textContent = $('inNombre').value.trim() || 'Nombre del cliente';
        $('prevDistrito').textContent = $('inDistrito').value.trim() || 'Sin distrito';

        const tipo = $('inTipo').value;
        $('prevTipo').textContent = $('inTipo').selectedOptions[0]?.text && tipo ? $('inTipo').selectedOptions[0].text : 'Tipo de cliente';
        $('prevIcono').className = 'fas ' + (iconosPorTipo[tipo] || 'fa-user');

        $('prevTelefono').textContent = $('inTelefono').value.trim() || '—';
        $('prevEmail').textContent = $('inEmail').value.trim() || '—';
    }

    ['inNombre','inTipo','inDistrito','inTelefono','inEmail'].forEach(id => {
        $(id).addEventListener('input', actualizarPreview);
        $(id).addEventListener('change', actualizarPreview);
    });

    actualizarPreview();
</script>
@endpush
