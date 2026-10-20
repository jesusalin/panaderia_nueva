# Cambios faltantes — Kardex y Gestión de Reportes (enfoque Almacenero)

Este documento resume lo que se mejoró en esta vuelta y lo que todavía queda
pendiente, para que no se pierda el hilo si se retoma después.

## ✅ Lo que se hizo en esta vuelta

1. **Accesos directos al Kardex** — en los listados de **Materia Prima** y
   **Productos**, cada fila ahora tiene un botón "Ver Kardex" que lleva
   directo a la ficha de ese ítem ya filtrada (antes había que entrar a
   Reportes → Kardex y volver a buscar el ítem en el desplegable).
2. **Rango de fechas automático** — al abrir el Kardex de un ítem sin
   especificar fechas, el sistema muestra automáticamente los **últimos 30
   días** en vez de dejar la pantalla vacía o cargar todo el historial de
   una. Incluye un enlace "Ver historial completo" para cuando sí se necesite
   todo.
3. **Aviso de stock bajo en el Kardex** — si el ítem que se está revisando
   está en stock bajo (≤ stock mínimo), se muestra una alerta directamente en
   la ficha y en el PDF exportado, para que salte a la vista sin tener que
   cruzarlo con el reporte de Stock.
4. **Nuevo "Resumen de Reportes"** (`/reportes`) — página de entrada que
   reúne en un solo lugar, con una cifra clave visible en cada tarjeta:
   - Reporte de Stock (cuántos ítems están en stock bajo ahora mismo)
   - Kardex (movimientos registrados hoy)
   - Tiempos por Operación (OE3)
   - Conteo Físico (exactitud del último conteo, si existe)
   - Historial de Ventas → Excel (solo si el usuario tiene el módulo Ventas)

   Se agregó como primer ítem del menú "REPORTES" en el sidebar
   ("Resumen de Reportes").

## 🔲 Lo que falta (no se alcanzó a hacer esta vuelta)

1. **Unificar el Kardex "ficha por ítem" con los listados existentes.**
   Hoy conviven dos cosas distintas y no se tocaron para no romper nada:
   - `Reportes → Kardex` (`reportes.kardex`): ficha de UN ítem con saldo
     corrido — esta es la que se mejoró hoy.
   - `kardex.index` y `movimientos.index`: listados generales de
     movimientos de **productos** y de **materia prima** por separado (con
     sus propios filtros y stats), que NO recibieron las mismas mejoras
     (rango de 30 días automático, aviso de stock bajo).

   Recomendación: decidir si estas dos vistas generales siguen o si todo se
   consolida dentro de "Reportes", para no mantener dos caminos distintos
   para ver lo mismo.

2. **Selector de ítem más rápido en el Kardex.** El desplegable de insumos y
   productos es una lista plana; si el catálogo crece, buscar un ítem a mano
   se vuelve lento. Falta agregarle un buscador en vivo (por ejemplo con
   Choices.js o Select2) en vez del `<select>` normal.

3. **Reportes automáticos de verdad (programados).** "Automático" por ahora
   significa que el sistema pre-completa fechas y avisos por sí solo al
   entrar — pero no hay nada que se genere y envíe solo (por ejemplo, un
   correo semanal con el reporte de stock bajo, o un PDF de cierre de mes
   generado por el programador de tareas de Laravel). Si la idea de
   "automático" apuntaba a esto, es una tarea aparte: requeriría configurar
   el *scheduler* de Laravel y, si se quiere enviar por correo, un driver de
   mail real (hoy el `.env.example` trae `MAIL_MAILER` de prueba).

4. **Gráfico de evolución del Kardex.** Hoy la ficha es solo tabla. Un
   gráfico de línea con el saldo a través del tiempo (ya hay Chart.js
   cargado en el proyecto, se usa en Tiempos de Operación) ayudaría a leer
   de un vistazo si un insumo tiende a agotarse rápido.

5. **Conteo Físico no quedó enlazado desde el Kardex.** Sería útil que, si
   un ítem muestra "stock bajo" o una exactitud mala en su último conteo, el
   Kardex tenga un enlace directo a "Registrar conteo de este insumo" en vez
   de solo mostrar la alerta.

## Archivos tocados en esta vuelta

- `app/Http/Controllers/ReportesController.php` (nuevo método `index()`,
  fechas automáticas y aviso de stock bajo en `datosKardex()`)
- `routes/web.php` (ruta `reportes.index`)
- `resources/views/reportes/index.blade.php` (nuevo)
- `resources/views/reportes/kardex.blade.php` (rango automático + aviso stock bajo)
- `resources/views/reportes/kardex-pdf.blade.php` (rango real + aviso stock bajo)
- `resources/views/materia_prima/index.blade.php` (botón "Ver Kardex")
- `resources/views/productos/index.blade.php` (botón "Ver Kardex")
- `resources/views/layouts/app.blade.php` (ítem "Resumen de Reportes" en el menú)
