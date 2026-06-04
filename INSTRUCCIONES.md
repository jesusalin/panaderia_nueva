# 🍞 Sistema de Gestión — Panadería Muruwasi

## ¿Cómo instalar?
```bash
git clone https://github.com/jesusalin/panaderia_nueva.git
cd panaderia_nueva
composer install
cp .env.example .env
php artisan key:generate
```
Configura tu `.env`:
```
DB_DATABASE=panaderia_nueva
DB_USERNAME=root
DB_PASSWORD=
```
```bash
php artisan migrate:fresh --seed
php artisan serve
```

## ¿Cómo entrar?
URL: `http://127.0.0.1:8000/login`

| Usuario | Contraseña | Rol |
|---------|-----------|-----|
| admin | admin123 | Administrador |
| mquispe | vendedor123 | Vendedora |
| cmamani | almacen123 | Almacenero |

---

## 📋 Módulos del Sistema

### 🏠 Dashboard
Pantalla principal con resumen del negocio. Muestra 8 indicadores:
- **Ventas de Hoy** — total vendido en el día
- **Ventas del Mes** — total vendido en el mes actual
- **Compras del Mes** — total gastado en insumos
- **Ganancia Bruta** — diferencia entre ventas y compras del mes
- **Alertas de Stock** — productos e insumos por debajo del mínimo
- **Exactitud del Inventario** — % de productos con stock disponible (KPI de tesis)
- **Registros Procesados Hoy** — cuántas operaciones se registraron hoy (KPI de tesis)
- **Productos en Rotación** — cuántos productos se vendieron este mes

También muestra gráfico de ventas de los últimos 7 días, top 5 más vendidos y rotación rápida de stock.

---

### 📦 Productos
Registra todos los productos que la panadería fabrica y vende:
- Pan frances, tortas, galletas, empanadas, bebidas, etc.
- Cada producto tiene precio de venta, costo de producción y stock
- Alerta en dashboard cuando el stock baja del mínimo

---

### 🏷️ Categorías
Agrupa los productos por tipo:
- Panes, Pasteles, Galletas, Empanadas, Bebidas

---

### 🌾 Materia Prima
Registra todos los ingredientes para producir:
- Harina, azúcar, mantequilla, huevos, leche, levadura, etc.
- Stock mínimo con alerta automática
- El stock se descuenta al registrar producción

---

### 🏭 Producción
Registra cuándo se fabrican los productos:
- Al registrar, el stock del producto **sube**
- Los ingredientes de la receta se **descuentan** de materia prima
- Se registra automáticamente en el **Kardex de Productos**
- Requiere que el producto tenga receta configurada

---

### 📝 Recetas
Define ingredientes para fabricar cada producto:
- Rendimiento: cuántas unidades produce la receta
- Ejemplo: 1kg harina + 10g levadura + 20g sal = 50 panes franceses

---

### 🚚 Proveedores
Empresas que venden insumos a la panadería:
- Molinos, distribuidoras de lácteos, proveedores de huevos, etc.
- Historial de compras por proveedor

---

### 🛒 Compras
Registra compras de insumos a proveedores:
- Estado: Pendiente → Recibida → Anulada
- Al marcar "Recibida", el stock de materia prima **sube automáticamente**
- Incluye cálculo de IGV (18%)

---

### 👥 Clientes y Distribución
Registra a quienes la panadería vende y distribuye:
- **Tipos:** bodega, supermercado, colegio, restaurante, otra panadería, particular
- Campos: RUC, dirección, distrito, referencia
- Vista de detalle con historial de ventas y total comprado por cliente

---

### 💰 Ventas
Registra cada venta:
- Selección de cliente y productos
- Métodos de pago: efectivo, Yape, Plin, tarjeta
- IGV (18%) calculado automáticamente
- Al registrar, el stock del producto **baja** y se registra en el **Kardex**
- Se puede anular una venta (restaura el stock)

---

### 📖 Kardex de Productos *(NUEVO)*
Registro detallado de entradas y salidas de productos terminados:
- **Entradas:** cuando se registra una producción
- **Salidas:** cuando se registra una venta
- **Devoluciones:** cuando se anula una venta
- Muestra stock antes y después de cada movimiento
- Filtros por producto, tipo y rango de fechas
- Permite auditar el inventario de productos

---

### 📊 Rotación de Stock *(NUEVO)*
Reporte mensual de rendimiento de productos:
- Unidades vendidas por producto
- Ingresos generados
- Utilidad bruta por producto
- Margen de ganancia en %
- Alerta de stock bajo
- Filtrable por mes y año
- Totales generales al pie

---

### 📊 Movimientos de Inventario
Kardex de materia prima (insumos):
- Entradas: cuando se recibe una compra
- Salidas: cuando se usa en producción
- Filtros por insumo y tipo de movimiento

---

### 👤 Usuarios
Gestión de accesos al sistema:
- **Admin:** acceso total
- **Vendedor:** solo ventas
- **Almacenero:** inventario y compras

---

## 🔄 Flujo del Negocio

```
PROVEEDORES → COMPRAS → MATERIA PRIMA
                              ↓
                    PRODUCCIÓN (usa recetas)
                              ↓
                    STOCK DE PRODUCTOS ←→ KARDEX PRODUCTOS
                              ↓
CLIENTES ←──────────────── VENTAS
              ↓
          DASHBOARD (KPIs + Rotación de Stock)
```

1. Proveedor vende insumos → **Compras** → stock materia prima sube
2. Con insumos fabricas → **Producción** → stock producto sube + kardex entrada
3. Cliente compra → **Ventas** → stock producto baja + kardex salida
4. Todo visible en → **Dashboard** con KPIs en tiempo real
5. Análisis de rentabilidad → **Rotación de Stock**
