@extends('layouts.app')
@section('title', 'Nuevo Proveedor')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('proveedores.index') }}">Proveedores</a></li>
    <li class="breadcrumb-item active">Nuevo</li>
@endsection

@include('productos.partials._styles')

@section('content')
<div class="row">
    <div class="col-lg-8">
        <div class="card prod-form-card">
            <div class="prod-form-header">
                <div class="prod-form-icon"><i class="fas fa-truck"></i></div>
                <div>
                    <h5>Nuevo Proveedor</h5>
                    <p>Se agregará a la lista de empresas que te abastecen de materia prima</p>
                </div>
            </div>
            <div class="prod-form-body">
                <form action="{{ route('proveedores.store') }}" method="POST" id="formProveedor">
                    @csrf

                    <label class="section-label"><i class="fas fa-info-circle mr-1"></i> Datos de la empresa</label>
                    <div class="row">
                        <div class="col-md-8 form-group">
                            <label>Nombre / Razón Social <span class="text-danger">*</span></label>
                            <input type="text" name="nombre" id="inNombre" class="form-control @error('nombre') is-invalid @enderror"
                                value="{{ old('nombre') }}" placeholder="Ej: Distribuidora El Molino S.A.C." required>
                            @error('nombre')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4 form-group">
                            <label>RUC</label>
                            <input type="text" name="ruc" id="inRuc" class="form-control" value="{{ old('ruc') }}" placeholder="20123456789">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Dirección</label>
                        <input type="text" name="direccion" id="inDireccion" class="form-control" value="{{ old('direccion') }}" placeholder="Av. Ejemplo 123, Chorrillos">
                    </div>

                    <label class="section-label"><i class="fas fa-address-book mr-1"></i> Contacto</label>
                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label>Persona de Contacto</label>
                            <input type="text" name="contacto" id="inContacto" class="form-control" value="{{ old('contacto') }}" placeholder="Nombre del representante">
                        </div>
                        <div class="col-md-3 form-group">
                            <label>Teléfono</label>
                            <input type="text" name="telefono" id="inTelefono" class="form-control" value="{{ old('telefono') }}" placeholder="999 999 999">
                        </div>
                        <div class="col-md-3 form-group">
                            <label>Email</label>
                            <input type="email" name="email" id="inEmail" class="form-control @error('email') is-invalid @enderror" value="{{ old('email') }}" placeholder="contacto@empresa.com">
                            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Estado</label>
                        <select name="estado" id="inEstado" class="form-control">
                            <option value="activo" {{ old('estado','activo') === 'activo' ? 'selected' : '' }}>Activo</option>
                            <option value="inactivo" {{ old('estado') === 'inactivo' ? 'selected' : '' }}>Inactivo</option>
                        </select>
                    </div>

                    <div class="form-actions">
                        <a href="{{ route('proveedores.index') }}" class="btn btn-light">Cancelar</a>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i>Guardar Proveedor</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Vista previa en vivo: así se va a ver la tarjeta del proveedor --}}
    <div class="col-lg-4">
        <div class="preview-wrap">
            <span class="preview-label"><i class="fas fa-eye mr-1"></i>Así se verá la tarjeta</span>
            <div class="item-card">
                <div class="item-card-body" style="padding:1rem 1.1rem .9rem;">
                    <div class="row-icon" style="margin-bottom:.6rem;"><i class="fas fa-truck"></i></div>
                    <div class="item-card-cat" id="prevRuc">Sin RUC registrado</div>
                    <h3 class="item-card-title" id="prevNombre">Nombre del proveedor</h3>

                    <div class="mt-2">
                        <div class="d-flex align-items-center gap-2 text-muted" style="font-size:.83rem; padding:.28rem 0;" id="prevContactoRow">
                            <i class="fas fa-user" style="width:16px;color:#adb5bd;"></i><span id="prevContacto">—</span>
                        </div>
                        <div class="d-flex align-items-center gap-2 text-muted" style="font-size:.83rem; padding:.28rem 0;" id="prevTelefonoRow">
                            <i class="fas fa-phone-alt" style="width:16px;color:#adb5bd;"></i><span id="prevTelefono">—</span>
                        </div>
                        <div class="d-flex align-items-center gap-2 text-muted" style="font-size:.83rem; padding:.28rem 0;" id="prevEmailRow">
                            <i class="fas fa-envelope" style="width:16px;color:#adb5bd;"></i><span id="prevEmail">—</span>
                        </div>
                    </div>

                    <div class="item-card-stockrow mt-2">
                        <span>Compras registradas</span>
                        <span class="badge-soft badge-soft-info">0</span>
                    </div>
                </div>
                <div class="item-card-footer">
                    <span class="badge-soft badge-soft-success" id="prevEstado">Activo</span>
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

    actualizarPreview();
</script>
@endpush
