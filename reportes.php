<?php
session_start();

// Verificar si el usuario está logueado
if (!isset($_SESSION['usuario'])) {
    header("Location: index.php");
    exit();
}

$titulo_pagina = "Reportes";
require_once 'layout.php';
require_once 'conexion.php';

$usuario_id = $_SESSION['usuario_id'];

// Obtener datos para reportes
$sql_general = "SELECT
    COUNT(*) as total_productos,
    SUM(cantidad) as total_unidades,
    SUM(precio * cantidad) as valor_total,
    AVG(precio) as precio_promedio,
    MIN(precio) as precio_minimo,
    MAX(precio) as precio_maximo
FROM inventario WHERE usuario_id = ?";

$stmt = $conexion->prepare($sql_general);
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$datos_general = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Productos por categoría
$sql_categorias = "SELECT
    categoria,
    COUNT(*) as productos,
    SUM(cantidad) as unidades,
    SUM(precio * cantidad) as valor
FROM inventario
WHERE usuario_id = ? AND categoria != ''
GROUP BY categoria
ORDER BY valor DESC";

$stmt = $conexion->prepare($sql_categorias);
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$reporte_categorias = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Productos con stock bajo
$sql_stock_bajo = "SELECT codigo, nombre, cantidad, precio
FROM inventario
WHERE usuario_id = ? AND cantidad > 0 AND cantidad < 5
ORDER BY cantidad ASC";

$stmt = $conexion->prepare($sql_stock_bajo);
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$stock_bajo = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Productos sin stock
$sql_sin_stock = "SELECT codigo, nombre, precio
FROM inventario
WHERE usuario_id = ? AND cantidad = 0
ORDER BY nombre";

$stmt = $conexion->prepare($sql_sin_stock);
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$sin_stock = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Productos más vendidos (simulado por cantidad baja - podrían ser los más solicitados)
$sql_mas_vendidos = "SELECT codigo, nombre, cantidad, precio, (precio * cantidad) as valor_inventario
FROM inventario
WHERE usuario_id = ?
ORDER BY cantidad ASC
LIMIT 10";

$stmt = $conexion->prepare($sql_mas_vendidos);
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$mas_vendidos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$conexion->close();
?>

<!-- Resumen General -->
<div class="stats-cards">
    <div class="card">
        <div class="card-icon blue">
            <i class="fas fa-boxes"></i>
        </div>
        <div class="card-title">Total Productos</div>
        <div class="card-value"><?php echo number_format($datos_general['total_productos']); ?></div>
    </div>

    <div class="card">
        <div class="card-icon green">
            <i class="fas fa-cubes"></i>
        </div>
        <div class="card-title">Total Unidades</div>
        <div class="card-value"><?php echo number_format($datos_general['total_unidades']); ?></div>
    </div>

    <div class="card">
        <div class="card-icon orange">
            <i class="fas fa-dollar-sign"></i>
        </div>
        <div class="card-title">Valor Total Inventario</div>
        <div class="card-value">$<?php echo number_format($datos_general['valor_total'], 2); ?></div>
    </div>

    <div class="card">
        <div class="card-icon red">
            <i class="fas fa-chart-line"></i>
        </div>
        <div class="card-title">Precio Promedio</div>
        <div class="card-value">$<?php echo number_format($datos_general['precio_promedio'], 2); ?></div>
    </div>
</div>

<!-- Reporte por Categorías -->
<div class="table-container">
    <div class="table-header">
        <h3 class="table-title">Valor por Categoría</h3>
        <div class="table-actions">
            <button onclick="exportarReporte('categorias')" class="btn btn-primary">
                <i class="fas fa-download"></i> Exportar
            </button>
        </div>
    </div>

    <?php if (count($reporte_categorias) > 0): ?>
        <table>
            <thead>
                <tr>
                    <th>Categoría</th>
                    <th>Productos</th>
                    <th>Unidades</th>
                    <th>Valor Total</th>
                    <th>% del Total</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($reporte_categorias as $cat): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($cat['categoria']); ?></td>
                        <td><?php echo number_format($cat['productos']); ?></td>
                        <td><?php echo number_format($cat['unidades']); ?></td>
                        <td>$<?php echo number_format($cat['valor'], 2); ?></td>
                        <td><?php echo $datos_general['valor_total'] > 0 ? number_format(($cat['valor'] / $datos_general['valor_total']) * 100, 1) : 0; ?>%</td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div class="empty-state">
            <i class="fas fa-chart-pie"></i>
            <p>No hay datos de categorías para mostrar</p>
        </div>
    <?php endif; ?>
