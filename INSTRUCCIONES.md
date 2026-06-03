# 🍞 Sistema de Gestión - Panadería Muruwasi

## ¿Cómo entrar al sistema?
- URL local: `http://127.0.0.1:8000/login`
- **Usuario:** admin
- **Contraseña:** admin123

---

## 📋 Módulos del Sistema

### 🏠 Dashboard
Pantalla principal con un resumen general del negocio:
- Ventas del día y del mes
- Productos con stock bajo (necesitan reposición)
- Los productos más vendidos
- Últimas ventas realizadas

---

### 📦 Productos
Aquí se registran todos los productos que la panadería **fabrica y vende**:
- Pan frances, tortas, galletas, empanadas, bebidas, etc.
- Cada producto tiene precio de venta, costo de producción y stock
- Cuando el stock baja del mínimo, aparece una alerta en el dashboard
- Se puede activar o desactivar productos sin eliminarlos

---

### 🏷️ Categorías
Agrupa los productos por tipo para encontrarlos más fácil:
- Panes, Pasteles, Galletas, Empanadas, Bebidas, etc.
- Cada producto pertenece a una categoría

---

### 🌾 Materia Prima (Insumos)
Registra todos los ingredientes que se usan para producir:
- Harina, azúcar, mantequilla, huevos, leche, levadura, etc.
- Tiene stock actual y stock mínimo (alerta cuando se acaba)
- El stock se descuenta automáticamente cuando se produce

---

### 🏭 Producción
Registra cuándo se fabrican los productos:
- Se indica qué producto se fabricó y cuántas unidades
- Al registrar una producción, el stock del producto **aumenta**
- Los ingredientes de la receta se **descuentan** del stock de materia prima
- Requiere que el producto tenga una receta configurada

---

### 📝 Recetas
Define los ingredientes necesarios para fabricar cada producto:
- Ej: Para hacer 50 panes franceses se necesita 1kg de harina, 10g de levadura, 20g de sal
- El rendimiento indica cuántas unidades produce la receta
- Sin receta, no se puede registrar la producción

---

### 🚚 Proveedores
Empresas o personas que **venden insumos a la panadería**:
- Molino que vende harina
- Distribuidora de huevos
- Proveedor de lácteos
- Se puede ver el historial de compras por proveedor

---

### 🛒 Compras
Registra cuando la panadería **compra insumos a sus proveedores**:
- Se indica qué insumos se compraron, cantidad y precio
- Tiene estado: Pendiente → Recibida → Anulada
- Al marcar como "Recibida", el stock de materia prima **aumenta automáticamente**
- Incluye cálculo de IGV (18%)

---

### 👥 Clientes y Distribución
Registra a quienes la panadería **vende y distribuye sus productos**:
- Bodegas del barrio
- Supermercados (Plaza Vea, Tottus, etc.)
- Colegios
- Restaurantes
- Otras panaderías
- Clientes particulares
- Se puede ver el historial de compras de cada cliente y cuánto ha gastado en total

---

### 💰 Ventas
Registra cada venta que realiza la panadería:
- Se selecciona el cliente y los productos que compró
- Acepta distintos métodos de pago: efectivo, Yape, Plin, tarjeta
- Incluye cálculo de IGV (18%)
- Al registrar la venta, el stock del producto **disminuye automáticamente**
- Se puede anular una venta si fue un error

---

### 📊 Movimientos de Inventario
Registro automático de todos los cambios de stock de materia prima:
- Cada entrada (compra) y salida (producción) queda registrada
- Muestra el stock antes y después de cada movimiento
- Útil para auditorías y control del inventario

---

### 👤 Usuarios
Gestión de las personas que usan el sistema:
- **Admin:** acceso total al sistema
- **Vendedor:** solo puede registrar ventas
- **Almacenero:** gestiona inventario y compras
- Se puede activar o desactivar usuarios

---

## 🔄 Flujo del Negocio

```
PROVEEDORES → COMPRAS → MATERIA PRIMA
                              ↓
                         PRODUCCIÓN (usa recetas)
                              ↓
                           STOCK DE PRODUCTOS
                              ↓
CLIENTES ←————————————————— VENTAS
```

1. El proveedor te vende insumos → se registra en **Compras**
2. Con los insumos fabricas productos → se registra en **Producción**
3. Los productos se acumulan en stock → visible en **Productos**
4. Los clientes te compran productos → se registra en **Ventas**
5. Todo queda en el historial → visible en **Dashboard** y **Movimientos**

---

## 👥 Usuarios de Prueba
| Usuario | Contraseña | Rol |
|---------|-----------|-----|
| admin | admin123 | Administrador |
| mquispe | vendedor123 | Vendedora |
| cmamani | almacen123 | Almacenero |
