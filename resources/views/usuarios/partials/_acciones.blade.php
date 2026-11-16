{{-- Qué puede hacer dentro de la pantalla $pantalla (se ve solo si la pantalla está marcada). --}}
@if(!empty(\App\Models\Usuario::ACCIONES[$pantalla]))
    <div class="perm-acciones" data-pantalla="{{ $pantalla }}">
        <span class="perm-acciones-lbl">Dentro de esta pantalla puede:</span>
        @foreach(\App\Models\Usuario::ACCIONES[$pantalla] as $accion => $def)
            @php $clave = $pantalla . '.' . $accion; @endphp
            <label class="perm-accion">
                <input type="checkbox" name="acciones[]" value="{{ $clave }}" class="js-perm-accion" {{ in_array($clave, $quitadasSel, true) ? '' : 'checked' }}>
                <span>{{ $def['nombre'] }}</span>
            </label>
        @endforeach
    </div>
@endif
