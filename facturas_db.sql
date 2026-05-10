-- =========================================
-- SISTEMA DE FACTURACIÓN POS - BASE DE DATOS
-- =========================================
-- Archivo: facturas_db.sql
-- Fecha: 14 de enero de 2026
-- Descripción: Estructura completa para el sistema de facturación

USE inventario_db;

-- =========================================
-- TABLA: facturas
-- Descripción: Almacena la información principal de cada factura
-- =========================================
CREATE TABLE IF NOT EXISTS facturas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    numero_factura VARCHAR(20) UNIQUE NOT NULL COMMENT 'Número único de factura (ej: FAC-20240114-0001)',
    cliente_nombre VARCHAR(100) COMMENT 'Nombre del cliente',
    cliente_documento VARCHAR(20) COMMENT 'Documento/ID del cliente',
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha y hora de creación',
    total DECIMAL(10,2) NOT NULL DEFAULT 0 COMMENT 'Total final de la factura',
    impuesto DECIMAL(10,2) DEFAULT 0 COMMENT 'Porcentaje o monto de impuesto aplicado',
    descuento DECIMAL(10,2) DEFAULT 0 COMMENT 'Monto de descuento aplicado',
    metodo_pago ENUM('efectivo', 'tarjeta_credito', 'tarjeta_debito', 'transferencia', 'cheque', 'otro')
        DEFAULT 'efectivo' COMMENT 'Método de pago utilizado',
    estado ENUM('pendiente', 'pagada', 'cancelada')
        DEFAULT 'pendiente' COMMENT 'Estado actual de la factura',
    usuario_id INT NOT NULL COMMENT 'ID del usuario que creó la factura',

    -- Llaves foráneas
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,

    -- Índices para optimización
    INDEX idx_numero_factura (numero_factura),
    INDEX idx_fecha_creacion (fecha_creacion),
    INDEX idx_estado (estado),
    INDEX idx_usuario (usuario_id),
    INDEX idx_metodo_pago (metodo_pago)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Tabla principal de facturas del sistema POS';

