<?php
session_start();

// Verificar si el usuario está logueado (esto ya lo hace layout.php, pero por consistencia)
if (!isset($_SESSION['usuario'])) {
    header("Location: index.php");
    exit();
}

// Verificar si se proporcionó un ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: inventario.php");
    exit();
}

$titulo_pagina = "Editar Producto";
require_once 'layout.php';
require_once 'conexion.php';

$usuario_id = $_SESSION['usuario_id'];
$mensaje = "";
$tipo_mensaje = "";

$id = intval($_GET['id']);

// Obtener datos del producto
$sql = "SELECT id, codigo, nombre, descripcion, cantidad, precio, categoria FROM inventario WHERE id = ? AND usuario_id = ?";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("ii", $id, $usuario_id);
$stmt->execute();
$resultado = $stmt->get_result();

if ($resultado->num_rows == 0) {
    header("Location: inventario.php");
    exit();
}

$producto = $resultado->fetch_assoc();
$stmt->close();

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
        // Verificar si el código ya existe (excluyendo el producto actual)
        $sql_check = "SELECT id FROM inventario WHERE codigo = ? AND usuario_id = ? AND id != ?";
        $stmt_check = $conexion->prepare($sql_check);
        $stmt_check->bind_param("sii", $codigo, $usuario_id, $id);
        $stmt_check->execute();
        $resultado_check = $stmt_check->get_result();

        if ($resultado_check->num_rows > 0) {
            $mensaje = "El código del producto ya existe";
            $tipo_mensaje = "error";
        } else {
            $sql_update = "UPDATE inventario SET codigo = ?, nombre = ?, descripcion = ?, cantidad = ?, precio = ?, categoria = ? WHERE id = ? AND usuario_id = ?";
            $stmt_update = $conexion->prepare($sql_update);
            $stmt_update->bind_param("sssidssi", $codigo, $nombre, $descripcion, $cantidad, $precio, $categoria, $id, $usuario_id);

            if ($stmt_update->execute()) {
                $mensaje = "✓ Producto actualizado correctamente";
                $tipo_mensaje = "success";

                // Recargar datos del producto
                $sql_reload = "SELECT id, codigo, nombre, descripcion, cantidad, precio, categoria FROM inventario WHERE id = ? AND usuario_id = ?";
                $stmt_reload = $conexion->prepare($sql_reload);
                $stmt_reload->bind_param("ii", $id, $usuario_id);
                $stmt_reload->execute();
                $producto = $stmt_reload->get_result()->fetch_assoc();
                $stmt_reload->close();
            } else {
                $mensaje = "Error al actualizar el producto: " . htmlspecialchars($conexion->error);
                $tipo_mensaje = "error";
            }
            $stmt_update->close();
        }
        $stmt_check->close();
    }
}

// Obtener categorías para el select
$sql_categorias = "SELECT DISTINCT categoria FROM inventario WHERE usuario_id = ? AND categoria != '' ORDER BY categoria";
$stmt_cat = $conexion->prepare($sql_categorias);
$stmt_cat->bind_param("i", $usuario_id);
$stmt_cat->execute();
$categorias = $stmt_cat->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_cat->close();

$conexion->close();
?>

<div class="form-section">
    <h3>Editar Producto: <?php echo htmlspecialchars($producto['nombre']); ?></h3>

    <form method="POST" class="form-grid">
        <div class="form-group">
            <label for="codigo">Código del Producto *</label>
            <input type="text" id="codigo" name="codigo" value="<?php echo htmlspecialchars($producto['codigo']); ?>" required>
        </div>

        <div class="form-group">
            <label for="nombre">Nombre del Producto *</label>
            <input type="text" id="nombre" name="nombre" value="<?php echo htmlspecialchars($producto['nombre']); ?>" required>
        </div>

        <div class="form-group">
            <label for="cantidad">Cantidad</label>
            <input type="number" id="cantidad" name="cantidad" value="<?php echo intval($producto['cantidad']); ?>" min="0">
        </div>

        <div class="form-group">
            <label for="precio">Precio</label>
            <input type="number" id="precio" name="precio" step="0.01" value="<?php echo number_format($producto['precio'], 2, '.', ''); ?>" min="0">
        </div>

        <div class="form-group">
            <label for="categoria">Categoría</label>
            <select id="categoria" name="categoria">
                <option value="">Seleccionar categoría</option>
                <?php foreach ($categorias as $cat): ?>
                    <option value="<?php echo htmlspecialchars($cat['categoria']); ?>"
                            <?php echo $producto['categoria'] === $cat['categoria'] ? 'selected' : ''; ?>>
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
            <textarea id="descripcion" name="descripcion" rows="4"><?php echo htmlspecialchars($producto['descripcion'] ?: ''); ?></textarea>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i> Actualizar Producto
            </button>
            <a href="inventario.php" class="btn btn-secondary">
                <i class="fas fa-times"></i> Cancelar
            </a>
        </div>
    </form>
</div>

<!-- Información adicional del producto -->
<div class="form-section">
    <h3>Información del Producto</h3>
    <div class="stats-cards">
        <div class="card">
            <div class="card-icon blue">
                <i class="fas fa-calendar"></i>
            </div>
            <div class="card-title">ID del Producto</div>
            <div class="card-value">#<?php echo $producto['id']; ?></div>
        </div>

        <div class="card">
            <div class="card-icon green">
                <i class="fas fa-dollar-sign"></i>
            </div>
            <div class="card-title">Valor en Inventario</div>
            <div class="card-value">$<?php echo number_format($producto['cantidad'] * $producto['precio'], 2); ?></div>
        </div>

        <div class="card">
            <div class="card-icon orange">
                <i class="fas fa-boxes"></i>
            </div>
            <div class="card-title">Estado del Stock</div>
            <div class="card-value">
                <?php if ($producto['cantidad'] == 0): ?>
                    <span style="color: #dc3545;">Agotado</span>
                <?php elseif ($producto['cantidad'] < 5): ?>
                    <span style="color: #ffc107;">Stock Bajo</span>
                <?php else: ?>
                    <span style="color: #28a745;">Disponible</span>
                <?php endif; ?>
            </div>
        </div>

        <div class="card">
            <div class="card-icon red">
                <i class="fas fa-tags"></i>
            </div>
            <div class="card-title">Categoría Actual</div>
            <div class="card-value"><?php echo htmlspecialchars($producto['categoria'] ?: 'Sin categoría'); ?></div>
        </div>
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