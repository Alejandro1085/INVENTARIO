<?php
// crear_bd.php - Ejecutar una sola vez para crear las tablas desde archivo SQL

$servidor = "localhost";
$usuario = "root";
$contrasena = ""; // Sin contraseña por defecto en XAMPP

// Conexión sin base de datos
$conexion = new mysqli($servidor, $usuario, $contrasena);

if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}

// Leer el archivo SQL
$sql_file = 'inventario_db.sql';
if (!file_exists($sql_file)) {
    die("Error: No se encuentra el archivo $sql_file");
}

$sql_content = file_get_contents($sql_file);

// Dividir el archivo SQL en consultas individuales
$queries = array_filter(array_map('trim', explode(';', $sql_content)));

// Ejecutar cada consulta
$errors = [];
$success_count = 0;

foreach ($queries as $query) {
    if (!empty($query) && !preg_match('/^--/', $query)) {
        if (!$conexion->query($query)) {
            $errors[] = "Error en consulta: " . $conexion->error . "\nQuery: " . substr($query, 0, 100) . "...";
        } else {
            $success_count++;
        }
    }
}

$conexion->close();

// Mostrar resultados
echo "<!DOCTYPE html>
<html lang='es'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Instalación Base de Datos</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .success { color: #28a745; }
        .error { color: #dc3545; }
        .warning { color: #ffc107; }
        .btn { display: inline-block; padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; margin: 10px 5px 0 0; }
        .btn:hover { background: #0056b3; }
    </style>
</head>
<body>
    <div class='container'>
        <h1>📦 Instalación del Sistema de Inventario</h1>
        <hr>";

if (count($errors) == 0) {
    echo "<div class='success'>
        <h2>✅ Instalación Completada</h2>
        <p>Se ejecutaron <strong>$success_count</strong> consultas SQL exitosamente.</p>
        <p>La base de datos <strong>inventario_db</strong> ha sido creada con todas las tablas y datos iniciales.</p>
    </div>

    <div style='background: #e7f3ff; padding: 15px; border-radius: 5px; margin: 20px 0;'>
        <h3>🔐 Credenciales de Acceso</h3>
        <p><strong>Usuario:</strong> admin</p>
        <p><strong>Contraseña:</strong> 123456</p>
    </div>

    <div style='background: #fff3cd; padding: 15px; border-radius: 5px; margin: 20px 0;'>
        <h3>⚠️ Importante</h3>
        <p>Por seguridad, elimina este archivo después de la instalación.</p>
    </div>";
} else {
    echo "<div class='error'>
        <h2>❌ Errores durante la instalación</h2>
        <p>Se encontraron " . count($errors) . " errores:</p>
        <ul>";
    foreach ($errors as $error) {
        echo "<li>$error</li>";
    }
    echo "</ul>
    </div>";
}

echo "
    <div style='margin-top: 30px;'>
        <a href='index.php' class='btn'>🚀 Ir al Sistema</a>
        <a href='inventario_db.sql' class='btn' style='background: #6c757d;'>📄 Ver Archivo SQL</a>
        <a href='README.md' class='btn' style='background: #17a2b8;'>📖 Ver Documentación</a>
    </div>

    <div style='margin-top: 30px; padding-top: 20px; border-top: 1px solid #dee2e6; color: #6c757d; font-size: 14px;'>
        <p><strong>Archivos incluidos en la instalación:</strong></p>
        <ul>
            <li>✅ Tabla usuarios (con admin por defecto)</li>
            <li>✅ Tabla categorias (con datos de ejemplo)</li>
            <li>✅ Tabla proveedores (con datos de ejemplo)</li>
            <li>✅ Tabla inventario (con productos de ejemplo)</li>
            <li>✅ Tabla movimientos_inventario (historial)</li>
            <li>✅ Tabla ventas y detalle_ventas</li>
            <li>✅ Vistas para consultas complejas</li>
            <li>✅ Triggers para auditoría automática</li>
        </ul>
    </div>
    </div>
</body>
</html>";
?>
