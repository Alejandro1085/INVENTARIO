<?php
session_start();

// Redirigir si ya está logueado
if (isset($_SESSION['usuario'])) {
    header("Location: inventario.php");
    exit();
}

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    require_once 'conexion.php';
    
    $usuario = trim($_POST['usuario']);
    $contrasena = trim($_POST['contrasena']);
    
    if (empty($usuario) || empty($contrasena)) {
        $error = "Por favor, completa todos los campos";
    } else {
        // Buscar el usuario en la base de datos
        $sql = "SELECT id, usuario, contrasena, nombre, rol FROM usuarios WHERE usuario = ?";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("s", $usuario);
        $stmt->execute();
        $resultado = $stmt->get_result();
        
        if ($resultado->num_rows == 1) {
            $fila = $resultado->fetch_assoc();
            // Verificar contraseña
            if (password_verify($contrasena, $fila['contrasena'])) {
                // Inicio de sesión exitoso
                $_SESSION['usuario'] = $fila['usuario'];
                $_SESSION['usuario_id'] = $fila['id'];
                $_SESSION['nombre'] = $fila['nombre'];
                $_SESSION['rol'] = $fila['rol'];
                
                header("Location: dashboard.php");
                exit();
            } else {
                $error = "Contraseña incorrecta";
            }
        } else {
            $error = "Usuario no encontrado";
        }
        
        $stmt->close();
    }
    
    $conexion->close();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inicio de Sesión - Inventario</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        
        .contenedor-login {
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
            width: 100%;
            max-width: 400px;
        }
        
        .logo {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .logo h1 {
            color: #333;
            font-size: 28px;
            margin-bottom: 10px;
        }
        
        .logo p {
            color: #666;
            font-size: 14px;
        }
        
        .formulario {
            display: flex;
            flex-direction: column;
        }
        
        .grupo-form {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
        }
        
        input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
            transition: border-color 0.3s;
        }
        
        input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 5px rgba(102, 126, 234, 0.2);
        }
        
        .alerta {
            background-color: #f8d7da;
            color: #721c24;
            padding: 12px;
            border-radius: 5px;
            margin-bottom: 20px;
            border: 1px solid #f5c6cb;
        }
        
        .boton-login {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 12px;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s;
            margin-top: 10px;
        }
        
        .boton-login:hover {
            transform: translateY(-2px);
        }
        
        .pie {
            text-align: center;
            margin-top: 20px;
            color: #666;
            font-size: 13px;
        }
    </style>
</head>
<body>
    <div class="contenedor-login">
        <div class="logo">
            <h1>📦 Inventario</h1>
            <p>Gestión de Inventario</p>
        </div>
        
        <?php if ($error): ?>
            <div class="alerta">
                ⚠️ <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" class="formulario">
            <div class="grupo-form">
                <label for="usuario">Usuario:</label>
                <input type="text" id="usuario" name="usuario" required autofocus>
            </div>
            
            <div class="grupo-form">
                <label for="contrasena">Contraseña:</label>
                <input type="password" id="contrasena" name="contrasena" required>
            </div>
            
            <button type="submit" class="boton-login">Iniciar Sesión</button>
        </form>
        
        <div class="pie">
            <p>Usuario de prueba: <strong>admin</strong> / <strong>123456</strong></p>
            <p><a href="registro.php" style="color: #667eea; text-decoration: none;">¿No tienes cuenta? Regístrate aquí</a></p>
        </div>
    </div>
</body>
</html>
