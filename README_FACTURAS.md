# 🗄️ Base de Datos - Sistema de Facturación POS

## 📋 Descripción General

Este documento describe la estructura completa de la base de datos para el sistema de facturación Point of Sale (POS) integrado al sistema de inventario.

## 🏗️ Estructura de Tablas

### 1. Tabla `facturas`
Almacena la información principal de cada factura generada en el sistema POS.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | INT AUTO_INCREMENT | Identificador único de la factura |
| `numero_factura` | VARCHAR(20) UNIQUE | Número único de factura (ej: FAC-20240114-0001) |
| `cliente_nombre` | VARCHAR(100) | Nombre del cliente |
| `cliente_documento` | VARCHAR(20) | Documento/ID del cliente |
| `fecha_creacion` | TIMESTAMP | Fecha y hora de creación automática |
| `total` | DECIMAL(10,2) | Total final de la factura |
| `impuesto` | DECIMAL(10,2) | Porcentaje o monto de impuesto aplicado |
| `descuento` | DECIMAL(10,2) | Monto de descuento aplicado |
| `metodo_pago` | ENUM | Método de pago utilizado |
| `estado` | ENUM | Estado actual de la factura |
| `usuario_id` | INT | ID del usuario que creó la factura |

### 2. Tabla `factura_detalles`
Contiene el detalle de productos incluidos en cada factura.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | INT AUTO_INCREMENT | Identificador único del detalle |
| `factura_id` | INT | ID de la factura padre |
| `producto_id` | INT | ID del producto vendido |
| `cantidad` | INT | Cantidad vendida del producto |
| `precio_unitario` | DECIMAL(10,2) | Precio unitario al momento de la venta |
| `subtotal` | DECIMAL(10,2) | Subtotal (cantidad × precio_unitario) |

## 🔗 Relaciones

- **facturas.usuario_id** → **usuarios.id** (Usuario que creó la factura)
- **factura_detalles.factura_id** → **facturas.id** (Factura padre)
- **factura_detalles.producto_id** → **inventario.id** (Producto vendido)

## 📊 Estados de Factura

- **pendiente**: Factura creada pero no pagada
- **pagada**: Factura completada y pagada
- **cancelada**: Factura anulada

## 💳 Métodos de Pago

- **efectivo**: Pago en efectivo
- **tarjeta_credito**: Tarjeta de crédito
- **tarjeta_debito**: Tarjeta de débito
- **transferencia**: Transferencia bancaria
- **cheque**: Pago con cheque
- **otro**: Otro método de pago

## 📈 Vistas Incluidas

### `vista_facturas_completas`
Vista completa con información de facturas, clientes y usuarios.

### `vista_ventas_por_metodo`
Estadísticas de ventas agrupadas por método de pago.

### `vista_productos_mas_vendidos`
Ranking de productos más vendidos con estadísticas.

## ⚙️ Procedimientos Almacenados

### `sp_crear_factura`
Crea una nueva factura con número automático.

### `sp_agregar_producto_factura`
Agrega productos a una factura existente y actualiza totales.

## 🚀 Instalación

1. Ejecutar el archivo `facturas_db.sql` en MySQL:
   ```sql
   SOURCE facturas_db.sql;
   ```

2. Verificar que las tablas se crearon correctamente:
   ```sql
   SHOW TABLES LIKE 'factur%';
   ```

## 📋 Uso del Sistema

### Crear Factura
1. Acceder al POS (`pos.php`)
2. Seleccionar productos y cantidades
3. Ingresar datos del cliente
4. Seleccionar método de pago
5. Aplicar descuentos/impuestos si es necesario
6. Confirmar y crear factura

### Gestionar Facturas
1. Acceder a "Facturas" en el menú
2. Ver lista completa de facturas
3. Cambiar estados usando el dropdown
4. Ver detalles e imprimir

### Estados y Cambios
- Las facturas se crean con estado "pendiente"
- Cambiar a "pagada" cuando se complete el pago
- Cambiar a "cancelada" solo con confirmación

## 🔒 Seguridad

- Control de permisos por usuario
- Solo administradores pueden ver todas las facturas
- Usuarios normales solo ven sus propias facturas
- Estados críticos requieren confirmación

## 📊 Consultas Útiles

### Facturas del mes actual
```sql
SELECT * FROM facturas
WHERE MONTH(fecha_creacion) = MONTH(CURRENT_DATE())
AND YEAR(fecha_creacion) = YEAR(CURRENT_DATE());
```

### Total de ventas por día
```sql
SELECT DATE(fecha_creacion) as fecha, SUM(total) as total_ventas
FROM facturas
WHERE estado = 'pagada'
GROUP BY DATE(fecha_creacion)
ORDER BY fecha DESC;
```

### Productos más vendidos
```sql
SELECT i.nombre, SUM(fd.cantidad) as total_vendido
FROM factura_detalles fd
JOIN inventario i ON fd.producto_id = i.id
JOIN facturas f ON fd.factura_id = f.id
WHERE f.estado = 'pagada'
GROUP BY i.id, i.nombre
ORDER BY total_vendido DESC
LIMIT 10;
```

## 🔧 Mantenimiento

### Backup de facturas
```sql
mysqldump inventario_db facturas factura_detalles > backup_facturas.sql
```

### Limpiar facturas antiguas (ejemplo: más de 2 años)
```sql
DELETE FROM facturas
WHERE fecha_creacion < DATE_SUB(CURRENT_DATE(), INTERVAL 2 YEAR);
```

## 📞 Soporte

Para problemas con la base de datos de facturas, verificar:
1. Que las tablas existen y tienen la estructura correcta
2. Que las llaves foráneas están configuradas
3. Que los permisos de usuario son correctos
4. Que los datos de ejemplo se insertaron correctamente