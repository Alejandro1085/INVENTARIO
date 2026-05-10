# 📊 Estructura de Base de Datos - Sistema de Inventario

## 🏗️ Arquitectura General

La base de datos `inventario_db` está diseñada con una arquitectura relacional normalizada que soporta:
- **Múltiples usuarios** con permisos diferenciados
- **Auditoría completa** de todas las operaciones
- **Escalabilidad** para futuras expansiones
- **Integridad referencial** con foreign keys

## 📋 Tablas Principales

### 1. `usuarios`
**Propósito:** Gestión de usuarios del sistema
```sql
id INT PRIMARY KEY AUTO_INCREMENT
usuario VARCHAR(50) UNIQUE NOT NULL
contrasena VARCHAR(255) NOT NULL
nombre VARCHAR(100) NOT NULL
email VARCHAR(100)
fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
ultimo_acceso TIMESTAMP NULL
activo BOOLEAN DEFAULT TRUE
rol ENUM('admin', 'usuario') DEFAULT 'usuario'
```

### 2. `categorias`
**Propósito:** Clasificación de productos
```sql
id INT PRIMARY KEY AUTO_INCREMENT
nombre VARCHAR(100) UNIQUE NOT NULL
descripcion TEXT
fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
usuario_id INT → usuarios(id)
```

### 3. `proveedores`
**Propósito:** Información de proveedores
```sql
id INT PRIMARY KEY AUTO_INCREMENT
nombre VARCHAR(100) NOT NULL
contacto VARCHAR(100)
telefono VARCHAR(20)
email VARCHAR(100)
direccion TEXT
fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
usuario_id INT → usuarios(id)
```

### 4. `inventario`
**Propósito:** Productos principales del sistema
```sql
id INT PRIMARY KEY AUTO_INCREMENT
codigo VARCHAR(50) UNIQUE NOT NULL
nombre VARCHAR(100) NOT NULL
descripcion TEXT
cantidad INT DEFAULT 0
precio DECIMAL(10, 2) DEFAULT 0.00
precio_venta DECIMAL(10, 2) DEFAULT 0.00
categoria VARCHAR(50)
categoria_id INT → categorias(id)
proveedor_id INT → proveedores(id)
stock_minimo INT DEFAULT 5
fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
usuario_id INT NOT NULL → usuarios(id)
imagen VARCHAR(255)
activo BOOLEAN DEFAULT TRUE
```

### 5. `movimientos_inventario`
**Propósito:** Historial de cambios en inventario
```sql
id INT PRIMARY KEY AUTO_INCREMENT
producto_id INT NOT NULL → inventario(id)
tipo ENUM('entrada', 'salida', 'ajuste', 'venta') NOT NULL
cantidad INT NOT NULL
cantidad_anterior INT NOT NULL
cantidad_nueva INT NOT NULL
motivo TEXT
referencia VARCHAR(100)
fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP
usuario_id INT NOT NULL → usuarios(id)
```

### 6. `ventas` y `detalle_ventas`
**Propósito:** Sistema de ventas
```sql
-- ventas
id INT PRIMARY KEY AUTO_INCREMENT
numero_venta VARCHAR(20) UNIQUE NOT NULL
cliente VARCHAR(100)
total DECIMAL(10, 2) NOT NULL
fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP
usuario_id INT NOT NULL → usuarios(id)
estado ENUM('pendiente', 'completada', 'cancelada') DEFAULT 'completada'

-- detalle_ventas
id INT PRIMARY KEY AUTO_INCREMENT
venta_id INT NOT NULL → ventas(id)
producto_id INT NOT NULL → inventario(id)
cantidad INT NOT NULL
precio_unitario DECIMAL(10, 2) NOT NULL
subtotal DECIMAL(10, 2) NOT NULL
```

## 👁️ Vistas Optimizadas

### `vista_productos`
**Propósito:** Vista unificada de productos con información completa
```sql
SELECT i.*, u.nombre as usuario_registro, p.nombre as proveedor,
       CASE WHEN i.cantidad = 0 THEN 'Agotado'
            WHEN i.cantidad <= i.stock_minimo THEN 'Stock Bajo'
            ELSE 'Disponible' END as estado_stock
FROM inventario i
LEFT JOIN usuarios u ON i.usuario_id = u.id
LEFT JOIN proveedores p ON i.proveedor_id = p.id
WHERE i.activo = TRUE
```

