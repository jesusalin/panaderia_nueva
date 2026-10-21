# Cambios faltantes — Kardex y Gestión de Reportes (enfoque Almacenero)

Resumen de lo que ya está hecho y de lo que todavía queda pendiente, para no
perder el hilo si se retoma después. Este archivo reúne **dos vueltas de trabajo**
(la primera y la segunda, que se fusionaron).

## ✅ Vuelta 1 (ya estaba en tu ZIP)

1. **Accesos directos al Kardex** — botón "Ver Kardex" en los listados de
   Materia Prima y Productos.
2. **Rango de fechas automático** — el Kardex abre con los últimos 30 días.
3. **Aviso de stock bajo en el Kardex** (pantalla y PDF).
4. **"Resumen de Reportes"** (`/reportes`) como primer ítem del menú REPORTES,
   con una cifra clave por tarjeta.

## ✅ Vuelta 2 (esta entrega)

### Kardex (`Reportes → Kardex`)
- 🐞 **Bug corregido:** los **ajustes de inventario y conteos físicos** salían en
  blanco (ni entrada ni salida) y **no sumaban en los totales**. Ahora la
  entrada/salida se calcula con la variación real del stock
  (`stock_después − stock_antes`), así que todo movimiento queda contado.
- **Saldo inicial** del periodo (fila propia en la tabla, PDF y Excel).
- **Verificación "el kardex cuadra"**: saldo inicial + entradas − salidas = saldo
  final. Si no cuadra, avisa y sugiere un conteo físico (útil para el OE2).
- **Valor del saldo en S/** (stock × costo) junto al saldo actual y el mínimo.
- **Desglose por motivo** (compra, producción, venta, merma, ajuste…).
- **Un solo selector** de ítem (insumos y productos juntos) que carga solo al
  elegir, y **periodos rápidos** (Hoy / 7 días / 30 días / Este mes / Todo) más
  fechas exactas opcionales.
- Atajo **"Ajustar stock"** desde el kardex de un insumo.
- Se mantiene compatible con los enlaces "Ver Kardex" de la vuelta 1
  (`tipo_item` / `id_item`).

### Reporte de Stock (`Reportes → Stock`)
- **Semáforo** por ítem: Agotado / Stock bajo / Normal.
- **Cantidad sugerida a reponer / producir** (la configurada o el doble del mínimo).
- Los ítems **críticos salen primero**; filtro "necesitan reposición" y "solo agotados".
- **Búsqueda automática** (filtra sola al dejar de escribir).
- Acciones directas por fila: ver kardex, ajustar stock, registrar compra,
  registrar producción (según los permisos del usuario).
- Botón **"Generar órdenes de reposición"** (llama a Órdenes Automáticas).
- El **Excel** ahora incluye el estado detallado y la columna "Cantidad a reponer".

### Dashboard — Panel del almacén (solo usuarios con módulo Inventario)
- Los **6 ítems más críticos** con barra de nivel, y accesos directos a Conteo
  físico, Registrar compra, Reporte de stock y Kardex.

### Automático
- Nuevo comando `php artisan inventario:revisar-stock`, **programado todos los
  días a las 6:30 a.m.**: deja creadas las órdenes de reposición de los insumos
  con stock bajo (sin duplicar las pendientes). El almacenero las encuentra
  listas en *Órdenes Automáticas*.

## 🔲 Lo que falta

1. **Activar el scheduler en el servidor.** El comando automático y el respaldo
   diario solo corren si Laravel tiene quien lo dispare. En XAMPP/local no corre
   solo: usar `php artisan schedule:work` (ventana abierta) para probarlo; en
   hosting hace falta un cron cada minuto:
   `* * * * * cd /ruta/proyecto && php artisan schedule:run >> /dev/null 2>&1`
2. **Envío por correo** (alerta de stock bajo / reporte semanal de stock): el
   sistema ya detecta y genera, pero no envía correos. Requiere configurar un
   `MAIL_MAILER` real (hoy el `.env` usa `log`).
3. **Selector de ítem con buscador en vivo** (Select2 / Choices.js). Con muchos
   insumos el desplegable agrupado se hace largo.
4. **Gráfico de evolución del saldo** en el Kardex (Chart.js ya está en el proyecto).
5. **Enlace al Conteo Físico desde el Kardex** cuando un ítem esté en stock bajo
   o el kardex no cuadre ("Registrar conteo de este insumo").
6. **Unificar los listados generales** (`kardex.index` = Movimientos de Productos,
   `movimientos.index` = Movimientos de Materia Prima) con *Reportes → Kardex*:
   hoy son dos caminos para ver cosas parecidas. Falta decidir si se consolidan.
7. **PDF de Stock**: mostrar también "Agotado" y la columna "Reponer" (hoy el PDF
   conserva su columna Estado original; la pantalla y el Excel ya la tienen).
8. **Prueba automática del kardex** (que saldo inicial + entradas − salidas =
   saldo final, incluyendo ajustes), al estilo de `tests/Unit/ConteoFisicoExactitudTest.php`.
9. **Pre-test / Post-test** (pedido del jurado): documentar en la metodología
   (Cap. III) cómo se midieron los 28 + 28 registros. No es código.

## 🧪 Cómo probar esta entrega (5 minutos)

1. `php artisan serve`, entrar como `almacenero` / `almacenero123`.
2. **Dashboard** → ver el "Panel del almacén".
3. **Reportes → Stock** → ver semáforo, "Reponer ≈" y exportar Excel/PDF.
4. **Reportes → Kardex** → elegir un insumo con un ajuste o conteo físico y
   comprobar que el ajuste aparece como entrada/salida y que dice "El kardex cuadra".
5. `php artisan inventario:revisar-stock` → debe crear órdenes (o decir que no hay).

> Nota: los cambios se validaron por sintaxis (PHP/Blade) pero no se ejecutaron
> contra tu base de datos; si algo falla al probar, pásame el mensaje exacto.

## Archivos tocados en la vuelta 2

- `app/Http/Controllers/ReportesController.php` — kardex normalizado, periodos,
  stock con semáforo (se conserva el `index()` del Resumen de Reportes)
- `app/Http/Controllers/DashboardController.php` — datos del panel del almacén
- `app/Exports/KardexExport.php` — saldo inicial y ajustes
- `app/Exports/StockExport.php` — estado y cantidad a reponer
- `app/Console/Commands/RevisarStockBajo.php` — **nuevo**
- `routes/console.php` — programación diaria del comando
- `resources/views/reportes/kardex.blade.php`
- `resources/views/reportes/kardex-pdf.blade.php`
- `resources/views/reportes/stock.blade.php`
- `resources/views/dashboard/index.blade.php`
- `CAMBIOS-FALTANTES.md`
