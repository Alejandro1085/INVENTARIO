<?php
session_start();

// Verificar si el usuario está logueado
if (!isset($_SESSION['usuario'])) {
    header("Location: index.php");
    exit();
}

$titulo_pagina = "Configuración";
require_once 'layout.php';
require_once 'conexion.php';

$usuario_id = $_SESSION['usuario_id'];
$mensaje = "";
$tipo_mensaje = "";

// Procesar cambio de contraseña
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['accion'])) {
    if ($_POST['accion'] == 'cambiar_password') {
        $password_actual = trim($_POST['password_actual']);
        $password_nueva = trim($_POST['password_nueva']);
        $password_confirmar = trim($_POST['password_confirmar']);

        if (empty($password_actual) || empty($password_nueva) || empty($password_confirmar)) {
            $mensaje = "Todos los campos de contraseña son obligatorios";
            $tipo_mensaje = "error";
        } elseif ($password_nueva !== $password_confirmar) {
            $mensaje = "Las contraseñas nuevas no coinciden";
            $tipo_mensaje = "error";
        } elseif (strlen($password_nueva) < 6) {
            $mensaje = "La contraseña debe tener al menos 6 caracteres";
            $tipo_mensaje = "error";
        } else {
            // Verificar contraseña actual
            $sql = "SELECT contrasena FROM usuarios WHERE id = ?";
            $stmt = $conexion->prepare($sql);
            $stmt->bind_param("i", $usuario_id);
            $stmt->execute();
            $resultado = $stmt->get_result();

            if ($resultado->num_rows == 1) {
                $fila = $resultado->fetch_assoc();
                if (password_verify($password_actual, $fila['contrasena'])) {
                    // Cambiar contraseña
                    $hash_nueva = password_hash($password_nueva, PASSWORD_BCRYPT);
                    $sql_update = "UPDATE usuarios SET contrasena = ? WHERE id = ?";
                    $stmt_update = $conexion->prepare($sql_update);
                    $stmt_update->bind_param("si", $hash_nueva, $usuario_id);

                    if ($stmt_update->execute()) {
                        $mensaje = "✓ Contraseña cambiada correctamente";
                        $tipo_mensaje = "success";
                    } else {
                        $mensaje = "Error al cambiar la contraseña";
                        $tipo_mensaje = "error";
                    }
                    $stmt_update->close();
                } else {
                    $mensaje = "Contraseña actual incorrecta";
                    $tipo_mensaje = "error";
                }
            }
            $stmt->close();
        }
    } elseif ($_POST['accion'] == 'actualizar_perfil') {
        $nombre = trim($_POST['nombre']);
        $email = trim($_POST['email']);

        if (empty($nombre)) {
            $mensaje = "El nombre es obligatorio";
            $tipo_mensaje = "error";
        } else {
            $sql = "UPDATE usuarios SET nombre = ?, email = ? WHERE id = ?";
            $stmt = $conexion->prepare($sql);
            $stmt->bind_param("ssi", $nombre, $email, $usuario_id);

            if ($stmt->execute()) {
                $_SESSION['nombre'] = $nombre;
                $mensaje = "✓ Perfil actualizado correctamente";
                $tipo_mensaje = "success";
            } else {
                $mensaje = "Error al actualizar el perfil";
                $tipo_mensaje = "error";
            }
            $stmt->close();
        }
    }
}

// Obtener datos del usuario actual
$sql_usuario = "SELECT usuario, nombre, email, fecha_creacion FROM usuarios WHERE id = ?";
$stmt = $conexion->prepare($sql_usuario);
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$usuario_actual = $stmt->get_result()->fetch_assoc();
$stmt->close();

$conexion->close();
?>

<div class="form-section">
    <h3>Información del Perfil</h3>
    <form method="POST" class="form-grid">
        <input type="hidden" name="accion" value="actualizar_perfil">

        <div class="form-group">
            <label for="usuario">Usuario</label>
            <input type="text" id="usuario" value="<?php echo htmlspecialchars($usuario_actual['usuario']); ?>" readonly
                   style="background: #f8f9fa; cursor: not-allowed;">
        </div>

        <div class="form-group">
            <label for="nombre">Nombre Completo</label>
            <input type="text" id="nombre" name="nombre" value="<?php echo htmlspecialchars($usuario_actual['nombre']); ?>" required>
        </div>

        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($usuario_actual['email'] ?: ''); ?>">
        </div>

        <div class="form-group">
            <label>Fecha de Registro</label>
            <input type="text" value="<?php echo date('d/m/Y H:i', strtotime($usuario_actual['fecha_creacion'])); ?>" readonly
                   style="background: #f8f9fa; cursor: not-allowed;">
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i> Actualizar Perfil
            </button>
        </div>
    </form>
