<?php
session_start();

// Solo admin puede acceder
if (!isset($_SESSION['usuario']) || $_SESSION['usuario'] !== 'admin') {
    header("Location: dashboard.php");
    exit();
}

$titulo_pagina = "Usuarios";
require_once 'layout.php';
require_once 'conexion.php';

$mensaje = "";
$tipo_mensaje = "";

// Procesar agregar usuario
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['accion'])) {
    if ($_POST['accion'] == 'agregar_usuario') {
        $usuario = trim($_POST['usuario']);
        $nombre = trim($_POST['nombre']);
        $email = trim($_POST['email']);
        $contrasena = trim($_POST['contrasena']);

        if (empty($usuario) || empty($nombre) || empty($contrasena)) {
            $mensaje = "Usuario, nombre y contraseña son obligatorios";
            $tipo_mensaje = "error";
        } else {
            // Verificar si el usuario ya existe
            $sql_check = "SELECT id FROM usuarios WHERE usuario = ?";
            $stmt_check = $conexion->prepare($sql_check);
            $stmt_check->bind_param("s", $usuario);
            $stmt_check->execute();
            $resultado_check = $stmt_check->get_result();

            if ($resultado_check->num_rows > 0) {
                $mensaje = "El usuario ya existe";
                $tipo_mensaje = "error";
            } else {
                $hash = password_hash($contrasena, PASSWORD_BCRYPT);
                $sql = "INSERT INTO usuarios (usuario, contrasena, nombre, email) VALUES (?, ?, ?, ?)";
                $stmt = $conexion->prepare($sql);
                $stmt->bind_param("ssss", $usuario, $hash, $nombre, $email);

                if ($stmt->execute()) {
                    $mensaje = "✓ Usuario agregado correctamente";
                    $tipo_mensaje = "success";
                } else {
                    $mensaje = "Error al agregar el usuario";
                    $tipo_mensaje = "error";
                }
                $stmt->close();
            }
            $stmt_check->close();
        }
    } elseif ($_POST['accion'] == 'eliminar_usuario') {
        $id_eliminar = intval($_POST['id_eliminar']);

        if ($id_eliminar == $_SESSION['usuario_id']) {
            $mensaje = "No puedes eliminar tu propio usuario";
            $tipo_mensaje = "error";
        } else {
            // Contar productos del usuario
            $sql_count = "SELECT COUNT(*) as count FROM inventario WHERE usuario_id = ?";
            $stmt_count = $conexion->prepare($sql_count);
            $stmt_count->bind_param("i", $id_eliminar);
            $stmt_count->execute();
            $result_count = $stmt_count->get_result()->fetch_assoc();

            if ($result_count['count'] > 0) {
                $mensaje = "No se puede eliminar el usuario porque tiene " . $result_count['count'] . " producto(s) registrado(s)";
                $tipo_mensaje = "error";
            } else {
                $sql = "DELETE FROM usuarios WHERE id = ?";
                $stmt = $conexion->prepare($sql);
                $stmt->bind_param("i", $id_eliminar);

                if ($stmt->execute()) {
                    $mensaje = "✓ Usuario eliminado correctamente";
                    $tipo_mensaje = "success";
                } else {
                    $mensaje = "Error al eliminar el usuario";
                    $tipo_mensaje = "error";
                }
                $stmt->close();
            }
            $stmt_count->close();
        }
    }
}

// Obtener todos los usuarios
$sql_usuarios = "SELECT id, usuario, nombre, email, fecha_creacion FROM usuarios ORDER BY fecha_creacion DESC";
$resultado = $conexion->query($sql_usuarios);
$usuarios = $resultado->fetch_all(MYSQLI_ASSOC);

// Obtener estadísticas de productos por usuario
$sql_stats = "SELECT u.usuario, u.nombre, COUNT(i.id) as total_productos,
                     SUM(i.cantidad) as total_unidades, SUM(i.precio * i.cantidad) as valor_total
              FROM usuarios u
              LEFT JOIN inventario i ON u.id = i.usuario_id
              GROUP BY u.id, u.usuario, u.nombre
              ORDER BY total_productos DESC";

$resultado_stats = $conexion->query($sql_stats);
$estadisticas_usuarios = $resultado_stats->fetch_all(MYSQLI_ASSOC);

$conexion->close();
?>

<?php if (!empty($mensaje)): ?>
    <div class="alert alert-<?php echo $tipo_mensaje; ?>">
        <?php echo htmlspecialchars($mensaje); ?>
    </div>
<?php endif; ?>

<div class="form-section">
    <h3>Agregar Nuevo Usuario</h3>
    <form method="POST" class="form-grid">
        <input type="hidden" name="accion" value="agregar_usuario">

        <div class="form-group">
            <label for="usuario">Usuario *</label>
            <input type="text" id="usuario" name="usuario" required>
        </div>

        <div class="form-group">
            <label for="nombre">Nombre Completo *</label>
            <input type="text" id="nombre" name="nombre" required>
        </div>

        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" id="email" name="email">
        </div>

        <div class="form-group">
            <label for="contrasena">Contraseña *</label>
            <input type="password" id="contrasena" name="contrasena" required>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-success">
                <i class="fas fa-user-plus"></i> Agregar Usuario
            </button>
        </div>
    </form>
</div>

<!-- Lista de Usuarios -->
<div class="table-container">
    <div class="table-header">
        <h3 class="table-title">Usuarios del Sistema</h3>
    </div>

    <table>
        <thead>
            <tr>
                <th>Usuario</th>
                <th>Nombre</th>
                <th>Email</th>
                <th>Fecha Registro</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($usuarios as $usuario): ?>
                <tr>
                    <td><?php echo htmlspecialchars($usuario['usuario']); ?></td>
                    <td><?php echo htmlspecialchars($usuario['nombre']); ?></td>
                    <td><?php echo htmlspecialchars($usuario['email'] ?: 'N/A'); ?></td>
                    <td><?php echo date('d/m/Y', strtotime($usuario['fecha_creacion'])); ?></td>
                    <td>
                        <div class="actions">
                            <?php if ($usuario['usuario'] !== 'admin'): ?>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="accion" value="eliminar_usuario">
                                    <input type="hidden" name="id_eliminar" value="<?php echo $usuario['id']; ?>">
                                    <button type="submit" class="btn btn-danger btn-sm"
                                            onclick="return confirm('¿Estás seguro de eliminar al usuario \'<?php echo htmlspecialchars($usuario['usuario']); ?>\'?')">
                                        <i class="fas fa-trash"></i> Eliminar
                                    </button>
                                </form>
                            <?php else: ?>
                                <span style="color: #6c757d; font-size: 12px;">Admin</span>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Estadísticas por Usuario -->
<div class="table-container">
    <div class="table-header">
        <h3 class="table-title">Estadísticas por Usuario</h3>
    </div>

    <table>
        <thead>
            <tr>
                <th>Usuario</th>
                <th>Nombre</th>
                <th>Productos</th>
                <th>Unidades Totales</th>
                <th>Valor del Inventario</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($estadisticas_usuarios as $stats): ?>
                <tr>
                    <td><?php echo htmlspecialchars($stats['usuario']); ?></td>
                    <td><?php echo htmlspecialchars($stats['nombre']); ?></td>
                    <td><?php echo number_format($stats['total_productos']); ?></td>
                    <td><?php echo number_format($stats['total_unidades']); ?></td>
                    <td>$<?php echo number_format($stats['valor_total'], 2); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php include 'layout_footer.php'; ?>