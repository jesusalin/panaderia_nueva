@extends('layouts.app')
@section('title', 'Editar Proveedor')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('proveedores.index') }}">Proveedores</a></li>
    <li class="breadcrumb-item active">Editar</li>
@endsection

@include('productos.partials._styles')

@section('content')
<div class="row">
    <div class="col-lg-8">
        <div class="card prod-form-card">
            <div class="prod-form-header">
                <div class="prod-form-icon"><i class="fas fa-edit"></i></div>
                <div>
                    <h5>Editar: {{ $proveedor->nombre }}</h5>
                    <p>Actualiza los datos de este proveedor</p>
                </div>
            </div>
            <div class="prod-form-body">
                <form action="{{ route('proveedores.update', $proveedor) }}" method="POST" id="formProveedor">
                    @csrf @method('PUT')

                    <label class="section-label"><i class="fas fa-info-circle mr-1"></i> Datos de la empresa</label>
                    <div class="row">
                        <div class="col-md-8 form-group">
                            <label>Nombre / Razón Social <span class="text-danger">*</span></label>
                            <input type="text" name="nombre" id="inNombre" class="form-control @error('nombre') is-invalid @enderror"
                                value="{{ old('nombre', $proveedor->nombre) }}" required>
                            @error('nombre')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4 form-group">
                            <label>RUC</label>
                            <input type="text" name="ruc" id="inRuc" class="form-control" value="{{ old('ruc', $proveedor->ruc) }}" placeholder="20123456789">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Dirección</label>
                        <input type="text" name="direccion" id="inDireccion" class="form-control" value="{{ old('direccion', $proveedor->direccion) }}" placeholder="Av. Ejemplo 123, Chorrillos">
                    </div>

                    <label class="section-label"><i class="fas fa-address-book mr-1"></i> Contacto</label>
                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label>Persona de Contacto</label>
                            <input type="text" name="contacto" id="inContacto" class="form-control" value="{{ old('contacto', $proveedor->contacto) }}" placeholder="Nombre del representante">
                        </div>
                        <div class="col-md-3 form-group">
                            <label>Teléfono</label>
                            <input type="text" name="telefono" id="inTelefono" class="form-control" value="{{ old('telefono', $proveedor->telefono) }}" placeholder="999 999 999">
                        </div>
                        <div class="col-md-3 form-group">
                            <label>Email</label>
                            <input type="email" name="email" id="inEmail" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', $proveedor->email) }}" placeholder="contacto@empresa.com">
                            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Estado</label>
                        <select name="estado" id="inEstado" class="form-control">
                            <option value="activo"   {{ old('estado', $proveedor->estado) === 'activo'   ? 'selected' : '' }}>Activo</option>
                            <option value="inactivo" {{ old('estado', $proveedor->estado) === 'inactivo' ? 'selected' : '' }}>Inactivo</option>
                        </select>
                        <small class="form-text text-muted">
                            <i class="fas fa-info-circle mr-1"></i>También puedes cambiar el estado directamente desde el interruptor en el listado.
                        </small>
                    </div>

                    <div class="form-actions">
                        <a href="{{ route('proveedores.index') }}" class="btn btn-light">Cancelar</a>
                        <button type="submit" class="btn btn-warning"><i class="fas fa-save mr-1"></i>Actualizar Proveedor</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Vista previa en vivo --}}
    <div class="col-lg-4">
        <div class="preview-wrap">
            <span class="preview-label"><i class="fas fa-eye mr-1"></i>Así se verá la tarjeta</span>
            <div class="item-card">
                <div class="item-card-body" style="padding:1rem 1.1rem .9rem;">
                    <div class="row-icon" style="margin-bottom:.6rem;"><i class="fas fa-truck"></i></div>
                    <div class="item-card-cat" id="prevRuc">{{ $proveedor->ruc ?: 'Sin RUC registrado' }}</div>
                    <h3 class="item-card-title" id="prevNombre">{{ $proveedor->nombre }}</h3>

                    <div class="mt-2">
                        <div class="d-flex align-items-center gap-2 text-muted" style="font-size:.83rem; padding:.28rem 0;">
                            <i class="fas fa-user" style="width:16px;color:#adb5bd;"></i><span id="prevContacto">{{ $proveedor->contacto ?: '—' }}</span>
                        </div>
                        <div class="d-flex align-items-center gap-2 text-muted" style="font-size:.83rem; padding:.28rem 0;">
                            <i class="fas fa-phone-alt" style="width:16px;color:#adb5bd;"></i><span id="prevTelefono">{{ $proveedor->telefono ?: '—' }}</span>
                        </div>
                        <div class="d-flex align-items-center gap-2 text-muted" style="font-size:.83rem; padding:.28rem 0;">
                            <i class="fas fa-envelope" style="width:16px;color:#adb5bd;"></i><span id="prevEmail">{{ $proveedor->email ?: '—' }}</span>
                        </div>
                    </div>

                    <div class="item-card-stockrow mt-2">
                        <span>Compras registradas</span>
                        <span class="badge-soft badge-soft-info">{{ $proveedor->compras()->count() }}</span>
                    </div>
                </div>
                <div class="item-card-footer">
                    <span class="badge-soft {{ $proveedor->estado === 'activo' ? 'badge-soft-success' : 'badge-soft-secondary' }}" id="prevEstado">
                        {{ ucfirst($proveedor->estado) }}
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

    function actualizarPreview() {
        $('prevNombre').textContent = $('inNombre').value.trim() || 'Nombre del proveedor';
        $('prevRuc').textContent = $('inRuc').value.trim() || 'Sin RUC registrado';

        $('prevContacto').textContent = $('inContacto').value.trim() || '—';
        $('prevTelefono').textContent = $('inTelefono').value.trim() || '—';
        $('prevEmail').textContent = $('inEmail').value.trim() || '—';

        const activo = $('inEstado').value === 'activo';
        $('prevEstado').textContent = activo ? 'Activo' : 'Inactivo';
        $('prevEstado').className = 'badge-soft ' + (activo ? 'badge-soft-success' : 'badge-soft-secondary');
    }

    ['inNombre','inRuc','inContacto','inTelefono','inEmail','inEstado'].forEach(id => {
        $(id).addEventListener('input', actualizarPreview);
        $(id).addEventListener('change', actualizarPreview);
    });
</script>
@endpush
