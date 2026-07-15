/**
 * TiempoOperacion
 * Cronometra operaciones del usuario (búsqueda de producto, verificación de
 * stock, registro de venta) y las envía al servidor para el reporte OE3
 * (reducción de tiempos muertos) de la tesis.
 *
 * Usa sessionStorage para que el cronómetro sobreviva a recargas de página,
 * ya que el sistema no usa SPA/AJAX en estas pantallas.
 */
window.TiempoOperacion = {
    _key(tipo) {
        return 'op_inicio_' + tipo;
    },

    // Marca el inicio de una operación (ej. cuando el usuario abre un filtro o empieza a buscar)
    marcarInicio(tipo) {
        // No sobrescribir un cronómetro ya iniciado para el mismo tipo
        if (!sessionStorage.getItem(this._key(tipo))) {
            sessionStorage.setItem(this._key(tipo), Date.now().toString());
        }
    },

    // Fuerza el inicio (reinicia el cronómetro aunque ya exista uno)
    reiniciar(tipo) {
        sessionStorage.setItem(this._key(tipo), Date.now().toString());
    },

    // Calcula la duración y la envía al servidor
    registrarFin(tipo, referenciaId = null) {
        const key = this._key(tipo);
        const inicio = sessionStorage.getItem(key);
        if (!inicio) return;
        sessionStorage.removeItem(key);

        const duracionMs = Date.now() - parseInt(inicio, 10);
        // Descarta duraciones inválidas (negativas o mayores a 10 minutos, probablemente abandono de la tarea)
        if (duracionMs <= 0 || duracionMs > 600000) return;

        const token = document.querySelector('meta[name="csrf-token"]');
        fetch('/tiempos-operacion/registrar', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': token ? token.content : '',
                'Accept': 'application/json',
            },
            body: JSON.stringify({
                tipo_operacion: tipo,
                duracion_ms: duracionMs,
                referencia_id: referenciaId,
            }),
            keepalive: true, // permite que la petición termine aunque la página navegue
        }).catch(() => {});
    },
};
