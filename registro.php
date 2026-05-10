<?php
session_start();

// Redirigir si ya está logueado
if (isset($_SESSION['usuario'])) {
    header("Location: dashboard.php");
    exit();
}

require_once 'conexion.php';

$mensaje = "";
$tipo_mensaje = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $usuario = trim($_POST['usuario']);
    $nombre = trim($_POST['nombre']);
    $email = trim($_POST['email']);
    $contrasena = trim($_POST['contrasena']);
    $confirmar_contrasena = trim($_POST['confirmar_contrasena']);

    // Validaciones
    if (empty($usuario) || empty($nombre) || empty($contrasena)) {
        $mensaje = "Usuario, nombre y contraseña son obligatorios";
        $tipo_mensaje = "error";
    } elseif ($contrasena !== $confirmar_contrasena) {
        $mensaje = "Las contraseñas no coinciden";
        $tipo_mensaje = "error";
    } elseif (strlen($contrasena) < 6) {
        $mensaje = "La contraseña debe tener al menos 6 caracteres";
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
            $sql = "INSERT INTO usuarios (usuario, contrasena, nombre, email, rol) VALUES (?, ?, ?, ?, 'usuario')";
            $stmt = $conexion->prepare($sql);
            $stmt->bind_param("ssss", $usuario, $hash, $nombre, $email);

            if ($stmt->execute()) {
                $mensaje = "✓ Usuario registrado correctamente. Ahora puedes iniciar sesión.";
                $tipo_mensaje = "success";
            } else {
                $mensaje = "Error al registrar el usuario";
                $tipo_mensaje = "error";
            }
            $stmt->close();
        }
        $stmt_check->close();
    }
}

$conexion->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro - Sistema de Inventario</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .registro-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            padding: 40px;
            width: 100%;
            max-width: 450px;
            position: relative;
            overflow: hidden;
        }

        .registro-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: linear-gradient(90deg, #667eea, #764ba2);
        }

        .logo {
            text-align: center;
            margin-bottom: 30px;
        }

        .logo i {
            font-size: 3rem;
            color: #667eea;
            margin-bottom: 10px;
        }

        .logo h1 {
            color: #333;
            font-size: 1.8rem;
            margin-bottom: 5px;
        }

        .logo p {
            color: #666;
            font-size: 0.9rem;
        }

        .form-group {
            margin-bottom: 20px;
            position: relative;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: #333;
            font-weight: 500;
        }

        .form-group input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-group input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .form-group i {
            position: absolute;
            right: 15px;
            top: 38px;
            color: #999;
        }

        .btn-registro {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-bottom: 20px;
        }

        .btn-registro:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.3);
        }

        .btn-registro:active {
            transform: translateY(0);
        }

        .mensaje {
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
            font-weight: 500;
        }

        .mensaje.error {
            background-color: #fee;
            color: #c33;
            border: 1px solid #fcc;
        }

        .mensaje.success {
            background-color: #efe;
            color: #363;
            border: 1px solid #cfc;
        }

        .enlaces {
            text-align: center;
            margin-top: 20px;
        }

        .enlaces a {
            color: #667eea;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .enlaces a:hover {
            color: #764ba2;
            text-decoration: underline;
        }

        @media (max-width: 480px) {
            .registro-container {
                padding: 30px 20px;
                margin: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="registro-container">
        <div class="logo">
            <i class="fas fa-boxes"></i>
            <h1>Sistema de Inventario</h1>
            <p>Crear nueva cuenta</p>
        </div>

        <?php if (!empty($mensaje)): ?>
            <div class="mensaje <?php echo $tipo_mensaje; ?>">
                <?php echo htmlspecialchars($mensaje); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label for="usuario">Usuario *</label>
                <input type="text" id="usuario" name="usuario" required
                       value="<?php echo isset($_POST['usuario']) ? htmlspecialchars($_POST['usuario']) : ''; ?>">
                <i class="fas fa-user"></i>
            </div>

            <div class="form-group">
                <label for="nombre">Nombre completo *</label>
                <input type="text" id="nombre" name="nombre" required
                       value="<?php echo isset($_POST['nombre']) ? htmlspecialchars($_POST['nombre']) : ''; ?>">
                <i class="fas fa-id-card"></i>
            </div>

            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email"
                       value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                <i class="fas fa-envelope"></i>
            </div>

            <div class="form-group">
                <label for="contrasena">Contraseña *</label>
                <input type="password" id="contrasena" name="contrasena" required>
                <i class="fas fa-lock"></i>
            </div>

            <div class="form-group">
                <label for="confirmar_contrasena">Confirmar contraseña *</label>
                <input type="password" id="confirmar_contrasena" name="confirmar_contrasena" required>
                <i class="fas fa-lock"></i>
            </div>

            <button type="submit" class="btn-registro">
                <i class="fas fa-user-plus"></i> Registrarse
            </button>
        </form>

        <div class="enlaces">
            <a href="index.php">
                <i class="fas fa-sign-in-alt"></i> ¿Ya tienes cuenta? Inicia sesión
            </a>
        </div>
    </div>

    <script>
        // Validación en tiempo real
        document.getElementById('confirmar_contrasena').addEventListener('input', function() {
            const contrasena = document.getElementById('contrasena').value;
            const confirmar = this.value;
            this.style.borderColor = contrasena === confirmar ? '#4CAF50' : '#ff4444';
        });
    </script>
</body>
</html>