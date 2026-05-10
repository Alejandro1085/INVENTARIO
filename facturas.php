<?php
session_start();

// Verificar si el usuario está logueado
if (!isset($_SESSION['usuario'])) {
    header("Location: index.php");
    exit();
}

$titulo_pagina = "Lista de Facturas";
require_once 'layout.php';
require_once 'conexion.php';

$usuario_id = $_SESSION['usuario_id'];
$es_admin = (isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin');
$mensaje = "";
$tipo_mensaje = "";

// Procesar cambio de estado
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['cambiar_estado'])) {
    $factura_id = intval($_POST['factura_id']);
    $nuevo_estado = $_POST['nuevo_estado'];

    // Verificar permisos
    $sql_permiso = "SELECT usuario_id FROM facturas WHERE id = ?";
    $stmt_permiso = $conexion->prepare($sql_permiso);
    $stmt_permiso->bind_param("i", $factura_id);
    $stmt_permiso->execute();
    $resultado_permiso = $stmt_permiso->get_result();

    if ($resultado_permiso->num_rows > 0) {
        $factura_permiso = $resultado_permiso->fetch_assoc();

        if ($es_admin || $factura_permiso['usuario_id'] == $usuario_id) {
            $sql_update = "UPDATE facturas SET estado = ? WHERE id = ?";
            $stmt_update = $conexion->prepare($sql_update);
            $stmt_update->bind_param("si", $nuevo_estado, $factura_id);

            if ($stmt_update->execute()) {
                $mensaje = "Estado de la factura actualizado correctamente";
                $tipo_mensaje = "success";
            } else {
                $mensaje = "Error al actualizar el estado: " . htmlspecialchars($conexion->error);
                $tipo_mensaje = "error";
            }
            $stmt_update->close();
        } else {
            $mensaje = "No tienes permisos para modificar esta factura";
            $tipo_mensaje = "error";
        }
    }
    $stmt_permiso->close();
}
$sql_base = "SELECT f.id, f.numero_factura, f.cliente_nombre, f.fecha_creacion, f.total, f.metodo_pago, f.estado, u.nombre as usuario_nombre
             FROM facturas f
             JOIN usuarios u ON f.usuario_id = u.id
             WHERE 1=1";

if (!$es_admin) {
    $sql_base .= " AND f.usuario_id = ?";
}

$sql_base .= " ORDER BY f.fecha_creacion DESC";

$stmt = $conexion->prepare($sql_base);
if (!$es_admin) {
    $stmt->bind_param("i", $usuario_id);
}
$stmt->execute();
$resultado = $stmt->get_result();
$facturas = $resultado->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Calcular estadísticas
$total_facturas = count($facturas);
$total_ingresos = 0;
$facturas_pendientes = 0;
$facturas_pagadas = 0;

foreach ($facturas as $factura) {
    $total_ingresos += $factura['total'];
    if ($factura['estado'] === 'pendiente') {
        $facturas_pendientes++;
    } elseif ($factura['estado'] === 'pagada') {
        $facturas_pagadas++;
    }
}

$conexion->close();
?>