### `vista_estadisticas`
**Propósito:** Estadísticas generales calculadas
```sql
SELECT COUNT(*) as total_productos, SUM(cantidad) as total_unidades,
       SUM(precio * cantidad) as valor_inventario, AVG(precio) as precio_promedio,
       COUNT(CASE WHEN cantidad = 0 THEN 1 END) as productos_agotados,
       COUNT(CASE WHEN cantidad <= stock_minimo AND cantidad > 0 THEN 1 END) as productos_stock_bajo
FROM inventario WHERE activo = TRUE
```

### `vista_movimientos_recientes`
**Propósito:** Historial de actividad reciente
```sql
SELECT m.*, i.nombre as producto, i.codigo as codigo_producto, u.nombre as usuario
FROM movimientos_inventario m
JOIN inventario i ON m.producto_id = i.id
JOIN usuarios u ON m.usuario_id = u.id
ORDER BY m.fecha DESC LIMIT 50
```

## ⚡ Triggers Automáticos

### `tr_inventario_update`
**Propósito:** Registra automáticamente movimientos de inventario
```sql
CREATE TRIGGER tr_inventario_update AFTER UPDATE ON inventario
FOR EACH ROW
BEGIN
    IF OLD.cantidad != NEW.cantidad THEN
        INSERT INTO movimientos_inventario
        (producto_id, tipo, cantidad, cantidad_anterior, cantidad_nueva, motivo, usuario_id)
        VALUES (NEW.id, 'ajuste', NEW.cantidad - OLD.cantidad, OLD.cantidad, NEW.cantidad, 'Actualización manual', NEW.usuario_id);
    END IF;
END;;
```

## 🔍 Índices Estratégicos

### Índices de Rendimiento
- `inventario.idx_codigo` - Búsqueda rápida por código
- `inventario.idx_categoria` - Filtrado por categoría
- `inventario.idx_usuario` - Productos por usuario
- `inventario.idx_activo` - Solo productos activos
- `movimientos_inventario.idx_fecha` - Ordenamiento por fecha

### Índices de Relaciones
- Foreign keys en todas las tablas relacionadas
- Constraints de integridad referencial
- Cascade deletes donde aplica

## 📊 Consultas Comunes

### Productos con stock bajo
```sql
SELECT codigo, nombre, cantidad, stock_minimo
FROM inventario
WHERE cantidad > 0 AND cantidad <= stock_minimo
ORDER BY cantidad ASC
```

### Valor total por categoría
```sql
SELECT categoria, COUNT(*) as productos,
       SUM(cantidad) as unidades, SUM(precio * cantidad) as valor
FROM inventario
WHERE categoria != '' AND activo = TRUE
GROUP BY categoria
ORDER BY valor DESC
```

### Movimientos del último mes
```sql
SELECT DATE(fecha) as fecha, tipo, SUM(cantidad) as total
FROM movimientos_inventario
WHERE fecha >= DATE_SUB(NOW(), INTERVAL 30 DAY)
GROUP BY DATE(fecha), tipo
ORDER BY fecha DESC
```

## 🔧 Mantenimiento

### Optimización
```sql
-- Análisis de tablas
ANALYZE TABLE usuarios, categorias, proveedores, inventario, movimientos_inventario, ventas, detalle_ventas;

-- Optimización de índices
OPTIMIZE TABLE inventario;
```

### Backup
```sql
-- Exportar estructura y datos
mysqldump -u root inventario_db > backup_inventario.sql

-- Solo estructura
mysqldump -u root --no-data inventario_db > estructura_inventario.sql
```

## 🚀 Escalabilidad

### Expansiones Futuras
- **Códigos de barras** - Campo adicional en inventario
- **Imágenes de productos** - Campo `imagen` ya preparado
- **Múltiples almacenes** - Nueva tabla `almacenes`
- **Precios por cliente** - Tabla `precios_especiales`
- **Facturación electrónica** - Integración con APIs

### Particionamiento (para grandes volúmenes)
```sql
-- Particionar movimientos por mes
ALTER TABLE movimientos_inventario
PARTITION BY RANGE (YEAR(fecha)) (
    PARTITION p2024 VALUES LESS THAN (2025),
    PARTITION p2025 VALUES LESS THAN (2026)
);
```

## 🔐 Seguridad

### Niveles de Acceso
- **Admin:** Acceso completo a todas las funciones
- **Usuario:** Acceso limitado según permisos
- **Auditoría:** Todos los cambios quedan registrados

### Mejores Prácticas
- Prepared statements en todas las consultas
- Validación de entrada en formularios
- Hash de contraseñas con bcrypt
- Sesiones seguras con regeneración

---

**Última actualización:** 14 de enero de 2026
**Versión BD:** 1.0