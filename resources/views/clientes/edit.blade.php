@extends('layouts.app')
@section('content')
<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header"><h5>Editar Cliente: {{ $cliente->nombre }}</h5></div>
            <div class="card-body">
                <form action="{{ route('clientes.update', $cliente) }}" method="POST">
                    @csrf @method('PUT')
                    <div class="row">
                        <div class="col-md-8 mb-3">
                            <label class="form-label">Nombre / Razón Social *</label>
                            <input type="text" name="nombre" class="form-control @error('nombre') is-invalid @enderror" value="{{ old('nombre', $cliente->nombre) }}">
                            @error('nombre')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Tipo de Cliente *</label>
                            <select name="tipo" class="form-select">
                                <option value="bodega"       {{ old('tipo',$cliente->tipo)=='bodega'       ? 'selected':'' }}>Bodega</option>
                                <option value="supermercado" {{ old('tipo',$cliente->tipo)=='supermercado' ? 'selected':'' }}>Supermercado</option>
                                <option value="colegio"      {{ old('tipo',$cliente->tipo)=='colegio'      ? 'selected':'' }}>Colegio</option>
                                <option value="restaurante"  {{ old('tipo',$cliente->tipo)=='restaurante'  ? 'selected':'' }}>Restaurante</option>
                                <option value="panaderia"    {{ old('tipo',$cliente->tipo)=='panaderia'    ? 'selected':'' }}>Otra Panadería</option>
                                <option value="particular"   {{ old('tipo',$cliente->tipo)=='particular'   ? 'selected':'' }}>Particular</option>
                                <option value="otro"         {{ old('tipo',$cliente->tipo)=='otro'         ? 'selected':'' }}>Otro</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">RUC</label>
                            <input type="text" name="ruc" class="form-control" value="{{ old('ruc', $cliente->ruc) }}">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">DNI</label>
                            <input type="text" name="dni" class="form-control" value="{{ old('dni', $cliente->dni) }}">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Teléfono</label>
                            <input type="text" name="telefono" class="form-control" value="{{ old('telefono', $cliente->telefono) }}">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control" value="{{ old('email', $cliente->email) }}">
                        </div>
                        <div class="col-md-8 mb-3">
                            <label class="form-label">Dirección</label>
                            <input type="text" name="direccion" class="form-control" value="{{ old('direccion', $cliente->direccion) }}">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Distrito</label>
                            <input type="text" name="distrito" class="form-control" value="{{ old('distrito', $cliente->distrito) }}">
                        </div>
                        <div class="col-12 mb-3">
                            <label class="form-label">Referencia</label>
                            <input type="text" name="referencia" class="form-control" value="{{ old('referencia', $cliente->referencia) }}">
                        </div>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">Actualizar</button>
                        <a href="{{ route('clientes.index') }}" class="btn btn-secondary">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
