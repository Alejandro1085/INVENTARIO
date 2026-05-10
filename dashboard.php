<?php
session_start();

// Verificar si el usuario está logueado
if (!isset($_SESSION['usuario'])) {
    header("Location: index.php");
    exit();
}

$titulo_pagina = "Dashboard";
require_once 'layout.php';
require_once 'conexion.php';

$usuario_id = $_SESSION['usuario_id'];

// Obtener estadísticas
$sql_stats = "SELECT
    COUNT(*) as total_productos,
    SUM(cantidad) as total_cantidad,
    SUM(precio * cantidad) as valor_total,
    COUNT(DISTINCT categoria) as total_categorias
FROM inventario WHERE usuario_id = ?";

$stmt = $conexion->prepare($sql_stats);
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Productos con stock bajo (menos de 5 unidades)
$sql_stock_bajo = "SELECT COUNT(*) as productos_stock_bajo FROM inventario
                   WHERE usuario_id = ? AND cantidad < 5 AND cantidad > 0";
$stmt = $conexion->prepare($sql_stock_bajo);
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$stock_bajo = $stmt->get_result()->fetch_assoc()['productos_stock_bajo'];
$stmt->close();

// Productos sin stock
$sql_sin_stock = "SELECT COUNT(*) as productos_sin_stock FROM inventario
                  WHERE usuario_id = ? AND cantidad = 0";
$stmt = $conexion->prepare($sql_sin_stock);
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$sin_stock = $stmt->get_result()->fetch_assoc()['productos_sin_stock'];
$stmt->close();

// Productos recientes (últimos 7 días)
$sql_recientes = "SELECT COUNT(*) as productos_recientes FROM inventario
                  WHERE usuario_id = ? AND fecha_creacion >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
$stmt = $conexion->prepare($sql_recientes);
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$recientes = $stmt->get_result()->fetch_assoc()['productos_recientes'];
$stmt->close();

// Productos más caros
$sql_caros = "SELECT nombre, precio FROM inventario
              WHERE usuario_id = ? ORDER BY precio DESC LIMIT 5";
$stmt = $conexion->prepare($sql_caros);
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$productos_caros = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$conexion->close();
?>

<!-- Statistics Cards -->
<div class="stats-cards">
    <div class="card">
        <div class="card-icon blue">
            <i class="fas fa-boxes"></i>
        </div>
        <div class="card-title">Total Productos</div>
        <div class="card-value"><?php echo number_format($stats['total_productos']); ?></div>
    </div>

    <div class="card">
        <div class="card-icon green">
            <i class="fas fa-cubes"></i>
        </div>
        <div class="card-title">Total Unidades</div>
        <div class="card-value"><?php echo number_format($stats['total_cantidad']); ?></div>
    </div>

    <div class="card">
        <div class="card-icon orange">
            <i class="fas fa-dollar-sign"></i>
        </div>
        <div class="card-title">Valor Total</div>
        <div class="card-value">$<?php echo number_format($stats['valor_total'], 2); ?></div>
    </div>

    <div class="card">
        <div class="card-icon red">
            <i class="fas fa-tags"></i>
        </div>
        <div class="card-title">Categorías</div>
        <div class="card-value"><?php echo number_format($stats['total_categorias']); ?></div>
    </div>
</div>

<!-- Alert Cards -->
<div class="stats-cards">
    <div class="card">
        <div class="card-icon orange">
            <i class="fas fa-exclamation-triangle"></i>
        </div>
        <div class="card-title">Stock Bajo</div>
        <div class="card-value"><?php echo number_format($stock_bajo); ?></div>
        <small style="color: #6c757d; margin-top: 5px;">Menos de 5 unidades</small>
    </div>

    <div class="card">
        <div class="card-icon red">
            <i class="fas fa-times-circle"></i>
        </div>
        <div class="card-title">Sin Stock</div>
        <div class="card-value"><?php echo number_format($sin_stock); ?></div>
        <small style="color: #6c757d; margin-top: 5px;">Agotados</small>
    </div>

    <div class="card">
        <div class="card-icon green">
            <i class="fas fa-plus-circle"></i>
        </div>
        <div class="card-title">Agregados Recientemente</div>
        <div class="card-value"><?php echo number_format($recientes); ?></div>
        <small style="color: #6c757d; margin-top: 5px;">Últimos 7 días</small>
    </div>

    <div class="card">
        <div class="card-icon blue">
            <i class="fas fa-star"></i>
        </div>
        <div class="card-title">Productos Premium</div>
        <div class="card-value"><?php echo count($productos_caros); ?></div>
        <small style="color: #6c757d; margin-top: 5px;">Más caros</small>
    </div>
</div>

<!-- Recent Products Table -->
<div class="table-container">
    <div class="table-header">
        <h3 class="table-title">Productos Más Caros</h3>
        <div class="table-actions">
            <a href="inventario.php" class="btn btn-primary">
                <i class="fas fa-eye"></i> Ver Todos
            </a>
        </div>
    </div>

    <?php if (count($productos_caros) > 0): ?>
        <table>
            <thead>
                <tr>
                    <th>Producto</th>
                    <th>Precio</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($productos_caros as $producto): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($producto['nombre']); ?></td>
                        <td>$<?php echo number_format($producto['precio'], 2); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div class="empty-state">
            <i class="fas fa-box-open"></i>
            <p>No hay productos registrados aún</p>
            <a href="agregar_producto.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> Agregar Primer Producto
            </a>
        </div>
    <?php endif; ?>
</div>

<!-- Quick Actions -->
<div class="form-section">
    <h3>Acciones Rápidas</h3>
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
        <a href="agregar_producto.php" class="btn btn-success" style="padding: 15px; text-align: center; display: flex; align-items: center; justify-content: center; gap: 10px;">
            <i class="fas fa-plus-circle"></i>
            Agregar Producto
        </a>
        <a href="inventario.php" class="btn btn-primary" style="padding: 15px; text-align: center; display: flex; align-items: center; justify-content: center; gap: 10px;">
            <i class="fas fa-boxes"></i>
            Ver Inventario
        </a>
        <a href="categorias.php" class="btn btn-warning" style="padding: 15px; text-align: center; display: flex; align-items: center; justify-content: center; gap: 10px;">
            <i class="fas fa-tags"></i>
            Gestionar Categorías
        </a>
        <a href="reportes.php" class="btn btn-info" style="padding: 15px; text-align: center; display: flex; align-items: center; justify-content: center; gap: 10px;">
            <i class="fas fa-chart-bar"></i>
            Ver Reportes
        </a>
    </div>
</div>

<?php include 'layout_footer.php'; ?>