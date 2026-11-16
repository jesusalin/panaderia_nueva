{{--
    Tablas interactivas: cualquier <table data-tabla> del sistema se puede ORDENAR tocando el título
    de una columna (▲ de menor a mayor, ▼ de mayor a menor, un tercer toque vuelve al orden original)
    y FILTRAR con el buscador y con un desplegable por cada columna marcada con <th data-filtro>.

    Opciones en la tabla:
      data-tabla="kardex"       activa la tabla (el nombre es opcional)
      data-exportar=".js-exp"   enlaces de PDF/Excel que deben salir con el mismo orden y filtros
                                 (el servidor los aplica con App\Support\TablaFiltro)
    En los <th>:
      data-clave="fecha"        nombre de la columna para el PDF/Excel
      data-filtro               agrega un desplegable con los valores de esa columna
      data-no-ordenar           la columna no se ordena (por ejemplo, la de botones)
    En las celdas:
      data-v="123.5"            valor para ordenar (si es distinto de lo que se ve)
      data-f="Entrada"          valor para el filtro (si es distinto de lo que se ve)
    En las filas:
      data-fijo                 la fila se queda arriba y no se filtra (por ejemplo, "Saldo inicial")
    En el pie (tfoot):
      data-tb-cuenta="insumo|insumos"   muestra cuántas filas se ven
      data-tb-suma="6"                  suma la columna 6 (empezando en 0) de las filas que se ven, en soles
--}}
<style>
    .tb-barra { display: flex; flex-wrap: wrap; gap: .45rem; align-items: center; margin: 0 0 .6rem; }
    .tb-barra .tb-buscar { position: relative; flex: 1 1 200px; max-width: 320px; }
    .tb-barra .tb-buscar i { position: absolute; left: .7rem; top: 50%; transform: translateY(-50%); color: var(--m-muted-2); font-size: .8rem; pointer-events: none; }
    .tb-barra .tb-buscar input { padding-left: 2rem; height: 34px; border-radius: 10px; font-size: .85rem; border: 1.5px solid var(--m-border); }
    .tb-barra select { height: 34px; border-radius: 10px; font-size: .82rem; border: 1.5px solid var(--m-border); width: auto; max-width: 210px; padding: .2rem .5rem; }
    .tb-barra select.activo, .tb-barra .tb-buscar input.activo { border-color: rgba(181,69,27,.55); background-color: rgba(181,69,27,.05); }
    .tb-barra .tb-info { font-size: .78rem; color: var(--m-muted); margin-left: auto; display: flex; align-items: center; gap: .5rem; flex-wrap: wrap; }
    .tb-barra .tb-limpiar { font-size: .78rem; font-weight: 700; color: var(--m-brand-text); background: none; border: none; padding: 0; }
    .tb-barra .tb-limpiar:hover { text-decoration: underline; }
    .tb-barra .tb-exp { font-size: .74rem; background: rgba(33,112,163,.1); color: var(--m-info-text); border-radius: 20px; padding: .15rem .6rem; font-weight: 700; }
    body.dark-mode .tb-barra .tb-buscar input, body.dark-mode .tb-barra select { background: #24243b; border-color: #33334d; color: #e4e4ef; }
    body.dark-mode .tb-barra .tb-exp { background: rgba(110,180,230,.15); color: #8cc8f0; }

    table[data-tabla] th.tb-ord { cursor: pointer; user-select: none; white-space: nowrap; transition: color .12s; }
    table[data-tabla] th.tb-ord:hover { color: var(--m-brand-text) !important; }
    table[data-tabla] th.tb-ord:focus-visible { outline: 2px solid #b5451b; outline-offset: -2px; }
    table[data-tabla] th .tb-flecha { display: inline-block; margin-left: .3rem; font-size: .68em; opacity: .3; transition: opacity .12s; }
    table[data-tabla] th.tb-ord:hover .tb-flecha { opacity: .7; }
    table[data-tabla] th.ord-asc, table[data-tabla] th.ord-desc { color: var(--m-brand-text) !important; }
    table[data-tabla] th.ord-asc .tb-flecha, table[data-tabla] th.ord-desc .tb-flecha { opacity: 1; }
    body.dark-mode table[data-tabla] th.ord-asc, body.dark-mode table[data-tabla] th.ord-desc { color: #ff9d6e !important; }
    table[data-tabla] tr.tb-oculta { display: none; }
    table[data-tabla] tr.tb-sin-resultados td { text-align: center; color: var(--m-muted); padding: 1.2rem; font-style: italic; }
    table[data-tabla] mark.tb-marca { background: rgba(243,156,18,.28); color: inherit; padding: 0 .1rem; border-radius: 3px; }
</style>

<script>
(function () {
    const normal = s => (s || '').toString().normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase().trim();
    const RE_FECHA = /^(\d{1,2})\/(\d{1,2})\/(\d{2,4})(?:\s+(\d{1,2}):(\d{2}))?/;
    const RE_NUM = /^[^\d\-−]{0,4}[+\-−]?\s*[\d.,]+\s*[a-zA-Záéíóú%.]{0,8}$/;

    function textoCelda(td) { return td ? (td.dataset.v ?? td.innerText ?? '').trim() : ''; }
    function filtroCelda(td) { return td ? (td.dataset.f ?? td.innerText ?? '').trim() : ''; }

    function aFecha(t) {
        const m = t.match(RE_FECHA);
        if (!m) return null;
        const anio = m[3].length === 2 ? 2000 + +m[3] : +m[3];
        return new Date(anio, m[2] - 1, m[1], m[4] || 0, m[5] || 0).getTime();
    }
    function aNumero(t) {
        const neg = /[\-−]/.test(t);
        const n = parseFloat(t.replace(/[^\d.]/g, ''));
        return isNaN(n) ? null : (neg ? -n : n);
    }

    function iniciar(tabla) {
        if (tabla.dataset.tbListo) return;
        const cabecera = tabla.tHead && tabla.tHead.rows[0];
        const cuerpo = tabla.tBodies[0];
        if (!cabecera || !cuerpo) return;
        tabla.dataset.tbListo = '1';

        const ths = [...cabecera.cells];
        const esVacia = r => r.cells.length === 1 && r.cells[0].colSpan > 1;
        const fijas = [...cuerpo.rows].filter(r => r.hasAttribute('data-fijo'));
        const filas = [...cuerpo.rows].filter(r => !r.hasAttribute('data-fijo') && !esVacia(r));
        if (filas.length < 2) return;
        filas.forEach((r, i) => r.dataset.tbPos = i);

        // Tipo de cada columna (número, fecha o texto), según sus celdas
        const tipos = ths.map((th, i) => {
            if (th.dataset.tipo) return th.dataset.tipo;
            const vals = filas.map(r => textoCelda(r.cells[i])).filter(v => v && v !== '—' && v !== '-');
            if (!vals.length) return 'texto';
            if (vals.every(v => RE_FECHA.test(v))) return 'fecha';
            if (vals.every(v => RE_NUM.test(v) || /^\d+(\.\d+)?$/.test(v))) return 'num';
            return 'texto';
        });
        const valor = (r, i) => {
            const t = textoCelda(r.cells[i]);
            if (tipos[i] === 'fecha') return aFecha(t) ?? (r.cells[i]?.dataset.v ? +r.cells[i].dataset.v : -Infinity);
            if (tipos[i] === 'num') { const n = aNumero(t); return n === null ? -Infinity : n; }
            return normal(t);
        };

        const estado = { col: null, dir: null, q: '', f: {} };

        // ── Barra: buscador, filtros por columna, contador ──
        const barra = document.createElement('div');
        barra.className = 'tb-barra';
        const conBuscador = filas.length >= 6 || tabla.hasAttribute('data-buscar');
        let inBuscar = null;
        if (conBuscador) {
            const caja = document.createElement('div');
            caja.className = 'tb-buscar';
            caja.innerHTML = '<i class="fas fa-search"></i><input type="search" class="form-control" placeholder="Buscar en la tabla…" aria-label="Buscar en la tabla">';
            inBuscar = caja.querySelector('input');
            inBuscar.addEventListener('input', () => { estado.q = inBuscar.value; aplicar(); });
            barra.appendChild(caja);
        }
        const selects = {};
        ths.forEach((th, i) => {
            if (!th.hasAttribute('data-filtro')) return;
            const conteo = new Map();
            filas.forEach(r => { const v = filtroCelda(r.cells[i]); if (v && v !== '—') conteo.set(v, (conteo.get(v) || 0) + 1); });
            if (conteo.size < 2) return;
            const titulo = th.textContent.trim();
            const sel = document.createElement('select');
            sel.className = 'form-control';
            sel.setAttribute('aria-label', 'Filtrar por ' + titulo);
            sel.innerHTML = '<option value="">' + titulo + ': todos</option>'
                + [...conteo.entries()].sort((a, b) => normal(a[0]).localeCompare(normal(b[0])))
                    .map(([v, n]) => '<option value="' + v.replace(/"/g, '&quot;') + '">' + v.replace(/</g, '&lt;') + ' (' + n + ')</option>').join('');
            sel.addEventListener('change', () => { estado.f[i] = sel.value; sel.classList.toggle('activo', !!sel.value); aplicar(); });
            selects[i] = sel;
            barra.appendChild(sel);
        });
        const info = document.createElement('div');
        info.className = 'tb-info';
        barra.appendChild(info);

        const exportar = tabla.dataset.exportar ? [...document.querySelectorAll(tabla.dataset.exportar)] : [];

        // Dónde va la barra: justo encima de la caja que contiene la tabla
        let host = tabla;
        while (host.parentElement && host.parentElement.children.length === 1
               && !host.parentElement.matches('body, .content, .content-wrapper, .container-fluid, section, details')) host = host.parentElement;
        host.parentElement.insertBefore(barra, host);

        // ── Encabezados que ordenan ──
        ths.forEach((th, i) => {
            if (th.hasAttribute('data-no-ordenar') || !th.textContent.trim()) return;
            th.classList.add('tb-ord');
            th.tabIndex = 0;
            th.title = 'Ordenar por ' + th.textContent.trim().toLowerCase();
            th.setAttribute('aria-sort', 'none');
            const flecha = document.createElement('i');
            flecha.className = 'fas fa-sort tb-flecha';
            th.appendChild(flecha);
            const clic = () => {
                if (estado.col !== i) { estado.col = i; estado.dir = 'asc'; }
                else if (estado.dir === 'asc') estado.dir = 'desc';
                else { estado.col = null; estado.dir = null; }
                aplicar();
            };
            th.addEventListener('click', clic);
            th.addEventListener('keydown', e => { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); clic(); } });
        });

        const vacia = document.createElement('tr');
        vacia.className = 'tb-sin-resultados';
        vacia.innerHTML = '<td colspan="' + ths.length + '">Ninguna fila coincide con el filtro.</td>';

        function aplicar(sinUrl) {
            const q = normal(estado.q);
            let visibles = 0;
            filas.forEach(r => {
                let ok = !q || normal(r.innerText).includes(q);
                if (ok) for (const [i, v] of Object.entries(estado.f)) {
                    if (v && filtroCelda(r.cells[i]) !== v) { ok = false; break; }
                }
                r.classList.toggle('tb-oculta', !ok);
                if (ok) visibles++;
            });

            const orden = [...filas];
            if (estado.col !== null) {
                const i = estado.col, signo = estado.dir === 'desc' ? -1 : 1;
                orden.sort((a, b) => {
                    const x = valor(a, i), y = valor(b, i);
                    const r = typeof x === 'string' ? x.localeCompare(y, 'es', { numeric: true }) : (x - y);
                    return (r || (a.dataset.tbPos - b.dataset.tbPos)) * signo;
                });
            } else {
                orden.sort((a, b) => a.dataset.tbPos - b.dataset.tbPos);
            }
            fijas.forEach(r => cuerpo.appendChild(r));
            orden.forEach(r => cuerpo.appendChild(r));
            if (!visibles) cuerpo.appendChild(vacia); else vacia.remove();

            ths.forEach((th, i) => {
                const f = th.querySelector('.tb-flecha');
                if (!f) return;
                const activo = estado.col === i;
                th.classList.toggle('ord-asc', activo && estado.dir === 'asc');
                th.classList.toggle('ord-desc', activo && estado.dir === 'desc');
                th.setAttribute('aria-sort', activo ? (estado.dir === 'asc' ? 'ascending' : 'descending') : 'none');
                f.className = 'fas tb-flecha ' + (activo ? (estado.dir === 'asc' ? 'fa-sort-up' : 'fa-sort-down') : 'fa-sort');
            });
            if (inBuscar) inBuscar.classList.toggle('activo', !!estado.q.trim());

            // Pie: cuántas filas se ven y sumas
            const vistas = filas.filter(r => !r.classList.contains('tb-oculta'));
            tabla.querySelectorAll('[data-tb-cuenta]').forEach(el => {
                const [uno, varios] = el.dataset.tbCuenta.split('|');
                el.textContent = vistas.length + ' ' + (vistas.length === 1 ? uno : (varios || uno));
            });
            tabla.querySelectorAll('[data-tb-suma]').forEach(el => {
                const i = +el.dataset.tbSuma;
                const s = vistas.reduce((t, r) => t + (aNumero(textoCelda(r.cells[i])) || 0), 0);
                el.textContent = 'S/ ' + s.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            });

            const filtrado = !!estado.q.trim() || Object.values(estado.f).some(Boolean);
            info.innerHTML = (filtrado ? '<span>Se ven <b>' + visibles + '</b> de ' + filas.length + '</span>' : '')
                + (filtrado || estado.col !== null ? '<button type="button" class="tb-limpiar"><i class="fas fa-undo mr-1"></i>Quitar orden y filtros</button>' : '')
                + (exportar.length && (filtrado || estado.col !== null) ? '<span class="tb-exp" title="El PDF y el Excel se descargan con el mismo orden y los mismos filtros que ves aquí"><i class="fas fa-file-export mr-1"></i>PDF y Excel salen así</span>' : '');
            const limpiar = info.querySelector('.tb-limpiar');
            if (limpiar) limpiar.addEventListener('click', () => {
                estado.col = estado.dir = null; estado.q = ''; estado.f = {};
                if (inBuscar) inBuscar.value = '';
                Object.values(selects).forEach(s => { s.value = ''; s.classList.remove('activo'); });
                aplicar();
            });

            sincronizar(sinUrl);
        }

        // ── Los enlaces de PDF/Excel (y la dirección de la página) llevan el mismo orden y filtros ──
        const clave = i => ths[i].dataset.clave || String(i);
        function parametros(url) {
            [...url.searchParams.keys()].filter(k => k.startsWith('t_')).forEach(k => url.searchParams.delete(k));
            if (estado.col !== null) { url.searchParams.set('t_ord', clave(estado.col)); url.searchParams.set('t_dir', estado.dir); }
            if (estado.q.trim()) url.searchParams.set('t_q', estado.q.trim());
            for (const [i, v] of Object.entries(estado.f)) if (v) url.searchParams.set('t_f[' + clave(i) + ']', v);
            return url;
        }
        function sincronizar(sinUrl) {
            if (!exportar.length) return;
            exportar.forEach(a => { a.href = parametros(new URL(a.href, location.href)).toString(); });
            if (!sinUrl) history.replaceState(null, '', parametros(new URL(location.href)).toString());
        }

        // Estado inicial desde la dirección (al volver a la página o compartir el enlace)
        if (exportar.length) {
            const p = new URLSearchParams(location.search);
            const idx = k => ths.findIndex((th, i) => clave(i) === k);
            const io = idx(p.get('t_ord'));
            if (io >= 0 && ths[io].classList.contains('tb-ord')) { estado.col = io; estado.dir = p.get('t_dir') === 'desc' ? 'desc' : 'asc'; }
            if (p.get('t_q')) { estado.q = p.get('t_q'); if (inBuscar) inBuscar.value = estado.q; }
            for (const [k, v] of p.entries()) {
                const m = k.match(/^t_f\[(.+)\]$/);
                const i = m ? idx(m[1]) : -1;
                if (i >= 0 && selects[i]) { estado.f[i] = v; selects[i].value = v; selects[i].classList.toggle('activo', !!v); }
            }
        }
        aplicar(true);
    }

    function iniciarTodas(raiz) { (raiz || document).querySelectorAll('table[data-tabla]').forEach(iniciar); }
    window.tablaInteractiva = iniciarTodas;
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', () => iniciarTodas());
    else iniciarTodas();
})();
</script>
