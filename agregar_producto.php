<?php
session_start();

// Verificar si el usuario está logueado
if (!isset($_SESSION['usuario'])) {
    header("Location: index.php");
    exit();
}

$titulo_pagina = "Agregar Producto";
require_once 'layout.php';
require_once 'conexion.php';

$usuario_id = $_SESSION['usuario_id'];
$mensaje = "";
$tipo_mensaje = "";

// Procesar parámetros de búsqueda
$busqueda = isset($_GET['busqueda']) ? trim($_GET['busqueda']) : '';
$categoria_filtro = isset($_GET['categoria']) ? trim($_GET['categoria']) : '';

// Procesar formulario
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $codigo = trim($_POST['codigo']);
    $nombre = trim($_POST['nombre']);
    $descripcion = trim($_POST['descripcion']);
    $cantidad = intval($_POST['cantidad']);
    $precio = floatval($_POST['precio']);
    $categoria = trim($_POST['categoria']);

    if (empty($codigo) || empty($nombre)) {
        $mensaje = "El código y nombre son obligatorios";
        $tipo_mensaje = "error";
    } else {
        // Verificar si el código ya existe
        $sql_check = "SELECT id FROM inventario WHERE codigo = ? AND usuario_id = ?";
        $stmt_check = $conexion->prepare($sql_check);
        $stmt_check->bind_param("si", $codigo, $usuario_id);
        $stmt_check->execute();
        $resultado_check = $stmt_check->get_result();

        if ($resultado_check->num_rows > 0) {
            $mensaje = "El código del producto ya existe";
            $tipo_mensaje = "error";
        } else {
            $sql = "INSERT INTO inventario (codigo, nombre, descripcion, cantidad, precio, categoria, usuario_id)
                    VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conexion->prepare($sql);
            $stmt->bind_param("sssidsi", $codigo, $nombre, $descripcion, $cantidad, $precio, $categoria, $usuario_id);

            if ($stmt->execute()) {
                $mensaje = "✓ Producto agregado correctamente";
                $tipo_mensaje = "success";
            } else {
                $mensaje = "Error al agregar el producto: " . htmlspecialchars($conexion->error);
                $tipo_mensaje = "error";
            }
            $stmt->close();
        }
        $stmt_check->close();
    }
}

// Obtener categorías únicas para el filtro
$sql_categorias_filtro = "SELECT DISTINCT categoria FROM inventario WHERE categoria IS NOT NULL AND categoria != '' ORDER BY categoria";
$resultado_categorias_filtro = $conexion->query($sql_categorias_filtro);
$categorias_filtro = $resultado_categorias_filtro->fetch_all(MYSQLI_ASSOC);

// Obtener categorías existentes para el select del formulario de agregar
$sql_categorias = "SELECT DISTINCT categoria FROM inventario WHERE usuario_id = ? AND categoria != '' ORDER BY categoria";
$stmt_cat = $conexion->prepare($sql_categorias);
$stmt_cat->bind_param("i", $usuario_id);
$stmt_cat->execute();
$categorias = $stmt_cat->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_cat->close();

// Verificar si es admin
$es_admin = (isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin');

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

$conexion->close();
?>

<div class="content-wrapper">
    <!-- Estadísticas -->
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
                    <?php foreach ($categorias_filtro as $cat): ?>
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
                    <a href="agregar_producto.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Limpiar Filtros
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- Formulario de agregar producto -->
    <div class="form-section">
        <h3>Agregar Nuevo Producto</h3>

    <form method="POST" class="form-grid">
        <div class="form-group">
            <label for="codigo">Código del Producto *</label>
            <input type="text" id="codigo" name="codigo" required autofocus>
        </div>

        <div class="form-group">
            <label for="nombre">Nombre del Producto *</label>
            <input type="text" id="nombre" name="nombre" required>
        </div>

        <div class="form-group">
            <label for="cantidad">Cantidad</label>
            <input type="number" id="cantidad" name="cantidad" value="0" min="0">
        </div>

        <div class="form-group">
            <label for="precio">Precio</label>
            <input type="number" id="precio" name="precio" step="0.01" value="0.00" min="0">
        </div>

        <div class="form-group">
            <label for="categoria">Categoría</label>
            <select id="categoria" name="categoria">
                <option value="">Seleccionar categoría</option>
                <?php foreach ($categorias as $cat): ?>
                    <option value="<?php echo htmlspecialchars($cat['categoria']); ?>">
                        <?php echo htmlspecialchars($cat['categoria']); ?>
                    </option>
                <?php endforeach; ?>
                <option value="nueva">+ Nueva categoría</option>
            </select>
        </div>

        <div class="form-group" id="nueva-categoria-group" style="display: none;">
            <label for="nueva_categoria">Nueva Categoría</label>
            <input type="text" id="nueva_categoria" name="nueva_categoria" placeholder="Ingresa nueva categoría">
        </div>

        <div class="form-group" style="grid-column: 1 / -1;">
            <label for="descripcion">Descripción</label>
            <textarea id="descripcion" name="descripcion" rows="4"></textarea>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i> Guardar Producto
            </button>
            <a href="inventario.php" class="btn btn-secondary">
                <i class="fas fa-times"></i> Cancelar
            </a>
        </div>
    </form>
</div>

<!-- Lista de productos existentes -->
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
                                <a href="editar_producto.php?id=<?php echo $producto['id']; ?>" class="btn btn-primary btn-sm">
                                    <i class="fas fa-edit"></i> Editar
                                </a>
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

<script>
// Mostrar campo de nueva categoría
document.getElementById('categoria').addEventListener('change', function() {
    const nuevaCategoriaGroup = document.getElementById('nueva-categoria-group');
    const nuevaCategoriaInput = document.getElementById('nueva_categoria');

    if (this.value === 'nueva') {
        nuevaCategoriaGroup.style.display = 'block';
        nuevaCategoriaInput.required = true;
        nuevaCategoriaInput.focus();
    } else {
        nuevaCategoriaGroup.style.display = 'none';
        nuevaCategoriaInput.required = false;
        nuevaCategoriaInput.value = '';
    }
});

// Procesar nueva categoría antes del envío
document.querySelector('form').addEventListener('submit', function(e) {
    const categoriaSelect = document.getElementById('categoria');
    const nuevaCategoriaInput = document.getElementById('nueva_categoria');

    if (categoriaSelect.value === 'nueva' && nuevaCategoriaInput.value.trim()) {
        categoriaSelect.value = nuevaCategoriaInput.value.trim();
    }
});
</script>

<?php include 'layout_footer.php'; ?>