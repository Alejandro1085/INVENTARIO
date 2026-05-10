<?php
require_once 'conexion.php';

$usuario = 'admin';
$contrasena = '123456';

$sql = "SELECT id, usuario, contrasena, nombre, rol, activo FROM usuarios WHERE usuario = ?";
$stmt = $conexion->prepare($sql);
$stmt->bind_param('s', $usuario);
$stmt->execute();
$resultado = $stmt->get_result();

if ($resultado->num_rows == 1) {
    $usuario_db = $resultado->fetch_assoc();

    if ($usuario_db['activo'] == 1 && password_verify($contrasena, $usuario_db['contrasena'])) {
        echo "✅ LOGIN EXITOSO\n";
        echo "Usuario: " . $usuario_db['usuario'] . "\n";
        echo "Rol: " . $usuario_db['rol'] . "\n";
        echo "Nombre: " . $usuario_db['nombre'] . "\n";
    } else {
        echo "❌ CONTRASEÑA INCORRECTA\n";
    }
} else {
    echo "❌ USUARIO NO ENCONTRADO\n";
}

$stmt->close();
$conexion->close();
?>