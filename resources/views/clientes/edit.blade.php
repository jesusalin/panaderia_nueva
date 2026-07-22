@extends('layouts.app')
@section('title', 'Editar Cliente')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('clientes.index') }}">Clientes</a></li>
    <li class="breadcrumb-item active">Editar</li>
@endsection

@include('productos.partials._styles')

@section('content')
<div class="row">
    <div class="col-lg-8">
        <div class="card prod-form-card">
            <div class="prod-form-header">
                <div class="prod-form-icon"><i class="fas fa-user-edit"></i></div>
                <div>
                    <h5>Editar Cliente</h5>
                    <p>{{ $cliente->nombre }}</p>
                </div>
            </div>
            <div class="prod-form-body">
                <form action="{{ route('clientes.update', $cliente) }}" method="POST" id="formCliente">
                    @csrf @method('PUT')

                    <label class="section-label"><i class="fas fa-store mr-1"></i> Datos del cliente</label>
                    <div class="row">
                        <div class="col-md-8 form-group">
                            <label>Nombre / Razón Social <span class="text-danger">*</span></label>
                            <input type="text" name="nombre" id="inNombre" class="form-control @error('nombre') is-invalid @enderror"
                                value="{{ old('nombre', $cliente->nombre) }}" required>
                            @error('nombre')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4 form-group">
                            <label>Tipo de Cliente <span class="text-danger">*</span></label>
                            <select name="tipo" id="inTipo" class="form-control @error('tipo') is-invalid @enderror" required>
                                <option value="bodega"       {{ old('tipo',$cliente->tipo)=='bodega'       ? 'selected':'' }}>Bodega</option>
                                <option value="supermercado" {{ old('tipo',$cliente->tipo)=='supermercado' ? 'selected':'' }}>Supermercado</option>
                                <option value="colegio"      {{ old('tipo',$cliente->tipo)=='colegio'      ? 'selected':'' }}>Colegio</option>
                                <option value="restaurante"  {{ old('tipo',$cliente->tipo)=='restaurante'  ? 'selected':'' }}>Restaurante</option>
                                <option value="panaderia"    {{ old('tipo',$cliente->tipo)=='panaderia'    ? 'selected':'' }}>Otra Panadería</option>
                                <option value="particular"   {{ old('tipo',$cliente->tipo)=='particular'   ? 'selected':'' }}>Particular</option>
                                <option value="otro"         {{ old('tipo',$cliente->tipo)=='otro'         ? 'selected':'' }}>Otro</option>
                            </select>
                            @error('tipo')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <label class="section-label"><i class="fas fa-id-card mr-1"></i> Identificación</label>
                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label>RUC</label>
                            <input type="text" name="ruc" id="inRuc" class="form-control" value="{{ old('ruc', $cliente->ruc) }}">
                        </div>
                        <div class="col-md-6 form-group">
                            <label>DNI</label>
                            <input type="text" name="dni" id="inDni" class="form-control" value="{{ old('dni', $cliente->dni) }}">
                        </div>
                    </div>

                    <label class="section-label"><i class="fas fa-address-book mr-1"></i> Contacto</label>
                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label>Teléfono</label>
                            <input type="text" name="telefono" id="inTelefono" class="form-control" value="{{ old('telefono', $cliente->telefono) }}">
                        </div>
                        <div class="col-md-6 form-group">
                            <label>Email</label>
                            <input type="email" name="email" id="inEmail" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', $cliente->email) }}">
                            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <label class="section-label"><i class="fas fa-map-marker-alt mr-1"></i> Ubicación</label>
                    <div class="row">
                        <div class="col-md-8 form-group">
                            <label>Dirección</label>
                            <input type="text" name="direccion" id="inDireccion" class="form-control" value="{{ old('direccion', $cliente->direccion) }}">
                        </div>
                        <div class="col-md-4 form-group">
                            <label>Distrito</label>
                            <input type="text" name="distrito" id="inDistrito" class="form-control" value="{{ old('distrito', $cliente->distrito) }}">
                        </div>
                        <div class="col-12 form-group">
                            <label>Referencia</label>
                            <input type="text" name="referencia" id="inReferencia" class="form-control" value="{{ old('referencia', $cliente->referencia) }}">
                        </div>
                    </div>

                    <label class="section-label"><i class="fas fa-toggle-on mr-1"></i> Estado</label>
                    <div class="form-group" style="max-width:220px;">
                        <select name="estado" id="inEstado" class="form-control">
                            <option value="activo"   {{ old('estado', $cliente->estado) === 'activo'   ? 'selected' : '' }}>Activo</option>
                            <option value="inactivo" {{ old('estado', $cliente->estado) === 'inactivo' ? 'selected' : '' }}>Inactivo</option>
                        </select>
                    </div>

                    <div class="form-actions">
                        <a href="{{ route('clientes.index') }}" class="btn btn-light">Cancelar</a>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i>Actualizar</button>
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
                        <div class="row-icon" id="prevIconoWrap" style="background:{{ $cliente->color_tipo }};"><i class="fas {{ $cliente->icono }}" id="prevIcono"></i></div>
                        <div class="ml-2">
                            <div class="row-title" id="prevNombre">{{ $cliente->nombre }}</div>
                            <div class="row-subtitle" id="prevDistrito">{{ $cliente->distrito ?? 'Sin distrito' }}</div>
                        </div>
                    </div>
                    <span class="badge-soft badge-soft-info" id="prevTipo">{{ ucfirst($cliente->tipo ?? 'particular') }}</span>

                    <div class="mt-3">
                        <div class="d-flex align-items-center gap-2 text-muted" style="font-size:.83rem; padding:.28rem 0;" id="prevTelefonoRow">
                            <i class="fas fa-phone-alt" style="width:16px;color:#adb5bd;"></i><span id="prevTelefono">{{ $cliente->telefono ?? '—' }}</span>
                        </div>
                        <div class="d-flex align-items-center gap-2 text-muted" style="font-size:.83rem; padding:.28rem 0;" id="prevEmailRow">
                            <i class="fas fa-envelope" style="width:16px;color:#adb5bd;"></i><span id="prevEmail">{{ $cliente->email ?? '—' }}</span>
                        </div>
                    </div>

                    <div class="item-card-stockrow mt-2">
                        <span>Ventas registradas</span>
                        <span class="badge-soft badge-soft-info">{{ $cliente->ventas()->count() }}</span>
                    </div>
                </div>
                <div class="item-card-footer">
                    <span class="badge-soft {{ $cliente->estado === 'activo' ? 'badge-soft-success' : 'badge-soft-secondary' }}" id="prevEstado">
                        {{ $cliente->estado === 'activo' ? 'Activo' : 'Inactivo' }}
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    const $ = id => document.getElementById(id);
    const tiposVisual = {
        bodega:       { icono: 'fa-store',         color: '#b5451b' },
        supermercado: { icono: 'fa-cart-shopping',  color: '#2170a3' },
        colegio:      { icono: 'fa-school',         color: '#8e44ad' },
        restaurante:  { icono: 'fa-utensils',       color: '#c0392b' },
        panaderia:    { icono: 'fa-bread-slice',    color: '#b9770e' },
        particular:   { icono: 'fa-user',           color: '#1e8e5a' },
        otro:         { icono: 'fa-building',       color: '#6c757d' },
    };

    function actualizarPreview() {
        $('prevNombre').textContent = $('inNombre').value.trim() || 'Nombre del cliente';
        $('prevDistrito').textContent = $('inDistrito').value.trim() || 'Sin distrito';

        const tipo = $('inTipo').value;
        const visual = tiposVisual[tipo];
        $('prevTipo').textContent = $('inTipo').selectedOptions[0]?.text || 'Tipo de cliente';
        $('prevIcono').className = 'fas ' + (visual?.icono || 'fa-user');
        $('prevIconoWrap').style.background = visual?.color || 'linear-gradient(135deg, #1a1a2e, #b5451b)';

        $('prevTelefono').textContent = $('inTelefono').value.trim() || '—';
        $('prevEmail').textContent = $('inEmail').value.trim() || '—';

        const activo = $('inEstado').value === 'activo';
        $('prevEstado').textContent = activo ? 'Activo' : 'Inactivo';
        $('prevEstado').className = 'badge-soft ' + (activo ? 'badge-soft-success' : 'badge-soft-secondary');
    }

    ['inNombre','inTipo','inDistrito','inTelefono','inEmail','inEstado'].forEach(id => {
        $(id).addEventListener('input', actualizarPreview);
        $(id).addEventListener('change', actualizarPreview);
    });

    actualizarPreview();
</script>
@endpush
