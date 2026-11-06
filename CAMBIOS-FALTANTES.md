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

## ✅ Vuelta 3 (pendientes de la vuelta 2 que ya se cerraron)

- **Buscador en vivo en el Kardex**: se escribe parte del nombre del insumo o
  producto y se elige del listado; carga solo (sin librerías externas).
- **Gráfico de evolución del saldo** en el Kardex (Chart.js) con la línea
  punteada del stock mínimo (aparece con 2 o más movimientos).
- **Enlace al Conteo Físico** desde el Kardex de un insumo cuando está en stock
  bajo o el kardex no cuadra (solo para usuarios con módulo Inventario).
- **PDF de Stock**: ahora distingue *Agotado* / *Stock bajo* / *Normal* y trae la
  columna *Reponer*.
- **Prueba automática del kardex** (`tests/Unit/KardexCalculoTest.php`, 8 pruebas):
  ajustes como entrada/salida, saldo inicial, "cuadra / no cuadra". Para esto el
  cálculo se movió a `app/Support/KardexCalculo.php` (clase sin base de datos).
- **Alerta de stock bajo por correo**: el comando diario `inventario:revisar-stock`
  envía un resumen si hay un correo configurado (ver "Activar el correo" abajo).
  Sin correo configurado no envía nada y todo lo demás funciona igual.

## ✅ Vuelta 4 (limpieza del sistema según la tesis nueva)

Los objetivos de la tesis pasaron a ser TPR, TCS, TCI y PER, medidos por observación.
Se quitó del sistema lo que ya no forma parte de la investigación:

- **Módulo Tiempos de Operación** completo (pantalla, PDF, cronómetros en Ventas,
  Kardex y Movimientos, controlador, modelos y su opción del menú).
- **Reporte de Rotación de Stock** y su tarjeta del Dashboard.
- **Indicadores del Dashboard** ligados a los objetivos viejos: Capacidad de
  Procesamiento, Rotación y Reducción de Tiempos Muertos. Se conserva *Exactitud del
  inventario* (viene del conteo físico).
- **Datos de ejemplo inventados** que el seeder insertaba (línea base manual de 45, 30 y
  90 segundos y mediciones "simuladas" del sistema). Ya no se crean.
- Referencias a "OE1–OE4" en textos del sistema.
- Las tablas `tiempos_operacion` y `tiempos_baseline` se eliminan con la migración
  `2025_01_01_000018_drop_tiempos_tables.php` (corre con `php artisan migrate`).

**Archivos a ELIMINAR a mano** (un ZIP no puede borrar archivos; ver también
`ARCHIVOS_A_ELIMINAR.txt`):
- `app/Http/Controllers/TiemposOperacionController.php`
- `app/Models/TiempoOperacion.php`
- `app/Models/TiempoBaseline.php`
- `public/js/tiempos-operacion.js`
- `resources/views/kardex/rotacion.blade.php`
- carpeta `resources/views/tiempos-operacion/`

## ✅ Vuelta 5 (menú ordenable)

- **"Ordenar menú"** (botón arriba del menú lateral): se arrastran y sueltan las
  secciones y los ítems dentro de cada sección, como una playlist. **Listo** guarda,
  **Cancelar** / Esc deshace, **↺** vuelve al orden original.
- **Solo el admin** ve "Ordenar menú" y el orden que guarda se aplica a **todos los
  usuarios** (cada uno ve solo sus módulos, en ese orden). Se guarda en la tabla nueva
  `configuraciones` (clave `orden_menu`), que también entra en los respaldos.
- En modo ordenar: casilla **"Solo títulos"** para mover solo las secciones; clic en un
  título abre/cierra esa sección. La barra Listo/Cancelar queda fija arriba.
- El sidebar completo hace scroll (el encabezado "Muruwasi" ya no queda fijo) y al
  arrastrar cerca del borde el menú baja/sube solo.
- Correr `php artisan migrate` (migraciones `..._000019_add_orden_menu_to_usuarios_table.php`
  y `..._000020_create_configuraciones_table.php`; la 000020 pasa el orden que el admin
  ya tuviera a la configuración general y elimina la columna `usuarios.orden_menu`).
- Archivos: `app/Http/Controllers/MenuController.php` (**nuevo**), `app/Models/Configuracion.php`
  (**nuevo**), `routes/web.php`, `app/Models/Usuario.php`, `resources/views/layouts/app.blade.php`,
  `tests/Feature/OrdenMenuTest.php` (**nuevo**, 7 pruebas).
- Para agregar un ítem nuevo al menú en el futuro: ponerle `data-menu-id="algo-unico"`
  al `<li>`; si es una sección nueva, además `data-menu-lista="sec-algo"` a su
  `<ul class="nav-section-body">`. Los ítems nuevos aparecen al final hasta que el
  usuario los reordene.

## ✅ Vuelta 6 (permisos por pantalla)

- Al crear/editar un usuario, el admin elige **pantalla por pantalla** dentro de cada
  módulo (p. ej. Inventario › Conteo Físico sí, Movimientos de Productos no). La casilla
  del módulo marca todo y queda "a medias" si solo hay algunas. **Plantillas rápidas**:
  Almacenero, Ventas / Caja, Marcar todo, Quitar todo.
- Las rutas, el menú y los botones de otras pantallas (dashboard, reportes, kardex,
  productos…) respetan cada pantalla: nadie ve un botón que lo lleve a "acceso denegado".
