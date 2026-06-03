@extends('layouts.app')
@section('content')
<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header"><h5>Registrar Nuevo Cliente</h5></div>
            <div class="card-body">
                <form action="{{ route('clientes.store') }}" method="POST">
                    @csrf
                    <div class="row">
                        <div class="col-md-8 mb-3">
                            <label class="form-label">Nombre / Razón Social *</label>
                            <input type="text" name="nombre" class="form-control @error('nombre') is-invalid @enderror" value="{{ old('nombre') }}">
                            @error('nombre')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Tipo de Cliente *</label>
                            <select name="tipo" class="form-select @error('tipo') is-invalid @enderror">
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
                        <div class="col-md-6 mb-3">
                            <label class="form-label">RUC</label>
                            <input type="text" name="ruc" class="form-control" value="{{ old('ruc') }}" placeholder="20123456789">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">DNI</label>
                            <input type="text" name="dni" class="form-control" value="{{ old('dni') }}" placeholder="12345678">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Teléfono</label>
                            <input type="text" name="telefono" class="form-control" value="{{ old('telefono') }}">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control" value="{{ old('email') }}">
                        </div>
                        <div class="col-md-8 mb-3">
                            <label class="form-label">Dirección</label>
                            <input type="text" name="direccion" class="form-control" value="{{ old('direccion') }}">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Distrito</label>
                            <input type="text" name="distrito" class="form-control" value="{{ old('distrito') }}">
                        </div>
                        <div class="col-12 mb-3">
                            <label class="form-label">Referencia</label>
                            <input type="text" name="referencia" class="form-control" value="{{ old('referencia') }}" placeholder="Ej: Frente al parque, cerca al mercado...">
                        </div>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">Guardar Cliente</button>
                        <a href="{{ route('clientes.index') }}" class="btn btn-secondary">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