<div class="content-wrapper">
    <div class="page-header">
        <h1><i class="fas fa-file-invoice-dollar"></i> Gestión de Facturas</h1>
        <div class="header-actions">
            <a href="pos.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> Nueva Factura
            </a>
        </div>
    </div>

    <!-- Estadísticas -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon">📄</div>
            <div class="stat-content">
                <h3><?php echo $total_facturas; ?></h3>
                <p>Total de Facturas</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">💰</div>
            <div class="stat-content">
                <h3>$<?php echo number_format($total_ingresos, 2); ?></h3>
                <p>Ingresos Totales</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">⏳</div>
            <div class="stat-content">
                <h3><?php echo $facturas_pendientes; ?></h3>
                <p>Pendientes</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">✅</div>
            <div class="stat-content">
                <h3><?php echo $facturas_pagadas; ?></h3>
                <p>Pagadas</p>
            </div>
        </div>
    </div>

    <!-- Lista de facturas -->
    <div class="table-container">
        <div class="table-header">
            <h3 class="table-title">Facturas</h3>
        </div>

        <?php if (count($facturas) > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Número</th>
                        <th>Cliente</th>
                        <th>Fecha</th>
                        <th>Total</th>
                        <th>Método de Pago</th>
                        <th>Estado</th>
                        <?php if ($es_admin): ?>
                            <th>Usuario</th>
                        <?php endif; ?>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($facturas as $factura): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($factura['numero_factura']); ?></td>
                            <td><?php echo htmlspecialchars($factura['cliente_nombre'] ?: 'Cliente Final'); ?></td>
                            <td><?php echo date('d/m/Y H:i', strtotime($factura['fecha_creacion'])); ?></td>
                            <td>$<?php echo number_format($factura['total'], 2); ?></td>
                            <td><?php 
                                $metodos = [
                                    'efectivo' => 'Efectivo',
                                    'tarjeta_credito' => 'Tarjeta Crédito',
                                    'tarjeta_debito' => 'Tarjeta Débito',
                                    'transferencia' => 'Transferencia',
                                    'cheque' => 'Cheque',
                                    'otro' => 'Otro'
                                ];
                                $metodo_pago = isset($factura['metodo_pago']) ? $factura['metodo_pago'] : 'efectivo';
                                echo isset($metodos[$metodo_pago]) ? $metodos[$metodo_pago] : ucfirst($metodo_pago);
                            ?></td>
                            <td>
                                <form method="POST" class="estado-form" style="display: inline;">
                                    <input type="hidden" name="factura_id" value="<?php echo $factura['id']; ?>">
                                    <select name="nuevo_estado" onchange="cambiarEstado(this, '<?php echo $factura['numero_factura']; ?>')" class="estado-select estado-<?php echo $factura['estado']; ?>">
                                        <option value="pendiente" <?php echo $factura['estado'] === 'pendiente' ? 'selected' : ''; ?>>Pendiente</option>
                                        <option value="pagada" <?php echo $factura['estado'] === 'pagada' ? 'selected' : ''; ?>>Pagada</option>
                                        <option value="cancelada" <?php echo $factura['estado'] === 'cancelada' ? 'selected' : ''; ?>>Cancelada</option>
                                    </select>
                                    <input type="hidden" name="cambiar_estado" value="1">
                                </form>
                            </td>
                            <?php if ($es_admin): ?>
                                <td><?php echo htmlspecialchars($factura['usuario_nombre']); ?></td>
                            <?php endif; ?>
                            <td>
                                <div class="actions">
                                    <a href="ver_factura.php?id=<?php echo $factura['id']; ?>" class="btn btn-primary btn-sm">
                                        <i class="fas fa-eye"></i> Ver
                                    </a>
                                    <a href="imprimir_factura.php?id=<?php echo $factura['id']; ?>" target="_blank" class="btn btn-secondary btn-sm">
                                        <i class="fas fa-print"></i> Imprimir
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="empty-state">
                <div class="empty-icon">📄</div>
                <h4>No hay facturas</h4>
                <p>Crea tu primera factura usando el sistema POS.</p>
                <a href="pos.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Crear Primera Factura
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
    padding-bottom: 20px;
    border-bottom: 2px solid #e9ecef;
}

.page-header h1 {
    margin: 0;
    color: #2c3e50;
}

.header-actions {
    display: flex;
    gap: 10px;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.stat-card {
    background: white;
    border-radius: 8px;
    padding: 20px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    display: flex;
    align-items: center;
    gap: 15px;
}

.stat-icon {
    font-size: 24px;
}

.stat-content h3 {
    margin: 0;
    color: #2c3e50;
    font-size: 24px;
}

.stat-content p {
    margin: 5px 0 0 0;
    color: #666;
    font-size: 14px;
}

.estado-badge {
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
}

.estado-pendiente {
    background: #fff3cd;
    color: #856404;
}

.estado-pagada {
    background: #d4edda;
    color: #155724;
}

.estado-cancelada {
    background: #f8d7da;
    color: #721c24;
}

.estado-form {
    margin: 0;
}

.estado-select {
    padding: 4px 8px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
    cursor: pointer;
    background: white;
    min-width: 100px;
}

.estado-select:focus {
    outline: none;
    border-color: #007bff;
    box-shadow: 0 0 0 2px rgba(0, 123, 255, 0.25);
}

.estado-select.estado-pendiente {
    color: #856404;
}

.estado-select.estado-pagada {
    color: #155724;
}

.estado-select.estado-cancelada {
    color: #721c24;
}
</style>

<script>
function cambiarEstado(selectElement, numeroFactura) {
    const nuevoEstado = selectElement.value;
    const estadoActual = selectElement.getAttribute('data-estado-actual') || '<?php echo $factura['estado']; ?>';
    
    // Si el estado no cambió, no hacer nada
    if (nuevoEstado === estadoActual) {
        return;
    }
    
    // Confirmar cambio a estado cancelado
    if (nuevoEstado === 'cancelada') {
        if (!confirm(`¿Estás seguro de cancelar la factura ${numeroFactura}? Esta acción no se puede deshacer.`)) {
            // Restaurar el valor anterior
            selectElement.value = estadoActual;
            return;
        }
    }
    
    // Confirmar cambio de pagada a otro estado
    if (estadoActual === 'pagada' && nuevoEstado !== 'pagada') {
        if (!confirm(`¿Estás seguro de cambiar el estado de la factura ${numeroFactura} de "Pagada" a "${nuevoEstado}"?`)) {
            selectElement.value = estadoActual;
            return;
        }
    }
    
    // Enviar el formulario
    selectElement.form.submit();
}

// Actualizar el estado actual en los selects
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.estado-select').forEach(select => {
        select.setAttribute('data-estado-actual', select.value);
    });
});
</script>

<?php include 'layout_footer.php'; ?>