- Catálogo de pantallas en `Usuario::PERMISOS`. Claves: `inventario.materia_prima`,
  `inventario.movimientos`, `inventario.conteo`, `inventario.kardex`, `compras.proveedores`,
  `compras.compras`, `compras.ordenes`, `catalogo.productos`, `catalogo.categorias`,
  `produccion.produccion`, `produccion.recetas`, `reportes.resumen`, `reportes.stock`,
  `reportes.kardex`, `ventas`, `clientes`.
- `php artisan migrate` corre `..._000021_permisos_por_pantalla.php`: quien tenía un módulo
  completo pasa a tener todas sus pantallas (nadie pierde acceso).
- Seeder corregido: los permisos de ejemplo se asignan por nombre de usuario (antes,
  con ids fijos, quedaban cruzados). María ahora tiene también Catálogo › Productos para
  poder vender desde el carrito.
- Pruebas: `tests/Feature/PermisosPantallaTest.php` (8 pruebas).
- El formulario sigue la **misma estructura y orden del menú**: sección → ítem → pestañas.
  Un ítem con pestañas adentro (Productos: Productos / Producción / Recetas) tiene su
  casilla general y una casilla por pestaña. Para pestañas nuevas en el futuro, se
  agregan en `'pestanas'` del ítem en `Usuario::PERMISOS` y el formulario las muestra solo.

## ✅ Vuelta 7 (datos de ejemplo realistas y hora de Lima)

- **Seeder nuevo** (`database/seeders/DatabaseSeeder.php`): simula 14 días de operación
  (producción por receta, ventas, órdenes → compras → recepción, mermas, conteos físicos).
  Todo se registra como lo hace el sistema, por eso **el kardex de los 33 insumos y 13
  productos cuadra** (antes no cuadraba casi ninguno). Se buscan los usuarios por nombre.
- **Zona horaria `America/Lima`** (`config/app.php`, `APP_TIMEZONE` en `.env`). Antes estaba
  en UTC: todo lo registrado después de las 7 p.m. caía en el día siguiente, lo que
  descuadraba "Ventas de hoy" y el futuro cierre diario (TCI).
  Agregar `APP_TIMEZONE=America/Lima` al `.env` de cada instalación.

## ✅ Vuelta 8 (dashboard del almacenero)

- El dashboard ahora es primero del **almacenero** (el usuario de la tesis): accesos rápidos,
  **Hoy en el almacén** (entradas/salidas/ajustes del día), **PER** del último conteo con la
  fórmula de la tesis (insumos que no coinciden ÷ insumos contados × 100) y su tendencia,
  **Por recibir** (compras pendientes y órdenes automáticas) y **lo más urgente de reponer**
  con los días que alcanza cada insumo (consumo de los últimos 7 días).
- Se quitó el indicador "Exactitud del inventario" (promediaba otra cosa y mostraba un
  "Estimado" inventado si no había conteo) y el botón "Nueva venta" a la pantalla antigua.
- Ventas queda abajo y solo para quien tiene el módulo Ventas; gráfico con los 7 días
  completos; "Ganancia del mes" pasó a "Ventas − compras del mes".
- **Dashboard por bloques según permisos**: cada usuario ve los bloques de sus áreas, así
  sirve para cualquier rol que arme el admin. Accesos rápidos según sus pantallas (hasta 6);
  bloques Almacén, Compras (por recibir), **Producción** (producido hoy y qué toca producir) y
  Ventas (con **productos por agotarse** para quien vende). El admin ve todos los bloques.
  Probado con admin, almacenero, vendedor, producción, solo reportes y solo compras.
- Pendiente: cuando existan Registro rápido, Consultar stock y Cierre del día, agregarlos a
  los accesos rápidos y mostrar si el cierre del día ya se hizo.

## ✅ Vuelta 9 (historial de actividad, usuarios activos/inactivos y categorías)

**Ejecutar:** `php artisan migrate` (crea `actividad_usuarios` y agrega `icono`/`color` a `categorias`).
Para datos de ejemplo: `php artisan migrate:fresh --seed`.

