<?php
session_start();

// Verificar si el usuario está logueado
if (!isset($_SESSION['usuario'])) {
    header("Location: index.php");
    exit();
}

$titulo_pagina = "Categorías";
require_once 'layout.php';
require_once 'conexion.php';

$usuario_id = $_SESSION['usuario_id'];
$mensaje = "";
$tipo_mensaje = "";

// Procesar agregar categoría
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['accion'])) {
    if ($_POST['accion'] == 'agregar_categoria') {
        $nombre_categoria = trim($_POST['nombre_categoria']);

        if (empty($nombre_categoria)) {
            $mensaje = "El nombre de la categoría es obligatorio";
            $tipo_mensaje = "error";
        } else {
            // Verificar si la categoría ya existe
            $sql_check = "SELECT COUNT(*) as count FROM inventario WHERE categoria = ? AND usuario_id = ?";
            $stmt_check = $conexion->prepare($sql_check);
            $stmt_check->bind_param("si", $nombre_categoria, $usuario_id);
            $stmt_check->execute();
            $result_check = $stmt_check->get_result()->fetch_assoc();

            if ($result_check['count'] > 0) {
                $mensaje = "Esta categoría ya existe";
                $tipo_mensaje = "warning";
            } else {
                $mensaje = "✓ Categoría agregada correctamente";
                $tipo_mensaje = "success";
            }
            $stmt_check->close();
        }
    } elseif ($_POST['accion'] == 'eliminar_categoria') {
        $categoria_eliminar = trim($_POST['categoria_eliminar']);

        // Verificar si hay productos en esta categoría
        $sql_count = "SELECT COUNT(*) as count FROM inventario WHERE categoria = ? AND usuario_id = ?";
        $stmt_count = $conexion->prepare($sql_count);
        $stmt_count->bind_param("si", $categoria_eliminar, $usuario_id);
        $stmt_count->execute();
        $result_count = $stmt_count->get_result()->fetch_assoc();

        if ($result_count['count'] > 0) {
            $mensaje = "No se puede eliminar la categoría porque tiene " . $result_count['count'] . " producto(s) asociado(s)";
            $tipo_mensaje = "error";
        } else {
            $mensaje = "✓ Categoría eliminada correctamente";
            $tipo_mensaje = "success";
        }
        $stmt_count->close();
    }
}

// Obtener estadísticas de categorías
$sql_categorias = "SELECT
    categoria,
    COUNT(*) as total_productos,
    SUM(cantidad) as total_cantidad,
    SUM(precio * cantidad) as valor_total,
    AVG(precio) as precio_promedio
FROM inventario
WHERE usuario_id = ? AND categoria != ''
GROUP BY categoria
ORDER BY total_productos DESC";

$stmt = $conexion->prepare($sql_categorias);
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$categorias = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Productos sin categoría
$sql_sin_categoria = "SELECT COUNT(*) as count FROM inventario WHERE usuario_id = ? AND (categoria = '' OR categoria IS NULL)";
$stmt_sin = $conexion->prepare($sql_sin_categoria);
$stmt_sin->bind_param("i", $usuario_id);
$stmt_sin->execute();
$sin_categoria = $stmt_sin->get_result()->fetch_assoc()['count'];
$stmt_sin->close();

$conexion->close();
?>

<div class="form-section">
    <h3>Agregar Nueva Categoría</h3>
    <form method="POST" style="max-width: 400px;">
        <input type="hidden" name="accion" value="agregar_categoria">
        <div class="form-group">
            <label for="nombre_categoria">Nombre de la Categoría</label>
            <input type="text" id="nombre_categoria" name="nombre_categoria" required>
        </div>
        <button type="submit" class="btn btn-success">
            <i class="fas fa-plus"></i> Agregar Categoría
        </button>
    </form>
</div>

<!-- Estadísticas de Categorías -->
<div class="table-container">
    <div class="table-header">
        <h3 class="table-title">Categorías y Estadísticas</h3>
    </div>

    <?php if (count($categorias) > 0): ?>
        <table>
            <thead>
                <tr>
                    <th>Categoría</th>
                    <th>Productos</th>
                    <th>Total Unidades</th>
                    <th>Valor Total</th>
                    <th>Precio Promedio</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($categorias as $categoria): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($categoria['categoria']); ?></td>
                        <td><?php echo number_format($categoria['total_productos']); ?></td>
                        <td><?php echo number_format($categoria['total_cantidad']); ?></td>
                        <td>$<?php echo number_format($categoria['valor_total'], 2); ?></td>
                        <td>$<?php echo number_format($categoria['precio_promedio'], 2); ?></td>
                        <td>
                            <div class="actions">
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="accion" value="eliminar_categoria">
                                    <input type="hidden" name="categoria_eliminar" value="<?php echo htmlspecialchars($categoria['categoria']); ?>">
                                    <button type="submit" class="btn btn-danger btn-sm"
                                            onclick="return confirm('¿Estás seguro de eliminar la categoría \'<?php echo htmlspecialchars($categoria['categoria']); ?>\'?')">
                                        <i class="fas fa-trash"></i> Eliminar
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div class="empty-state">
            <i class="fas fa-tags"></i>
            <p>No hay categorías registradas</p>
            <p style="font-size: 14px; color: #6c757d;">Agrega tu primera categoría arriba</p>
        </div>
    <?php endif; ?>
</div>

<?php if ($sin_categoria > 0): ?>
<div class="message warning">
    <i class="fas fa-exclamation-triangle"></i>
    Hay <?php echo $sin_categoria; ?> producto(s) sin categoría asignada.
    <a href="inventario.php" style="color: #856404; text-decoration: underline;">Ver productos</a>
</div>
<?php endif; ?>

<?php include 'layout_footer.php'; ?>