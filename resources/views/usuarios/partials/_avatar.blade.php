{{-- Foto de perfil o iniciales. Uso: @include('usuarios.partials._avatar', ['u' => $usuario, 'tam' => 44]) --}}
@php $tam = $tam ?? 40; @endphp
<span class="avatar-usuario {{ $clase ?? '' }}" style="width: {{ $tam }}px; height: {{ $tam }}px; font-size: {{ round($tam * .38) }}px;">
    @if($u->foto_url)
        <img src="{{ $u->foto_url }}" alt="Foto de {{ $u->nombre }}">
    @else
        {{ $u->iniciales }}
    @endif
</span>