</div>

<!-- Productos con Stock Bajo -->
<div class="table-container">
    <div class="table-header">
        <h3 class="table-title">Productos con Stock Bajo (< 5 unidades)</h3>
        <div class="table-actions">
            <span class="badge" style="background: #ffc107; color: #212529; padding: 5px 10px; border-radius: 4px;">
                <?php echo count($stock_bajo); ?> productos
            </span>
        </div>
    </div>

    <?php if (count($stock_bajo) > 0): ?>
        <table>
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Producto</th>
                    <th>Stock Actual</th>
                    <th>Precio</th>
                    <th>Valor</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($stock_bajo as $producto): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($producto['codigo']); ?></td>
                        <td><?php echo htmlspecialchars($producto['nombre']); ?></td>
                        <td><span style="color: #dc3545; font-weight: bold;"><?php echo $producto['cantidad']; ?></span></td>
                        <td>$<?php echo number_format($producto['precio'], 2); ?></td>
                        <td>$<?php echo number_format($producto['precio'] * $producto['cantidad'], 2); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div class="empty-state">
            <i class="fas fa-check-circle"></i>
            <p>¡Excelente! Todos los productos tienen stock suficiente</p>
        </div>
    <?php endif; ?>
</div>

<!-- Productos Sin Stock -->
<div class="table-container">
    <div class="table-header">
        <h3 class="table-title">Productos Agotados</h3>
        <div class="table-actions">
            <span class="badge" style="background: #dc3545; color: white; padding: 5px 10px; border-radius: 4px;">
                <?php echo count($sin_stock); ?> productos
            </span>
        </div>
    </div>

    <?php if (count($sin_stock) > 0): ?>
        <table>
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Producto</th>
                    <th>Precio</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($sin_stock as $producto): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($producto['codigo']); ?></td>
                        <td><?php echo htmlspecialchars($producto['nombre']); ?></td>
                        <td>$<?php echo number_format($producto['precio'], 2); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div class="empty-state">
            <i class="fas fa-smile"></i>
            <p>No hay productos agotados</p>
        </div>
    <?php endif; ?>
</div>

<!-- Estadísticas Adicionales -->
<div class="form-section">
    <h3>Estadísticas del Inventario</h3>
    <div class="stats-cards">
        <div class="card">
            <div class="card-icon blue">
                <i class="fas fa-arrow-up"></i>
            </div>
            <div class="card-title">Precio Máximo</div>
            <div class="card-value">$<?php echo number_format($datos_general['precio_maximo'], 2); ?></div>
        </div>

        <div class="card">
            <div class="card-icon green">
                <i class="fas fa-arrow-down"></i>
            </div>
            <div class="card-title">Precio Mínimo</div>
            <div class="card-value">$<?php echo number_format($datos_general['precio_minimo'], 2); ?></div>
        </div>

        <div class="card">
            <div class="card-icon orange">
                <i class="fas fa-tags"></i>
            </div>
            <div class="card-title">Categorías Activas</div>
            <div class="card-value"><?php echo count($reporte_categorias); ?></div>
        </div>

        <div class="card">
            <div class="card-icon red">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <div class="card-title">Alertas Activas</div>
            <div class="card-value"><?php echo count($stock_bajo) + count($sin_stock); ?></div>
        </div>
    </div>
</div>

<script>
function exportarReporte(tipo) {
    // Función básica de exportación (podría expandirse)
    alert('Función de exportación próximamente disponible');
}
</script>

<?php include 'layout_footer.php'; ?>