**Historial de actividad** (Administración › Historial de actividad, solo admin)
- Se registra solo: inicios y cierres de sesión, intentos fallidos (contraseña mala,
  usuario que no existe, cuenta desactivada) y cada registro, edición o eliminación
  con una frase clara (ej. *Registró la venta V-000120 por S/ 12.50*, *Recibió la compra #23*).
- Si la acción falla (error de validación o mensaje de error) **no** se anota.
- Filtros por usuario, tipo de acción, módulo, fechas y texto; agrupado por día.
- Al filtrar un usuario se ve su ficha: último acceso, ingresos, acciones e intentos fallidos de 30 días.
- Aviso rojo si hubo intentos de ingreso fallidos en los últimos 7 días (con × para ocultarlo; vuelve a salir cada vez que se entra).
- El historial no está en el menú lateral: se abre desde Usuarios.

**Usuarios**
- Cada tarjeta muestra el estado de actividad: *En línea*, *Activo*, *Sin entrar hace N días* (7–29),
  *Sin actividad hace N días* (30+) o *Nunca ingresó*, con la fecha y hora exacta del último acceso
  y la última acción que hizo.
- Filtro "Actividad" y tarjeta "Sin uso 30+ días" (cuentas activas que no se usan).
- Sugerencia de desactivar a quien no entra hace 30+ días; botón para ver su historial.

**Categorías**
- El admin elige ícono y color (con vista previa); si no elige, se adivina por el nombre.
- Buscador y filtro por estado; cada tarjeta muestra productos activos y cuántos tienen stock bajo.
- "Ver productos": ficha con precio, costo, stock (semáforo) y estado de cada producto.
- Una categoría con productos no se elimina: se les cambia la categoría desde Editar de cada producto, o se desactiva.

**Seeder:** íconos y colores en las 4 categorías, historial de los 14 días simulados (con sus
inicios y cierres de sesión), 4 intentos fallidos de ejemplo y un usuario inactivo
(`jrojas` / `caja123`, Caja 2, sin entrar hace 41 días).

## ✅ Vuelta 10 (Mi perfil y contraseñas olvidadas)

**Ejecutar:** `php artisan migrate` (agrega `debe_cambiar_password` a `usuarios`).

**Mi perfil** (menú de arriba a la derecha → *Mi perfil*, para todos los usuarios)
- Subir, cambiar o quitar la foto de perfil (JPG/PNG/WEBP, hasta 2 MB). La foto sale en la barra
  de arriba, en Usuarios y al editar. No necesita `php artisan storage:link`.
- Cambiar nombre completo, apodo, correo (Gmail u otro) y su propio "usuario para entrar" (sin espacios y que nadie más lo use).
- Cambiar su propia contraseña (pide la actual).

**Contraseñas olvidadas** (las contraseñas se guardan cifradas sin vuelta atrás: nadie puede leerlas)
- El admin, en *Editar usuario*, pulsa **Generar temporal**: se muestra UNA vez (ej. `atxt-7443`) para dársela.
- Al entrar con ella, el sistema obliga a esa persona a elegir su propia contraseña antes de usar nada más.
- También puede escribir una contraseña a mano y marcar "Pedirle que elija su propia contraseña al entrar".
- Si el **admin** olvida la suya: en la carpeta del proyecto, `php artisan usuario:clave admin`.
- En Usuarios, una etiqueta "Clave temporal" indica quién todavía no la cambió.

**Usuarios:** se destacan el nombre completo y el apodo; el nombre de usuario queda como dato secundario.
El buscador también encuentra por apodo.

## ✅ Vuelta 11 (Productos)

No necesita migración.

- **El stock ya no se escribe a mano al editar** (eso descuadraba el kardex). Cambia solo con
  producción, ventas o el nuevo **Ajustar stock**: *merma o pérdida* (cuántas se perdieron) o
  *corrección por conteo* (cuántas contaste), con motivo obligatorio; todo queda en el kardex.
- Al crear un producto, su **stock inicial** queda registrado en el kardex.
- **Ficha del producto** (clic en la tarjeta): precio, costo, ganancia y %, stock y para cuántos días
  alcanza, ventas de los últimos 14 días, receta con el costo de cada insumo y costo por unidad
  según la receta, y los últimos 10 movimientos.
- En Editar, si tiene receta, muestra cuánto cuesta según la receta y un enlace "Usar ese costo".
- Listado: resumen (productos, stock bajo, agotados), filtro por stock, orden (más vendidos,
  más urgentes, mayor ganancia, precio), buscador también por descripción.
- Cada tarjeta muestra el ícono y color de su categoría, % de ganancia, unidades vendidas en 30 días
  y una barra de stock (verde / ámbar / rojo).

## ✅ Vuelta 12 (cantidades sin ceros de sobra)

No necesita migración. Las cantidades de insumos se muestran limpias: `25.000 kg` → `25 kg`,
`0.500 kg` → `0.5 kg`, `1250.750` → `1,250.75` (los precios en S/ siguen con 2 decimales).
- `app/Support/Cantidad.php` (`Cantidad::fmt()`) y `fmtCant()` en JavaScript (layout).
- Modelos con cantidades como número: MateriaPrima, MovimientoInventario, RecetaDetalle,
  CompraDetalle, OrdenAutomatica, ConteoFisico.
- Aplicado en materia prima, ajuste, movimientos, conteo físico, compras, órdenes automáticas,
  producción, recetas, reportes de stock y kardex (pantalla y PDF) y el correo de stock bajo.
- Los movimientos nuevos de conteo/ajuste ya escriben "(-0.8)" en vez de "(-0.800)"; los
  textos que ya estaban guardados antes no cambian.

## ✅ Vuelta 13 (unidad más cómoda: 0.5 L → 500 mL)

No necesita migración. Al mostrar una cantidad, el sistema pasa a la unidad más fácil de leer:
`0.5 L` → `500 mL`, `0.25 kg` → `250 g`, `1500 mL` → `1.5 L`, `2000 g` → `2 kg` (1.5 L se queda igual).
Solo cambia cómo se muestra; en la base de datos y en los formularios todo sigue en la unidad del insumo.
- `Cantidad::conUnidad()` y `fmtCantU()` (JavaScript).
- Aplicado en materia prima, ajuste, movimientos, conteo físico, compras, órdenes automáticas,
  producción, recetas, dashboard, reportes de stock y kardex (pantalla y PDF) y correo de stock bajo.

## ✅ Vuelta 14 (solo el admin cambia contraseñas)

No necesita migración. **Borrar** `app/Http/Middleware/ExigirCambioPassword.php` (ya no se usa).
- Los usuarios ya no pueden cambiar su contraseña: en *Mi perfil* ven un aviso de que la administra
  el administrador. Siguen pudiendo cambiar su foto, nombre, apodo, correo y usuario.
- El admin sí cambia la suya en *Mi perfil* (pidiendo la actual) y la de los demás en *Editar usuario*:
  escribiéndola o con **Generar contraseña** (se muestra una sola vez).
- Ya no existe la "contraseña temporal" que obligaba a cambiarla al entrar.
- `php artisan usuario:clave admin` sigue sirviendo si el admin olvida la suya.

## ✅ Vuelta 15 (Producción y Recetas)

No necesita migración.

**Producción**
- **¿Qué producir hoy?** (arriba de *Nueva producción*): productos en su mínimo o cerca, cuánto venden
  al día (promedio de 7 días), cantidad sugerida en tandas completas y si alcanzan los insumos
  (y cuál falta). Botón "Producir N" que llena el formulario. En el listado, un aviso lleva ahí.
- Al elegir un producto: **para cuántas unidades alcanzan los insumos** (y cuál limita, con
  "Usar el máximo") y **costo de los insumos** de la tanda y por unidad.
- Mensaje claro si no alcanza: "No alcanzan los insumos: Harina (necesitas 3 kg, hay 2.5 kg).
  Con lo que hay puedes producir hasta 250 unidades." Al guardar se bloquea cada insumo para que
  dos producciones a la vez no dejen el stock en negativo.
- "Producir" desde la ficha del producto abre el formulario con ese producto elegido.
- Listado con el ícono y color de la categoría y las unidades producidas hoy.

**Recetas**
- Cada receta muestra cuánto cuesta una unidad, a cuánto se vende y el % de ganancia, y avisa si
  un insumo está inactivo, no tiene costo o si cuesta más de lo que se vende.
- Las cantidades se pueden escribir en **g o mL** (se guardan en kg o L); al editar, lo menor a
  1 kg/L aparece en g/mL. Costo de la tanda y por unidad mientras se edita.
- **Copiar los ingredientes de otro producto** y no se permite repetir un insumo.
- Buscador y filtro (con receta, sin receta, con avisos).
- Nuevo `app/Support/PlanProduccion.php` con los cálculos.

## ✅ Vuelta 16 (rediseño: Nueva producción, Ver detalle, Editar receta, Editar producto)

No necesita migración.

- **Nueva producción** en 3 pasos: (1) elegir el producto en fichas con buscador (marca "Sugerido" y
  "Sin receta"), (2) cantidad con − / + por tandas y botones "1, 2, 3 tandas" y "Máximo", (3) fecha y
  nota. A la derecha, un resumen fijo: alcanza para, stock después, costo de insumos, ganancia
  estimada e insumos con barra. Empieza con 1 tanda.
- **Ver detalle de producción**: cabecera con el producto, tandas, costo de insumos, valor de venta y
  ganancia estimada; insumos **realmente descontados** (no la receta actual) con su costo y cómo quedó
  el stock; "Qué pasó"; botones Ver producto y **Repetir producción**.
- **Editar receta** ahora es una pantalla propia (`/produccion/recetas/{producto}/editar`): filas
  numeradas con insumo, cantidad, unidad (g/kg, mL/L) y costo; stock y costo de cada insumo; panel
  con rendimiento, costo de la tanda y por unidad, y % de ganancia con barra; copiar de otro producto.
- **Nuevo / Editar producto** comparten un formulario por secciones (información, foto con vista
  previa y arrastrar, precio y ganancia con barra, stock, interruptor de disponible) y una columna con
  la vista previa del catálogo y lo que falta (precio, costo, receta).

## ✅ Vuelta 17 (listas desplegables de los filtros)

Solo cambia `resources/views/layouts/app.blade.php`. Las listas desplegables (Categoría, Estado,
Ordenar, etc.) tienen ahora su propia flecha con espacio reservado, así el texto elegido
("Galletas y dulces") ya no queda tapado por la flecha en ningún navegador. Además, en las barras de
búsqueda cada etiqueta queda pegada a su lista (ya no queda "ORDENAR" sola al final de una línea).

## ✅ Vuelta 18 (buscadores con sugerencias)

No necesita migración. En todos los buscadores (Productos, Materia prima, Categorías, Usuarios,
Clientes, Proveedores, Recetas, Historial y Reporte de stock):
- Al hacer clic se despliega la lista completa (con el estilo del sistema, en claro u oscuro; ya no
  aparece el autocompletar negro del navegador).
- Mientras escribes, la lista se va achicando y resalta lo escrito; no importan tildes ni mayúsculas
  ("frances" encuentra "Pan francés") y se puede escribir en desorden ("pan yem").
- Clic o ↑ ↓ + Enter en una sugerencia busca al instante; Esc cierra; Enter sin elegir busca lo escrito.
- Nuevo `SugerenciasController` (`/sugerencias/{fuente}`); cada lista pide el mismo permiso que su pantalla.
- Las listas desplegables en modo oscuro vuelven a mostrar su flecha.
- **Reporte de kardex**: el buscador del insumo/producto ya no usa la lista del navegador (la negra):
  muestra los 46 insumos y productos con su stock y se achica al escribir; al elegir carga su kardex.
- **Reporte de stock**: buscador con el mismo estilo de los demás (ícono, etiquetas y botón Filtrar);
  las sugerencias siguen a "Mostrar" (solo insumos, solo productos o todo).

## ✅ Vuelta 19 (Inventario)

> **Después de copiar, ejecuta `php artisan migrate`** (agrega `es_control` y marca como "insumos de control" a harina, azúcar, sal, levadura, manteca, mantequilla, huevo, leche, aceite y margarina).

- **Stock protegido:** en *Editar insumo* el stock ya no se escribe a mano; solo cambia con compras, producción, ajustes o conteo. El stock inicial de un insumo nuevo queda como movimiento en el kardex.
- **Ajuste claro:** dos opciones — *Merma o pérdida* (con motivo: vencido, roto, mal estado, otro) o *Corrección por conteo*. Se puede escribir en g/mL; muestra antes → después y la pérdida en S/.
- **Ficha del insumo** (clic en la tarjeta): stock, días que alcanza, consumo de 14 días, recetas donde se usa, últimas compras, últimos movimientos y último conteo.
- **Listado:** resumen (insumos, stock bajo, agotados, valor del almacén), filtro por stock, orden por urgencia / días / valor, "Alcanza para N días" y etiqueta *Control*.
- **Nuevo / Editar insumo** por secciones con vista previa.
- **Movimientos de materia prima y de productos:** periodos rápidos (hoy, 7, 30 días, todo), buscador, filtro por motivo, resumen con valor en S/ y lista agrupada por día con saldo antes → después.
- **Conteo físico:** modo *Insumos de control* (rápido, para el PER) o *Todos*; progreso, diferencia en vivo con su valor, PER en vivo, conteo a ciegas, botón "= Coincide", confirmación antes de guardar.
- **Historial y detalle del conteo:** PER de cada conteo (insumos con diferencia ÷ contados × 100), gráfico de tendencia, faltante/sobrante en S/.

## ✅ Vuelta 20 (Nuevo / Editar insumo y búsqueda del conteo)

- **Nuevo / Editar insumo:** la unidad se elige con un clic (Peso, Líquidos, Por pieza); avisa al instante si el nombre ya existe o se parece a otro (y el servidor tampoco deja repetirlo); muestra las conversiones (0.5 kg = 500 g, S/ por g, cuánto cuesta cada compra); al editar sugiere el stock mínimo según el consumo (3 días) y avisa si cambias la unidad de un insumo que ya tiene recetas o movimientos; lista de revisión antes de guardar.
- **Conteo físico fuera del menú:** se entra con el botón "Conteo físico" de Materia Prima (o desde el Dashboard). Solo aparece en el menú para quien tiene permiso de conteo pero no de Materia Prima.
- **Búsqueda del conteo:** sin tildes y con varias palabras ("har tri"), resalta lo encontrado, botón para borrar (o Esc), filtros Por contar / Contados / Con diferencia con sus números, "Mostrando X de Y", Enter salta a escribir la cantidad y vuelve al buscador, "/" abre el buscador, y si no encuentra en los de control ofrece buscar en todos los insumos.

## ✅ Vuelta 21 (Detalles de movimientos e historial)

- **Historial de conteos:** el botón "Nuevo Conteo" ahora es **Regresar** (vuelve a la pantalla del conteo).
- **Movimientos de materia prima y de productos:** cada movimiento tiene su flecha (verde ↑ si el stock subió, roja ↓ si bajó, ⇅ en ajustes), flecha de color entre el stock de antes y el de después, y en la cabecera de cada día cuántos subieron y cuántos bajaron. El resumen de arriba usa las mismas flechas. En celular la cantidad queda al lado del nombre.
- **Al entrar a cualquiera de los dos movimientos** se muestra **Todo** (antes eran 7 días); los botones Hoy / 7 / 30 días siguen para filtrar.

## ✅ Vuelta 22 (lista de sugerencias en el buscador del conteo)

- El buscador del **Conteo físico** ahora abre la **lista desplegable** como los demás buscadores: al hacer clic muestra todos los insumos y se va achicando mientras escribes (sin tildes, varias palabras, resaltado).
- Cada sugerencia dice si está **por contar** (con lo que dice el sistema), **contado y coincide** o **contado con diferencia**, con su ícono de color. En conteo a ciegas no muestra lo del sistema.
- **↑ ↓** para moverse, **Enter** o clic salta a la fila del insumo con el cursor listo para escribir lo contado; al dar Enter en la cantidad vuelve al buscador para el siguiente. **Esc** cierra la lista.

## ✅ Vuelta 23 (Compras completo)

> **Después de copiar, ejecuta `php artisan migrate`** (000026: guarda quién y cuándo recibió o anuló, el motivo, lo que llegó de verdad de cada insumo y si los precios incluyen IGV).

- **Listado de compras:** resumen (por recibir y su monto, atrasadas de más de 3 días, gasto del mes comparado con los mismos días del mes anterior), franja **Por recibir** con botón Recibir, periodos rápidos, buscador (proveedor, insumo, factura o #número), filtros por proveedor y estado.
- **Nueva compra:** buscador de insumos con lista (primero los del proveedor elegido, marca los de stock bajo), botones **Agregar sus insumos con stock bajo** y **Repetir su última compra**, cantidades en g/mL, precio comparado con el costo anterior (▲▼ %), opción **precios con IGV**, opción **El pedido ya llegó** (recibir al guardar). No deja repetir la misma factura del mismo proveedor, ni un insumo dos veces, ni fecha futura. Se abre ya rellena desde la ficha de un insumo, el reporte de stock o "Repetir compra".
- **Detalle de la compra:** seguimiento (registrada → esperando → recibida / anulada, con quién y cuándo), variación de precio contra la compra anterior, contacto del proveedor, **Enviar pedido por WhatsApp**, **Imprimir** orden de compra, Repetir compra.
- **Recibir:** pantalla para anotar lo que llegó de verdad (y el precio de la factura); si llegó distinto pide una nota y el total se recalcula. El stock y el kardex usan lo recibido.
- **Anular:** pide el motivo; si ya estaba recibida devuelve el stock (kardex) y, si venía de una orden automática, la orden vuelve a pendientes.
- **Proveedores:** tarjetas con insumos, compras, total comprado, última compra, pedidos por recibir y órdenes automáticas; llamar / WhatsApp / correo; **ficha del proveedor** (compras por mes, insumos que vende con su último precio, últimas compras); formulario por secciones con validación de **RUC** (11 números, 10/15/17/20, sin repetir) y nombre sin repetir.
- **Órdenes automáticas:** se revisa el stock **solo al entrar**; agrupadas por proveedor con cantidad editable, días que alcanza el stock y costo estimado; **Convertir en compras** crea una compra por proveedor. No se repite una orden si el insumo ya viene en una compra por recibir ni si se descartó (mientras el stock no siga bajando).

## ✅ Vuelta 24 (órdenes automáticas fuera del menú y barras fijas abajo)

- **Órdenes automáticas fuera del menú:** se entra con el botón "Órdenes automáticas" de Compras (con el número de pendientes) o desde el Dashboard; dentro hay un botón para volver a Compras y el menú marca "Compras". Solo sale en el menú para quien tiene permiso de órdenes pero no de Compras.
- **Barras de abajo fijas** (Conteo físico y Órdenes automáticas): ya no se suben al llegar al final; quedan pegadas al borde de la pantalla y el pie "Sistema de Gestión — Muruwasi" se muestra encima. Se acomodan con el menú abierto, contraído y en celular.

## ✅ Vuelta 25 (un insumo con varios proveedores y costo promedio ponderado)

> **Después de copiar, ejecuta `php artisan migrate`** (000027: el costo de los insumos pasa a guardarse con 4 decimales).

- **Órdenes automáticas:** en cada insumo se elige **a quién pedirlo**. La lista muestra primero los proveedores que ya se lo vendieron con su último precio (★ el más barato) y luego los demás. Al cambiarlo, el insumo **se queda en su lugar** con la etiqueta "Se pedirá a …"; los totales de cada proveedor, el pie ("Compras a crear" con el detalle) y la confirmación se calculan según lo elegido, y la compra se arma con lo que cobró ese proveedor. Los insumos **sin proveedor** ahora también se pueden pedir eligiendo uno.
- **Ficha del insumo → Precios por proveedor:** último precio y promedio de cada proveedor, cuál es el principal y cuál el más barato, diferencia en % y botón **Comprarle**.
- **Nueva compra:** al elegir proveedor se propone **lo que ese proveedor cobró la última vez** y avisa si otro lo vende más barato.
- **Costo promedio ponderado:** al recibir una compra el costo del insumo es el promedio entre lo que había (a su costo) y lo que llega (a su precio sin IGV); al anular una compra recibida se saca de ese promedio. Antes era el último precio. Se guarda con 4 decimales y se muestra con 2.

## ✅ Vuelta 26 (Reportes)

> **Después de copiar, ejecuta `php artisan migrate`** (000028: crea la tabla de cierres diarios y da los 3 reportes nuevos a quien ya tenía reportes).

- **Cierre diario de inventario (nuevo, indicador TCI):** todos los insumos con saldo inicial, entradas, salidas, ajustes y saldo final del día, valorizado; avisa si algún insumo **no cuadra**. Botón **Cerrar el día** (guarda quién y a qué hora, con nota); si después llegan movimientos de ese día, avisa que hay que volver a cerrar. Navegación por días, filtros (con movimiento, bajo el mínimo, no cuadran), historial de 14 días (cerrado / sin cerrar), **PDF con firmas** y Excel. Está en el menú de Reportes.
- **Reporte de mermas (nuevo):** lo perdido en S/ por insumo y producto, por motivo (vencido, roto, mal estado, otro), detalle con quién lo registró e **índice de merma** (perdido ÷ usado + perdido). PDF y Excel.
- **Reporte de compras (nuevo):** gastado con y sin IGV, por proveedor (con %), por insumo (cantidad, precio promedio, mín–máx, primer → último precio y variación), gasto por día/semana/mes y lista de compras. Filtro por proveedor. PDF y Excel.
- **Resumen de reportes:** indicadores del día (valor del almacén, stock bajo, cierre de hoy y días sin cerrar, mermas y compras del mes, PER del último conteo) y tarjetas de todos los reportes.
- **Reporte de stock:** columna **Alcanza (días)** y orden (urgentes, menos días, mayor valor, nombre); también en PDF y Excel.
- **Kardex:** el gráfico usa una versión fija de Chart.js.
- Todos los reportes tienen periodos rápidos (este mes, mes anterior, 30 días, 3 meses, año, todo) o fechas exactas.
- **Menú de Reportes:** Resumen, Cierre Diario, Stock, Kardex, **Mermas** y **Compras** (cada uno según los permisos del usuario).

## ✅ Vuelta 27 (Ventas)

> **Después de copiar, ejecuta `php artisan migrate`** (000029: agrega recibido, vuelto, n° de operación y datos de anulación a las ventas, y da el Reporte de Ventas a quien ya tenía reportes).

- **IGV corregido:** los precios de venta **ya incluyen el IGV** (como exige la ley en Perú para precios al consumidor). Antes se sumaba 18 % encima y se cobraba de más (11 panes de S/ 0.20 salían S/ 2.60 en vez de S/ 2.20). Ahora el total es la suma y se muestra "IGV (18 %) incluido". Las ventas antiguas mantienen sus montos.
- **Las ventas se hacen desde Productos (venta rápida):** se quitó la pantalla "Nueva venta" (si alguien entra a esa dirección, lo lleva a Productos). Se abre con el botón flotante de abajo (muestra el total). En la venta rápida: **buscador para agregar cualquier producto** aunque no esté en la página o filtro: busca por palabras en cualquier orden y por categoría ("yema pan", "pastelería"), ignora tildes, resalta lo encontrado, muestra imagen, precio, stock y cuántos ya van en la venta; al entrar muestra los más vendidos; **"3 pan" agrega 3 de una vez**; flechas y Enter para elegir, Esc limpia. Debajo, botones de los 6 más vendidos para agregarlos con un toque, cantidades editables con tope de stock, cliente (por defecto Cliente General), medio de pago con botones, **recibido y vuelto** con billetes rápidos, n° de operación para Yape/Plin/tarjeta y nota. Las tarjetas muestran cuántos van en la venta. El carrito usa el precio y stock de ese momento; si la venta falla no se pierde (vuelve abierto con el error) y se vacía solo cuando se registró. Al cobrar sale el aviso con **Imprimir ticket**. Atajos: F2 abre y busca, F9 cobra, Esc cierra.
- **Ventas:** caja de hoy (total, vs. ayer, ticket promedio, lo más vendido) y montos por medio de pago; periodos rápidos, búsqueda por n°, cliente o producto, filtros por pago, estado, vendedor y fechas; lista agrupada por día con el total de cada día.
- **Detalle de la venta:** productos, pago (recibido, vuelto, operación), seguimiento (quién registró y quién anuló), ticket y PDF.
- **Anular en vez de eliminar:** las ventas ya no se borran; se anulan **escribiendo el motivo**. Los productos vuelven al stock, queda en el kardex y se ve quién y cuándo la anuló.
- **Ticket de 80 mm** para impresora térmica (se abre la impresión sola). Número de venta correlativo `V-000001` que no se repite.
- **Reporte de ventas (nuevo):** vendido con y sin IGV, comparación con el periodo anterior, ventas por día/semana/mes y **por hora (hora pico)**, productos más vendidos con **costo, ganancia y margen**, por medio de pago, por día de la semana, mejores clientes, por vendedor y por categoría. PDF y Excel. En el menú de Reportes y en el Resumen.
- El Excel de ventas incluye pago, n° de operación, recibido, vuelto y motivo de anulación.

## ✅ Vuelta 28 (tocar la tarjeta para ver más y "Cancelar" que regresa bien)

- **Cancelar en Editar y Ajustar stock** (insumos y productos): regresa a la **lista** desde donde se abrió (con sus filtros), ya no a la ficha. Solo vuelve a la ficha si se entró desde la ficha. Si no se sabe de dónde se vino, va a la lista. Lo mismo al guardar. Antes fallaba porque el sistema tomaba como “página anterior” la última que se cargó en segundo plano.
- **Lo mismo en Editar de Proveedores, Categorías, Clientes y Usuarios:** Cancelar y Guardar regresan a la lista desde donde se abrió (con su búsqueda y filtros); si se entró desde la ficha, a la ficha. Antes, al editar un proveedor se iba a su ficha.
- **Toda la tarjeta o fila se puede tocar** para ver más, no solo el nombre. Al pasar el mouse se levanta, se marca el nombre y aparece "Ver ficha"; con el teclado se entra con Enter y con Ctrl + clic se abre en otra pestaña. Los botones, interruptores y enlaces de adentro siguen funcionando igual. Aplica en:
  - Materia prima y Productos (abre la ficha), Proveedores, Categorías, Compras y Ventas.
  - Clientes y Producción (abre el detalle en la ventana).
  - **Movimientos de materia prima y de productos:** cada movimiento abre lo que lo originó: la compra, la venta o la producción; si fue un ajuste o una merma, la ficha del insumo o producto.

## 🔲 Lo que falta

### A. Configuración (no es código)
1. **Activar el scheduler en el servidor.** El comando automático y el respaldo
   diario solo corren si Laravel tiene quien lo dispare. En local usar
   `php artisan schedule:work` (ventana abierta) para probarlo; en hosting hace
   falta un cron cada minuto:
   `* * * * * cd /ruta/proyecto && php artisan schedule:run >> /dev/null 2>&1`
2. **Activar el correo.** En el `.env`: `ALERTA_STOCK_CORREO=correo@ejemplo.com` y
   un `MAIL_MAILER=smtp` real con sus `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`,
   `MAIL_PASSWORD` (en Gmail hace falta una *contraseña de aplicación*). Hoy el
   `.env` usa `MAIL_MAILER=log`, que solo escribe el correo en `storage/logs`.
   Probar con: `php artisan inventario:revisar-stock`.
3. **Correr la prueba del kardex** con PHPUnit real:
   `php artisan test --filter=KardexCalculoTest` (aquí se validó con un runner
   propio porque no hay `vendor` disponible).

### B. Decisiones pendientes
4. **Unificar los listados generales** (`kardex.index` = Movimientos de Productos,
   `movimientos.index` = Movimientos de Materia Prima) con *Reportes → Kardex*:
   hoy son dos caminos para ver cosas parecidas.
5. **Reportes por correo** (resumen semanal de stock, no solo alerta diaria).

### C. Lo que falta para el POSTEST de la tesis (prioridad alta)
Según el Capítulo III: pretest manual del **19 oct al 1 nov**, implementación y
capacitación del **2 al 15 nov**, postest con el sistema del **16 al 29 nov**. Los
indicadores los mide un observador con cronómetro (ficha del Anexo 3), así que el
sistema no los calcula: tiene que permitir hacer rápido las cuatro tareas medidas.

| Indicador | Tarea medida | Qué falta en el sistema |
|---|---|---|
| **TPR** | Primeros 10 movimientos de la jornada | **Registro rápido** de entrada o salida de un insumo (insumo, tipo, cantidad, motivo) en una sola pantalla. Hoy solo hay compra completa o ajuste. |
| **TCS** | 5 consultas de insumos distintos | **Buscador de stock** en el menú superior, disponible en cualquier pantalla. |
| **TCI** | Cierre diario de inventario | ✅ Hecho en la vuelta 26 (Reportes → Cierre diario). |
| **PER** | Mismos 10 insumos fijos, stock registrado vs. contado | **Conteo físico de 10 insumos fijos** con "¿Coincide? Sí/No" y el PER calculado. |

7. **Registro rápido de movimientos** (TPR).
8. **Buscador rápido de stock** (TCS).
9. **Cierre de inventario diario** (TCI).
10. **Conteo físico de 10 insumos fijos y PER** (PER).
11. **Carga inicial** de los 30+ insumos reales con su stock antes del 2 de noviembre
    (sirve una importación desde Excel).
12. **Puesta en marcha y capacitación** (2–15 nov): decidir dónde se instala (hosting
    o PC de la panadería), activar el scheduler/cron (respaldo diario y revisión de
    stock) y preparar un manual corto para el almacenero.

### D. Documento de tesis (no es código)
13. **Capítulo II vs. sistema:** el Capítulo II menciona Tailwind CSS, pero la interfaz
    usa Bootstrap 4 y AdminLTE 3 (Tailwind solo aparece en las vistas de perfil).
    Alinear uno de los dos.
14. **Carátula:** dice "Ciclo IV"; verificar que sea el correcto.
15. **Pegar las capturas** en `Capitulo_IV_Desarrollo_del_Sistema_Muruwasi.docx` (18
    recuadros pendientes) y completar Resumen, Abstract y Capítulo V cuando termine
    el postest.
16. **Pre-test / Post-test** (pedido del jurado): ya está descrito en el Capítulo III;
    revisar que el texto coincida con la ficha del Anexo 3.

## 🧪 Cómo probar esta entrega (5 minutos)

1. `php artisan serve`, entrar como `almacenero` / `almacenero123`.
2. **Dashboard** → ver el "Panel del almacén".
3. **Reportes → Stock** → ver semáforo, "Reponer ≈" y exportar Excel/PDF.
4. **Reportes → Kardex** → elegir un insumo con un ajuste o conteo físico y
   comprobar que el ajuste aparece como entrada/salida y que dice "El kardex cuadra".
5. `php artisan inventario:revisar-stock` → debe crear órdenes (o decir que no hay).
6. **Kardex**: escribir parte de un nombre en el buscador, ver el gráfico del saldo y,
   con un insumo en stock bajo, el botón "Registrar conteo físico".
7. **PDF de Stock**: comprobar *Agotado / Stock bajo* y la columna *Reponer*.
8. `php artisan test --filter=KardexCalculoTest` → 8 pruebas en verde.

> Nota: los cambios se validaron por sintaxis (PHP/Blade) pero no se ejecutaron
> contra tu base de datos; si algo falla al probar, pásame el mensaje exacto.

## Archivos tocados (vueltas 2, 3 y 4)

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
- `app/Support/KardexCalculo.php` — **nuevo** (vuelta 3)
- `tests/Unit/KardexCalculoTest.php` — **nuevo** (vuelta 3)
- `config/inventario.php` — **nuevo** (vuelta 3)
- `resources/views/emails/stock-bajo.blade.php` — **nuevo** (vuelta 3)
- `resources/views/reportes/stock-pdf.blade.php` (vuelta 3)
- `.env.example` — variable `ALERTA_STOCK_CORREO` (vuelta 3)
- `routes/web.php`, `resources/views/layouts/app.blade.php`, `resources/views/kardex/index.blade.php`,
  `resources/views/movimientos/index.blade.php`, `resources/views/ventas/create.blade.php`,
  `resources/views/usuarios/index.blade.php`, `resources/views/conteo-fisico/historial.blade.php`,
  `app/Http/Controllers/KardexController.php`, `app/Http/Controllers/UsuariosController.php`,
  `app/Models/Usuario.php`, `database/seeders/DatabaseSeeder.php` (vuelta 4: limpieza)
- `database/migrations/2025_01_01_000018_drop_tiempos_tables.php` — **nuevo** (vuelta 4)
- `CAMBIOS-FALTANTES.md`
