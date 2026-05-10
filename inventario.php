<?php
session_start();

// Verificar si el usuario está logueado
if (!isset($_SESSION['usuario'])) {
    header("Location: index.php");
    exit();
}

$titulo_pagina = "Ver Inventario";
require_once 'layout.php';
require_once 'conexion.php';

$usuario_id = $_SESSION['usuario_id'];
$mensaje = "";
$tipo_mensaje = "";

// Determinar permisos
$es_admin = ($_SESSION['usuario'] === 'admin');

// Procesar búsqueda
$busqueda = isset($_GET['busqueda']) ? trim($_GET['busqueda']) : '';
$categoria_filtro = isset($_GET['categoria']) ? trim($_GET['categoria']) : '';

// Procesar eliminación de producto
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['accion'])) {
    if ($_POST['accion'] == 'eliminar') {
        $id = intval($_POST['id']);
        
        // Verificar permisos para eliminar
        $sql_check = "SELECT usuario_id FROM inventario WHERE id = ?";
        $stmt_check = $conexion->prepare($sql_check);
        $stmt_check->bind_param("i", $id);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();
        
        if ($result_check->num_rows > 0) {
            $producto = $result_check->fetch_assoc();
            
            if ($es_admin || $producto['usuario_id'] == $usuario_id) {
                $sql = "DELETE FROM inventario WHERE id = ?";
                $stmt = $conexion->prepare($sql);
                $stmt->bind_param("i", $id);
                
                if ($stmt->execute()) {
                    $mensaje = "✓ Producto eliminado";
                    $tipo_mensaje = "success";
                } else {
                    $mensaje = "Error al eliminar el producto";
                    $tipo_mensaje = "error";
                }
                $stmt->close();
            } else {
                $mensaje = "No tienes permisos para eliminar este producto";
                $tipo_mensaje = "error";
            }
        } else {
            $mensaje = "Producto no encontrado";
            $tipo_mensaje = "error";
        }
        $stmt_check->close();
    }
}

// Construir consulta para obtener productos
$sql_base = "SELECT i.id, i.codigo, i.nombre, i.descripcion, i.cantidad, i.precio, i.categoria, 
                    i.fecha_creacion, u.nombre as usuario_nombre
             FROM inventario i 
             JOIN usuarios u ON i.usuario_id = u.id 
             WHERE i.activo = 1";

$parametros = [];
$tipos = "";

// Filtros de búsqueda
if (!empty($busqueda)) {
    $sql_base .= " AND (i.nombre LIKE ? OR i.codigo LIKE ? OR i.descripcion LIKE ?)";
    $parametros[] = "%$busqueda%";
    $parametros[] = "%$busqueda%";
    $parametros[] = "%$busqueda%";
    $tipos .= "sss";
}

if (!empty($categoria_filtro)) {
    $sql_base .= " AND i.categoria LIKE ?";
    $parametros[] = "%$categoria_filtro%";
    $tipos .= "s";
}

// Si no es admin, solo mostrar sus productos
if (!$es_admin) {
    $sql_base .= " AND i.usuario_id = ?";
    $parametros[] = $usuario_id;
    $tipos .= "i";
}

$sql_base .= " ORDER BY i.fecha_creacion DESC";

$stmt = $conexion->prepare($sql_base);
if (!empty($parametros)) {
    $stmt->bind_param($tipos, ...$parametros);
}
$stmt->execute();
$resultado = $stmt->get_result();
$productos = $resultado->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Calcular totales
$total_productos = count($productos);
$valor_total = 0;
foreach ($productos as $producto) {
    $valor_total += ($producto['precio'] * $producto['cantidad']);
}

// Obtener categorías únicas para el filtro
$sql_categorias = "SELECT DISTINCT categoria FROM inventario WHERE categoria IS NOT NULL AND categoria != '' ORDER BY categoria";
$resultado_categorias = $conexion->query($sql_categorias);
$categorias = $resultado_categorias->fetch_all(MYSQLI_ASSOC);
?>

