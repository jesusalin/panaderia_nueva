/**
 * Utilidad genérica para que las pestañas y la paginación de una lista
 * se actualicen sin recargar la página completa.
 *
 * Requiere en el backend: si la petición viene por AJAX (header
 * X-Requested-With), el controlador debe devolver solo el fragmento
 * de la lista (una vista "_lista...blade.php" sin el layout).
 *
 * Uso:
 *   initAjaxLista({
 *     contenedor: 'miContainer',  // id del <div> que se reemplaza
 *     tabs: 'misTabs',            // id del contenedor de pestañas (opcional)
 *     tabClase: 'oa-tab'          // clase de cada pestaña (default 'oa-tab')
 *   });
 */
function initAjaxLista(opts) {
    const cont = document.getElementById(opts.contenedor);
    if (!cont) return;
    const tabClase = opts.tabClase || 'oa-tab';

    function cargar(url, marcarTabs) {
        cont.classList.add('cargando');
        fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(res => {
                if (!res.ok) throw new Error('Respuesta no válida');
                return res.text();
            })
            .then(html => {
                cont.innerHTML = html;
                cont.classList.remove('cargando');
                history.pushState({ ajaxLista: url }, '', url);
                if (marcarTabs && opts.tabs) marcarPestañaActiva(url);
                cont.scrollIntoView({ behavior: 'smooth', block: 'start' });
            })
            .catch(() => { window.location.href = url; });
    }

    function marcarPestañaActiva(url) {
        const tabsEl = document.getElementById(opts.tabs);
        if (!tabsEl) return;
        const params = new URL(url, window.location.origin).searchParams;
        tabsEl.querySelectorAll('.' + tabClase).forEach(tab => {
            const tabParams = new URL(tab.href, window.location.origin).searchParams;
            const claves = [...tabParams.keys()].filter(k => k !== 'page');
            let coincide = claves.length === 0
                ? [...params.keys()].filter(k => k !== 'page').length === 0
                : claves.every(k => (tabParams.get(k) || '') === (params.get(k) || ''));
            tab.classList.toggle('active', coincide);
        });
    }

    if (opts.tabs) {
        const tabsEl = document.getElementById(opts.tabs);
        if (tabsEl) {
            tabsEl.addEventListener('click', function (e) {
                const link = e.target.closest('.' + tabClase);
                if (!link) return;
                e.preventDefault();
                cargar(link.href, true);
            });
        }
    }

    // Paginación (se regenera dentro del contenedor en cada carga, por eso delegamos)
    cont.addEventListener('click', function (e) {
        const link = e.target.closest('a.pg-link');
        if (!link) return;
        e.preventDefault();
        cargar(link.href, true);
    });

    window.addEventListener('popstate', function () {
        cargar(window.location.href, true);
    });
}
