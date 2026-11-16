{{--
    Oculta los botones y formularios de las acciones que el admin le quitó a este usuario
    (Usuario::ACCIONES). Es solo para que no vea botones que no puede usar: el bloqueo real lo
    hace el middleware RevisarAccion en el servidor.
    - Enlaces (<a href>) a esas rutas: se quitan.
    - Formularios que envían a esas rutas: se quitan; si son un interruptor (activar/desactivar),
      se dejan visibles pero deshabilitados.
    - Botones que abren un modal cuyo formulario se quitó: también se quitan.
    - Cualquier elemento con data-accion="pantalla.accion" quitada: se quita.
--}}
@auth
@php
    $quitadas = auth()->user()->accionesQuitadas();
    $bloqueos = [];
    if ($quitadas) {
        foreach (\App\Models\Usuario::rutasConAccion() as $nombre => $accion) {
            if (!in_array($accion, $quitadas, true)) continue;
            $r = \Illuminate\Support\Facades\Route::getRoutes()->getByName($nombre);
            if (!$r) continue;
            $uri = '/' . ltrim($r->uri(), '/');
            $re = '^' . preg_replace('/\\\\\{[^}]+\\\\\}/', '[^/]+', preg_quote($uri, '/')) . '/?$';
            $bloqueos[] = ['re' => $re, 'm' => array_values(array_diff($r->methods(), ['HEAD'])), 'interruptor' => str_ends_with($nombre, 'toggle-estado')];
        }
    }
@endphp
@if($quitadas)
<script>
(function () {
    const BLOQUEOS = @json($bloqueos).map(b => ({ re: new RegExp(b.re.replace(/\\\//g, '/')), m: b.m, interruptor: b.interruptor }));
    const ACCIONES = @json($quitadas);
    const base = @json(rtrim(parse_url(url('/'), PHP_URL_PATH) ?? '', '/'));
    function ruta(url) {
        try { const u = new URL(url, location.href); if (u.origin !== location.origin) return null; return (base && u.pathname.startsWith(base) ? u.pathname.slice(base.length) : u.pathname) || '/'; }
        catch (e) { return null; }
    }
    const bloqueo = (url, metodo) => { const p = ruta(url); return p === null ? null : BLOQUEOS.find(b => b.re.test(p) && b.m.includes(metodo)) || null; };
    const bloqueada = (url, metodo) => !!bloqueo(url, metodo);
    const MOTIVO = 'No tienes permiso para esta acción';

    function limpiar(raiz) {
        const modales = new Set();
        raiz.querySelectorAll('a[href]').forEach(a => {
            if (bloqueada(a.getAttribute('href'), 'GET')) a.remove();
        });
        raiz.querySelectorAll('form[action]').forEach(f => {
            const metodo = (f.querySelector('input[name="_method"]')?.value || f.getAttribute('method') || 'GET').toUpperCase();
            const b = bloqueo(f.getAttribute('action'), metodo);
            if (!b) return;
            // Interruptores de activo/inactivo: se siguen viendo (muestran el estado) pero no se pueden tocar
            if (b.interruptor || (f.querySelector('input[type=checkbox]') && !f.classList.contains('modal-content'))) {
                f.querySelectorAll('input, button, select').forEach(el => { el.disabled = true; });
                f.title = MOTIVO; f.style.opacity = '.6'; f.onsubmit = () => false;
                return;
            }
            const modal = f.closest('.modal');
            if (modal && modal.id) modales.add(modal.id);
            f.remove();
        });
        modales.forEach(id => document.querySelectorAll('[data-target="#' + id + '"], [href="#' + id + '"]').forEach(b => b.remove()));
        ACCIONES.forEach(a => raiz.querySelectorAll('[data-accion="' + a + '"]').forEach(el => el.remove()));
        // Grupos de botones que quedaron vacíos
        raiz.querySelectorAll('.btn-group, .item-card-actions, .card-actions, .lr-acciones').forEach(g => { if (!g.children.length) g.remove(); });
    }
    limpiar(document);
    // Contenido que llega después (detalles en modales, listas que se recargan)
    new MutationObserver(cambios => cambios.forEach(c => c.addedNodes.forEach(n => { if (n.nodeType === 1) limpiar(n.parentNode || n); })))
        .observe(document.body, { childList: true, subtree: true });
})();
</script>
@endif
@endauth