<?php if (!empty($mensaje)): ?>
    <div class="alert alert-<?php echo $tipo_mensaje; ?>">
        <?php echo htmlspecialchars($mensaje); ?>
    </div>
<?php endif; ?>

<div class="content-wrapper">
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon">📦</div>
            <div class="stat-content">
                <h3><?php echo $total_productos; ?></h3>
                <p>Total de Productos</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">💰</div>
            <div class="stat-content">
                <h3>$<?php echo number_format($valor_total, 2); ?></h3>
                <p>Valor Total</p>
            </div>
        </div>
    </div>

    <!-- Formulario de búsqueda -->
    <div class="form-section">
        <h3>Buscar Productos</h3>
        <form method="GET" class="form-grid">
            <div class="form-group">
                <label for="busqueda">Buscar por nombre, código o descripción</label>
                <input type="text" id="busqueda" name="busqueda" 
                       value="<?php echo htmlspecialchars($busqueda); ?>" 
                       placeholder="Escribe para buscar...">
            </div>

            <div class="form-group">
                <label for="categoria">Filtrar por categoría</label>
                <select id="categoria" name="categoria">
                    <option value="">Todas las categorías</option>
                    <?php foreach ($categorias as $cat): ?>
                        <option value="<?php echo htmlspecialchars($cat['categoria']); ?>" 
                                <?php echo ($categoria_filtro === $cat['categoria']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cat['categoria']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search"></i> Buscar
                </button>
                <?php if (!empty($busqueda) || !empty($categoria_filtro)): ?>
                    <a href="inventario.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Limpiar Filtros
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <div class="table-container">
        <div class="table-header">
            <h3 class="table-title">
                Productos en Inventario
                <?php if (!empty($busqueda) || !empty($categoria_filtro)): ?>
                    <span style="font-size: 14px; color: #666; font-weight: normal;">
                        (<?php echo $total_productos; ?> resultado<?php echo $total_productos !== 1 ? 's' : ''; ?> encontrado<?php echo $total_productos !== 1 ? 's' : ''; ?>)
                    </span>
                <?php endif; ?>
            </h3>
        </div>

        <?php if (count($productos) > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Nombre</th>
                        <th>Categoría</th>
                        <th>Cantidad</th>
                        <th>Precio</th>
                        <th>Subtotal</th>
                        <?php if ($es_admin): ?>
                            <th>Usuario</th>
                        <?php endif; ?>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($productos as $producto): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($producto['codigo']); ?></td>
                            <td><?php echo htmlspecialchars($producto['nombre']); ?></td>
                            <td><?php echo htmlspecialchars($producto['categoria'] ?: 'N/A'); ?></td>
                            <td><?php echo intval($producto['cantidad']); ?></td>
                            <td>$<?php echo number_format($producto['precio'], 2); ?></td>
                            <td>$<?php echo number_format($producto['cantidad'] * $producto['precio'], 2); ?></td>
                            <?php if ($es_admin): ?>
                                <td><?php echo htmlspecialchars($producto['usuario_nombre']); ?></td>
                            <?php endif; ?>
                            <td>
                                <div class="actions">
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="accion" value="eliminar">
                                        <input type="hidden" name="id" value="<?php echo $producto['id']; ?>">
                                        <button type="submit" class="btn btn-danger btn-sm"
                                                onclick="return confirm('¿Estás seguro de eliminar \'<?php echo htmlspecialchars($producto['nombre']); ?>\'?')">
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
                <div class="empty-icon">📭</div>
                <h4>No hay productos</h4>
                <p><?php echo !empty($busqueda) || !empty($categoria_filtro) ? 'No se encontraron productos con los filtros aplicados.' : 'Agrega tu primer producto usando el formulario de arriba.'; ?></p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include 'layout_footer.php'; ?>

<?php
$conexion->close();
?>