</div>

<div class="form-section">
    <h3>Cambiar Contraseña</h3>
    <form method="POST" class="form-grid">
        <input type="hidden" name="accion" value="cambiar_password">

        <div class="form-group">
            <label for="password_actual">Contraseña Actual</label>
            <input type="password" id="password_actual" name="password_actual" required>
        </div>

        <div class="form-group">
            <label for="password_nueva">Nueva Contraseña</label>
            <input type="password" id="password_nueva" name="password_nueva" required minlength="6">
        </div>

        <div class="form-group">
            <label for="password_confirmar">Confirmar Nueva Contraseña</label>
            <input type="password" id="password_confirmar" name="password_confirmar" required minlength="6">
        </div>

        <div class="form-group" style="grid-column: 1 / -1;">
            <small style="color: #6c757d;">
                La contraseña debe tener al menos 6 caracteres.
            </small>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-warning">
                <i class="fas fa-key"></i> Cambiar Contraseña
            </button>
        </div>
    </form>
</div>

<div class="form-section">
    <h3>Información del Sistema</h3>
    <div class="stats-cards">
        <div class="card">
            <div class="card-icon blue">
                <i class="fas fa-server"></i>
            </div>
            <div class="card-title">Versión PHP</div>
            <div class="card-value"><?php echo PHP_VERSION; ?></div>
        </div>

        <div class="card">
            <div class="card-icon green">
                <i class="fas fa-database"></i>
            </div>
            <div class="card-title">Base de Datos</div>
            <div class="card-value">MySQL</div>
        </div>

        <div class="card">
            <div class="card-icon orange">
                <i class="fas fa-clock"></i>
            </div>
            <div class="card-title">Zona Horaria</div>
            <div class="card-value"><?php echo date_default_timezone_get(); ?></div>
        </div>

        <div class="card">
            <div class="card-icon red">
                <i class="fas fa-calendar"></i>
            </div>
            <div class="card-title">Fecha del Sistema</div>
            <div class="card-value"><?php echo date('d/m/Y'); ?></div>
        </div>
    </div>
</div>

<div class="form-section">
    <h3>Acciones del Sistema</h3>
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
        <button onclick="limpiarCache()" class="btn btn-warning" style="padding: 15px; text-align: center;">
            <i class="fas fa-broom"></i><br>
            Limpiar Cache
        </button>

        <button onclick="exportarDatos()" class="btn btn-primary" style="padding: 15px; text-align: center;">
            <i class="fas fa-download"></i><br>
            Exportar Datos
        </button>

        <button onclick="respaldarBD()" class="btn btn-success" style="padding: 15px; text-align: center;">
            <i class="fas fa-save"></i><br>
            Respaldar BD
        </button>

        <button onclick="mostrarAyuda()" class="btn btn-info" style="padding: 15px; text-align: center;">
            <i class="fas fa-question-circle"></i><br>
            Ayuda
        </button>
    </div>
</div>

<script>
function limpiarCache() {
    if (confirm('¿Estás seguro de limpiar la caché del navegador?')) {
        // Limpiar localStorage y sessionStorage
        localStorage.clear();
        sessionStorage.clear();

        // Recargar la página
        location.reload();
    }
}

function exportarDatos() {
    alert('Función de exportación próximamente disponible');
}

function respaldarBD() {
    alert('Función de respaldo próximamente disponible');
}

function mostrarAyuda() {
    alert('Sistema de Inventario v1.0\n\nFunciones principales:\n- Gestión de productos\n- Control de stock\n- Reportes y estadísticas\n- Múltiples usuarios\n\nPara soporte técnico contacte al administrador.');
}

// Validación de contraseñas
document.getElementById('password_confirmar').addEventListener('input', function() {
    const nueva = document.getElementById('password_nueva').value;
    const confirmar = this.value;

    if (nueva !== confirmar) {
        this.style.borderColor = '#dc3545';
    } else {
        this.style.borderColor = '#28a745';
    }
});
</script>

<?php include 'layout_footer.php'; ?>