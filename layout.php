<?php
// Iniciar sesión si no está activa
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Verificar si el usuario está logueado
if (!isset($_SESSION['usuario'])) {
    header("Location: index.php");
    exit();
}

// Obtener información del usuario
$usuario_actual = $_SESSION['usuario'];
$nombre_actual = $_SESSION['nombre'];

// Determinar página activa
$pagina_actual = basename($_SERVER['PHP_SELF'], '.php');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($titulo_pagina) ? $titulo_pagina : 'Sistema de Inventario'; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f8f9fa;
            overflow-x: hidden;
        }

        .layout {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar */
        .sidebar {
            width: 260px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            position: fixed;
            height: 100vh;
            left: 0;
            top: 0;
            z-index: 1000;
            transition: transform 0.3s ease;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
        }

        .sidebar.collapsed {
            transform: translateX(-260px);
        }

        .sidebar-header {
            padding: 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            text-align: center;
        }

        .sidebar-header h2 {
            font-size: 18px;
            margin-bottom: 5px;
        }

        .sidebar-header p {
            font-size: 12px;
            opacity: 0.8;
        }

        .sidebar-menu {
            padding: 20px 0;
            height: calc(100vh - 120px);
            overflow-y: auto;
            overflow-x: hidden;
        }

        /* Estilos para la barra de desplazamiento */
        .sidebar-menu::-webkit-scrollbar {
            width: 6px;
        }

        .sidebar-menu::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 3px;
        }

        .sidebar-menu::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.3);
            border-radius: 3px;
        }

        .sidebar-menu::-webkit-scrollbar-thumb:hover {
            background: rgba(255, 255, 255, 0.5);
        }

        .menu-item {
            margin: 5px 0;
        }

        .menu-item a {
            display: flex;
            align-items: center;
            padding: 12px 25px;
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            transition: all 0.3s ease;
            border-left: 3px solid transparent;
        }

        .menu-item a:hover,
        .menu-item.active a {
            background: rgba(255, 255, 255, 0.1);
            color: white;
            border-left-color: white;
        }

        .menu-item i {
            margin-right: 12px;
            width: 20px;
            text-align: center;
        }

        .menu-section {
            margin: 20px 0 10px 0;
            padding: 0 25px;
        }

        .menu-section h4 {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 1px;
            opacity: 0.7;
            font-weight: 600;
        }

        /* Main content */
        .main-content {
            flex: 1;
            margin-left: 260px;
            transition: margin-left 0.3s ease;
        }

        .main-content.expanded {
            margin-left: 0;
        }

        /* Header */
        .header {
            background: white;
            padding: 15px 30px;
            border-bottom: 1px solid #e9ecef;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        .header-left {
            display: flex;
            align-items: center;
        }

        .menu-toggle {
            background: none;
            border: none;
            color: #6c757d;
            font-size: 18px;
            cursor: pointer;
            padding: 8px;
            border-radius: 4px;
            transition: background 0.3s;
            margin-right: 15px;
        }

        .menu-toggle:hover {
            background: #f8f9fa;
        }

        .page-title {
            font-size: 24px;
            color: #495057;
            margin: 0;
        }

        .header-right {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .user-avatar {
            width: 35px;
            height: 35px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
        }

        .user-details {
            display: flex;
            flex-direction: column;
        }

        .user-name {
            font-size: 14px;
            font-weight: 600;
            color: #495057;
        }

        .user-role {
            font-size: 12px;
            color: #6c757d;
        }

        .logout-btn {
            background: #dc3545;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            text-decoration: none;
            transition: background 0.3s;
        }

        .logout-btn:hover {
            background: #c82333;
        }

        /* Content */
        .content {
            padding: 30px;
            max-width: 100%;
        }

        /* Cards */
        .stats-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .card {
            background: white;
            border-radius: 8px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
            border: 1px solid #e9ecef;
        }

        .card-icon {
            width: 50px;
            height: 50px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 15px;
        }

        .card-icon.blue { background: rgba(102, 126, 234, 0.1); color: #667eea; }
        .card-icon.green { background: rgba(40, 167, 69, 0.1); color: #28a745; }
        .card-icon.orange { background: rgba(255, 193, 7, 0.1); color: #ffc107; }
        .card-icon.red { background: rgba(220, 53, 69, 0.1); color: #dc3545; }

        .card-title {
            font-size: 14px;
            color: #6c757d;
            margin-bottom: 10px;
            text-transform: uppercase;
            font-weight: 600;
        }

        .card-value {
            font-size: 32px;
            font-weight: bold;
            color: #495057;
        }

        /* Messages */
        .message {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .message.warning {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }

        /* Tables */
        .table-container {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
            overflow: hidden;
            border: 1px solid #e9ecef;
        }

        .table-header {
            padding: 20px 25px;
            border-bottom: 1px solid #e9ecef;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .table-title {
            font-size: 18px;
            color: #495057;
            margin: 0;
        }

        .table-actions {
            display: flex;
            gap: 10px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead {
            background: #f8f9fa;
        }

        th {
            padding: 15px 20px;
            text-align: left;
            color: #495057;
            font-weight: 600;
            font-size: 14px;
            border-bottom: 1px solid #dee2e6;
        }

        td {
            padding: 15px 20px;
            border-bottom: 1px solid #f1f3f4;
            color: #495057;
        }

        tbody tr:hover {
            background: #f8f9fa;
        }

        .actions {
            display: flex;
            gap: 8px;
        }

        .btn {
            padding: 6px 12px;
            border: none;
            border-radius: 4px;
            font-size: 12px;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            transition: all 0.3s;
        }

        .btn-primary {
            background: #667eea;
            color: white;
        }

        .btn-primary:hover {
            background: #5568d3;
        }

        .btn-success {
            background: #28a745;
            color: white;
        }

        .btn-success:hover {
            background: #218838;
        }

        .btn-danger {
            background: #dc3545;
            color: white;
        }

        .btn-danger:hover {
            background: #c82333;
        }

        .btn-warning {
            background: #ffc107;
            color: #212529;
        }

        .btn-warning:hover {
            background: #e0a800;
        }

        .btn-sm {
            padding: 4px 8px;
            font-size: 11px;
        }

        /* Forms */
        .form-section {
            background: white;
            border-radius: 8px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
            margin-bottom: 30px;
            border: 1px solid #e9ecef;
        }

        .form-section h3 {
            margin-bottom: 20px;
            color: #495057;
            border-bottom: 2px solid #667eea;
            padding-bottom: 10px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        label {
            margin-bottom: 8px;
            color: #495057;
            font-weight: 500;
            font-size: 14px;
        }

        input, select, textarea {
            padding: 12px;
            border: 1px solid #ced4da;
            border-radius: 4px;
            font-size: 14px;
            font-family: inherit;
            transition: border-color 0.3s;
        }

        input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 2px rgba(102, 126, 234, 0.1);
        }

        textarea {
            resize: vertical;
            min-height: 80px;
        }

        .form-actions {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #e9ecef;
        }

        /* Empty state */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #6c757d;
        }

        .empty-state i {
            font-size: 48px;
            margin-bottom: 20px;
            opacity: 0.5;
        }

        .empty-state p {
            font-size: 16px;
            margin-bottom: 20px;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-260px);
            }

            .sidebar.collapsed {
                transform: translateX(0);
            }

            .main-content {
                margin-left: 0;
            }

            .header {
                padding: 15px 20px;
            }

            .page-title {
                font-size: 20px;
            }

            .stats-cards {
                grid-template-columns: 1fr;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }

            .table-header {
                flex-direction: column;
                gap: 15px;
                align-items: flex-start;
            }

            .form-actions {
                flex-direction: column;
            }

            .actions {
                flex-direction: column;
            }
        }

        /* Loading */
        .loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 1s ease-in-out infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="layout">
        <!-- Sidebar -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <h2>📦 Inventario</h2>
                <p>Sistema de Gestión</p>
            </div>

            <nav class="sidebar-menu">
                <div class="menu-section">
                    <h4>Principal</h4>
                </div>

                <div class="menu-item <?php echo $pagina_actual == 'dashboard' ? 'active' : ''; ?>">
                    <a href="dashboard.php">
                        <i class="fas fa-tachometer-alt"></i>
                        Dashboard
                    </a>
                </div>

                <div class="menu-item <?php echo $pagina_actual == 'inventario' ? 'active' : ''; ?>">
                    <a href="inventario.php">
                        <i class="fas fa-boxes"></i>
                        Inventario
                    </a>
                </div>

                <div class="menu-item <?php echo $pagina_actual == 'agregar_producto' ? 'active' : ''; ?>">
                    <a href="agregar_producto.php">
                        <i class="fas fa-plus-circle"></i>
                        Agregar Producto
                    </a>
                </div>

                <div class="menu-section">
                    <h4>Gestión</h4>
                </div>

                <div class="menu-item <?php echo $pagina_actual == 'categorias' ? 'active' : ''; ?>">
                    <a href="categorias.php">
                        <i class="fas fa-tags"></i>
                        Categorías
                    </a>
                </div>

                <div class="menu-item <?php echo $pagina_actual == 'reportes' ? 'active' : ''; ?>">
                    <a href="reportes.php">
                        <i class="fas fa-chart-bar"></i>
                        Reportes
                    </a>
                </div>

                <div class="menu-section">
                    <h4>POS & Ventas</h4>
                </div>

                <div class="menu-item <?php echo $pagina_actual == 'pos' ? 'active' : ''; ?>">
                    <a href="pos.php">
                        <i class="fas fa-cash-register"></i>
                        Nueva Factura
                    </a>
                </div>

                <div class="menu-item <?php echo $pagina_actual == 'facturas' ? 'active' : ''; ?>">
                    <a href="facturas.php">
                        <i class="fas fa-file-invoice-dollar"></i>
                        Facturas
                    </a>
                </div>

                <div class="menu-section">
                    <h4>Sistema</h4>
                </div>

                <div class="menu-item <?php echo $pagina_actual == 'usuarios' ? 'active' : ''; ?>">
                    <a href="usuarios.php">
                        <i class="fas fa-users"></i>
                        Usuarios
                    </a>
                </div>

                <div class="menu-item <?php echo $pagina_actual == 'configuracion' ? 'active' : ''; ?>">
                    <a href="configuracion.php">
                        <i class="fas fa-cog"></i>
                        Configuración
                    </a>
                </div>
            </nav>
        </aside>

        <!-- Main Content -->
        <div class="main-content" id="main-content">
            <!-- Header -->
            <header class="header">
                <div class="header-left">
                    <button class="menu-toggle" id="menu-toggle">
                        <i class="fas fa-bars"></i>
                    </button>
                    <h1 class="page-title"><?php echo isset($titulo_pagina) ? $titulo_pagina : 'Dashboard'; ?></h1>
                </div>

                <div class="header-right">
                    <div class="user-info">
                        <div class="user-avatar">
                            <?php echo strtoupper(substr($nombre_actual, 0, 1)); ?>
                        </div>
                        <div class="user-details">
                            <span class="user-name"><?php echo htmlspecialchars($nombre_actual); ?></span>
                            <span class="user-role">Usuario</span>
                        </div>
                    </div>
                    <a href="logout.php" class="logout-btn">
                        <i class="fas fa-sign-out-alt"></i> Salir
                    </a>
                </div>
            </header>

            <!-- Page Content -->
            <main class="content">
                <?php if (isset($mensaje) && $mensaje): ?>
                    <div class="message <?php echo isset($tipo_mensaje) ? $tipo_mensaje : 'success'; ?>">
                        <?php echo htmlspecialchars($mensaje); ?>
                    </div>
                <?php endif; ?>