-- =========================================
-- TABLA: factura_detalles
-- Descripción: Detalles de productos incluidos en cada factura
-- =========================================
CREATE TABLE IF NOT EXISTS factura_detalles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    factura_id INT NOT NULL COMMENT 'ID de la factura padre',
    producto_id INT NOT NULL COMMENT 'ID del producto vendido',
    cantidad INT NOT NULL COMMENT 'Cantidad vendida del producto',
    precio_unitario DECIMAL(10,2) NOT NULL COMMENT 'Precio unitario al momento de la venta',
    subtotal DECIMAL(10,2) NOT NULL COMMENT 'Subtotal (cantidad * precio_unitario)',

    -- Llaves foráneas
    FOREIGN KEY (factura_id) REFERENCES facturas(id) ON DELETE CASCADE,
    FOREIGN KEY (producto_id) REFERENCES inventario(id) ON DELETE CASCADE,

    -- Índices para optimización
    INDEX idx_factura (factura_id),
    INDEX idx_producto (producto_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Detalles de productos por factura en el sistema POS';

-- =========================================
-- DATOS DE EJEMPLO (Opcional)
-- =========================================
-- Insertar datos de ejemplo solo si las tablas están vacías

-- Verificar si hay facturas existentes
SET @facturas_count = (SELECT COUNT(*) FROM facturas);

-- Si no hay facturas, insertar datos de ejemplo
INSERT IGNORE INTO facturas (numero_factura, cliente_nombre, cliente_documento, total, impuesto, descuento, metodo_pago, estado, usuario_id)
SELECT
    'FAC-20240114-0001',
    'Juan Pérez',
    '12345678',
    150.00,
    15.00,
    0.00,
    'efectivo',
    'pagada',
    id
FROM usuarios
WHERE usuario = 'admin'
LIMIT 1;

-- Insertar detalles de la factura de ejemplo
INSERT IGNORE INTO factura_detalles (factura_id, producto_id, cantidad, precio_unitario, subtotal)
SELECT
    (SELECT id FROM facturas WHERE numero_factura = 'FAC-20240114-0001' LIMIT 1),
    i.id,
    2,
    i.precio,
    (2 * i.precio)
FROM inventario i
WHERE i.nombre LIKE '%Producto%'
LIMIT 1;

-- =========================================
-- VISTAS ÚTILES (Opcional)
-- =========================================

-- Vista para consultar facturas con detalles del cliente y usuario
CREATE OR REPLACE VIEW vista_facturas_completas AS
SELECT
    f.id,
    f.numero_factura,
    f.cliente_nombre,
    f.cliente_documento,
    f.fecha_creacion,
    f.total,
    f.impuesto,
    f.descuento,
    f.metodo_pago,
    f.estado,
    u.nombre as usuario_nombre,
    u.usuario as usuario_login,
    COUNT(fd.id) as total_productos,
    SUM(fd.cantidad) as total_unidades
FROM facturas f
JOIN usuarios u ON f.usuario_id = u.id
LEFT JOIN factura_detalles fd ON f.id = fd.factura_id
GROUP BY f.id, f.numero_factura, f.cliente_nombre, f.cliente_documento,
         f.fecha_creacion, f.total, f.impuesto, f.descuento, f.metodo_pago,
         f.estado, u.nombre, u.usuario;

-- Vista para estadísticas de ventas por método de pago
CREATE OR REPLACE VIEW vista_ventas_por_metodo AS
SELECT
    metodo_pago,
    COUNT(*) as total_facturas,
    SUM(total) as total_ventas,
    AVG(total) as promedio_factura,
    MIN(total) as venta_minima,
    MAX(total) as venta_maxima
FROM facturas
WHERE estado = 'pagada'
GROUP BY metodo_pago
ORDER BY total_ventas DESC;

-- Vista para productos más vendidos
CREATE OR REPLACE VIEW vista_productos_mas_vendidos AS
SELECT
    i.nombre as producto_nombre,
    i.codigo as producto_codigo,
    SUM(fd.cantidad) as total_vendido,
    SUM(fd.subtotal) as total_ingresos,
    COUNT(DISTINCT fd.factura_id) as facturas_donde_aparece,
    AVG(fd.precio_unitario) as precio_promedio
FROM factura_detalles fd
JOIN inventario i ON fd.producto_id = i.id
JOIN facturas f ON fd.factura_id = f.id
WHERE f.estado = 'pagada'
GROUP BY i.id, i.nombre, i.codigo
ORDER BY total_vendido DESC;

-- =========================================
-- PROCEDIMIENTOS ALMACENADOS (Opcional)
-- =========================================

-- Procedimiento para crear una nueva factura
DELIMITER //

CREATE PROCEDURE IF NOT EXISTS sp_crear_factura(
    IN p_cliente_nombre VARCHAR(100),
    IN p_cliente_documento VARCHAR(20),
    IN p_impuesto DECIMAL(10,2),
    IN p_descuento DECIMAL(10,2),
    IN p_metodo_pago VARCHAR(20),
    IN p_usuario_id INT,
    OUT p_factura_id INT,
    OUT p_numero_factura VARCHAR(20)
)
BEGIN
    -- Generar número de factura único
    SET p_numero_factura = CONCAT('FAC-', DATE_FORMAT(NOW(), '%Y%m%d'), '-', LPAD(FLOOR(RAND() * 9999) + 1, 4, '0'));

    -- Insertar factura
    INSERT INTO facturas (
        numero_factura, cliente_nombre, cliente_documento,
        impuesto, descuento, metodo_pago, usuario_id
    ) VALUES (
        p_numero_factura, p_cliente_nombre, p_cliente_documento,
        p_impuesto, p_descuento, p_metodo_pago, p_usuario_id
    );

    SET p_factura_id = LAST_INSERT_ID();
END //

-- Procedimiento para agregar producto a factura
CREATE PROCEDURE IF NOT EXISTS sp_agregar_producto_factura(
    IN p_factura_id INT,
    IN p_producto_id INT,
    IN p_cantidad INT
)
BEGIN
    DECLARE v_precio DECIMAL(10,2);
    DECLARE v_subtotal DECIMAL(10,2);

    -- Obtener precio del producto
    SELECT precio INTO v_precio
    FROM inventario
    WHERE id = p_producto_id AND activo = 1;

    IF v_precio IS NOT NULL THEN
        SET v_subtotal = v_precio * p_cantidad;

        -- Insertar detalle
        INSERT INTO factura_detalles (
            factura_id, producto_id, cantidad, precio_unitario, subtotal
        ) VALUES (
            p_factura_id, p_producto_id, p_cantidad, v_precio, v_subtotal
        );

        -- Actualizar total de la factura
        UPDATE facturas
        SET total = (
            SELECT SUM(subtotal) FROM factura_detalles WHERE factura_id = p_factura_id
        ) - descuento + ((SELECT SUM(subtotal) FROM factura_detalles WHERE factura_id = p_factura_id) - descuento) * (impuesto / 100)
        WHERE id = p_factura_id;
    END IF;
END //

DELIMITER ;

-- =========================================
-- PERMISOS Y OPTIMIZACIONES
-- =========================================

-- Asegurar que las tablas usen InnoDB
ALTER TABLE facturas ENGINE = InnoDB;
ALTER TABLE factura_detalles ENGINE = InnoDB;

-- =========================================
-- MENSAJE DE CONFIRMACIÓN
-- =========================================

SELECT
    'Base de datos de facturación instalada correctamente' as mensaje,
    COUNT(*) as tablas_facturacion
FROM information_schema.tables
WHERE table_schema = 'inventario_db'
AND table_name IN ('facturas', 'factura_detalles');