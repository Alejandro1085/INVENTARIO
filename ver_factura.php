<?php
session_start();

// Verificar si el usuario está logueado
if (!isset($_SESSION['usuario'])) {
    header("Location: index.php");
    exit();
}

require_once 'conexion.php';

$usuario_id = $_SESSION['usuario_id'];
$es_admin = (isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin');
$factura_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Obtener datos de la factura
$sql_factura = "SELECT f.*, u.nombre as usuario_nombre
                FROM facturas f
                JOIN usuarios u ON f.usuario_id = u.id
                WHERE f.id = ?";

if (!$es_admin) {
    $sql_factura .= " AND f.usuario_id = ?";
}

$stmt_factura = $conexion->prepare($sql_factura);
if (!$es_admin) {
    $stmt_factura->bind_param("ii", $factura_id, $usuario_id);
} else {
    $stmt_factura->bind_param("i", $factura_id);
}
$stmt_factura->execute();
$resultado_factura = $stmt_factura->get_result();

if ($resultado_factura->num_rows === 0) {
    header("Location: facturas.php");
    exit();
}

$factura = $resultado_factura->fetch_assoc();
$stmt_factura->close();

// Obtener detalles de la factura
$sql_detalles = "SELECT fd.*, i.nombre as producto_nombre, i.codigo as producto_codigo
                 FROM factura_detalles fd
                 JOIN inventario i ON fd.producto_id = i.id
                 WHERE fd.factura_id = ?
                 ORDER BY fd.id";

$stmt_detalles = $conexion->prepare($sql_detalles);
$stmt_detalles->bind_param("i", $factura_id);
$stmt_detalles->execute();
$detalles = $stmt_detalles->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_detalles->close();

$conexion->close();

// URL para el código QR
$url = 'http://localhost/P PERSON/ver_factura.php?id=' . $factura_id;
$url_encoded = urlencode($url);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Factura <?php echo htmlspecialchars($factura['numero_factura']); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            font-family: 'Arial', sans-serif;
            margin: 0;
            padding: 20px;
            background: #f5f5f5;
        }

        .factura-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }

        .factura-header {
            background: #2c3e50;
            color: white;
            padding: 30px;
            text-align: center;
        }

        .factura-header h1 {
            margin: 0;
            font-size: 28px;
        }

        .factura-header .numero {
            font-size: 18px;
            margin-top: 10px;
            opacity: 0.9;
        }

        .factura-body {
            padding: 30px;
        }

        .info-section {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 30px;
        }

        .info-box {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
        }

        .info-box h3 {
            margin: 0 0 15px 0;
            color: #2c3e50;
            font-size: 16px;
            border-bottom: 2px solid #dee2e6;
            padding-bottom: 5px;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
        }

        .productos-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }

        .productos-table th,
        .productos-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #dee2e6;
        }

        .productos-table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #2c3e50;
        }

        .totales-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-top: 20px;
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0;
        }

        .total-row.final {
            font-size: 18px;
            font-weight: bold;
            color: #28a745;
            border-top: 2px solid #dee2e6;
            margin-top: 10px;
            padding-top: 15px;
        }

        .factura-footer {
            background: #2c3e50;
            color: white;
            padding: 20px 30px;
            text-align: center;
        }

        .acciones {
            text-align: center;
            margin-bottom: 30px;
        }

        .btn {
            display: inline-block;
            padding: 10px 20px;
            background: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin: 0 5px;
            border: none;
            cursor: pointer;
        }

        .btn:hover {
            background: #0056b3;
        }

        .btn-secondary {
            background: #6c757d;
        }

        .btn-secondary:hover {
            background: #545b62;
        }

        .btn-success {
            background: #28a745;
        }

        .btn-success:hover {
            background: #1e7e34;
        }

        @media print {
            body {
                background: white;
                padding: 0;
            }

            .factura-container {
                box-shadow: none;
                margin: 0;
            }

            .acciones {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="acciones">
        <button onclick="window.print()" class="btn btn-primary">
            <i class="fas fa-print"></i> Imprimir
        </button>
        <button onclick="generarPDF()" class="btn btn-success">
            <i class="fas fa-download"></i> Guardar PDF
        </button>
        <a href="facturas.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Volver
        </a>
    </div>

    <div class="factura-container" id="factura-content">
        <div class="factura-header">
            <h1>Sistema de Inventario</h1>
            <div class="numero">Factura: <?php echo htmlspecialchars($factura['numero_factura']); ?></div>
        </div>

        <div class="factura-body">
            <div class="info-section">
                <div class="info-box">
                    <h3>Información del Cliente</h3>
                    <div class="info-row">
                        <span>Nombre:</span>
                        <span><?php echo htmlspecialchars($factura['cliente_nombre'] ?: 'Cliente Final'); ?></span>
                    </div>
                    <?php if ($factura['cliente_documento']): ?>
                        <div class="info-row">
                            <span>Documento:</span>
                            <span><?php echo htmlspecialchars($factura['cliente_documento']); ?></span>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="info-box">
                    <h3>Detalles de la Factura</h3>
                    <div class="info-row">
                        <span>Fecha:</span>
                        <span><?php echo date('d/m/Y H:i', strtotime($factura['fecha_creacion'])); ?></span>
                    </div>
                    <div class="info-row">
                        <span>Estado:</span>
                        <span><?php echo ucfirst($factura['estado']); ?></span>
                    </div>
                    <div class="info-row">
                        <span>Método de Pago:</span>
                        <span><?php 
                            $metodos = [
                                'efectivo' => 'Efectivo',
                                'tarjeta_credito' => 'Tarjeta de Crédito',
                                'tarjeta_debito' => 'Tarjeta de Débito',
                                'transferencia' => 'Transferencia Bancaria',
                                'cheque' => 'Cheque',
                                'otro' => 'Otro'
                            ];
                            echo isset($metodos[$factura['metodo_pago']]) ? $metodos[$factura['metodo_pago']] : ucfirst($factura['metodo_pago']);
                        ?></span>
                    </div>
                    <?php if ($es_admin): ?>
                        <div class="info-row">
                            <span>Usuario:</span>
                            <span><?php echo htmlspecialchars($factura['usuario_nombre']); ?></span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <table class="productos-table">
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Producto</th>
                        <th>Cantidad</th>
                        <th>Precio Unit.</th>
                        <th>Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $subtotal_general = 0;
                    foreach ($detalles as $detalle):
                        $subtotal_general += $detalle['subtotal'];
                    ?>
                        <tr>
                            <td><?php echo htmlspecialchars($detalle['producto_codigo']); ?></td>
                            <td><?php echo htmlspecialchars($detalle['producto_nombre']); ?></td>
                            <td><?php echo $detalle['cantidad']; ?></td>
                            <td>$<?php echo number_format($detalle['precio_unitario'], 2); ?></td>
                            <td>$<?php echo number_format($detalle['subtotal'], 2); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <div class="totales-section">
                <div class="total-row">
                    <span>Subtotal:</span>
                    <span>$<?php echo number_format($subtotal_general, 2); ?></span>
                </div>
                <?php if ($factura['descuento'] > 0): ?>
                    <div class="total-row">
                        <span>Descuento:</span>
                        <span>-$<?php echo number_format($factura['descuento'], 2); ?></span>
                    </div>
                <?php endif; ?>
                <?php if ($factura['impuesto'] > 0): ?>
                    <div class="total-row">
                        <span>Impuesto (<?php echo $factura['impuesto']; ?>%):</span>
                        <span>$<?php echo number_format(($subtotal_general - $factura['descuento']) * $factura['impuesto'] / 100, 2); ?></span>
                    </div>
                <?php endif; ?>
                <div class="total-row final">
                    <span>TOTAL:</span>
                    <span>$<?php echo number_format($factura['total'], 2); ?></span>
                </div>
            </div>

            <div class="qr-section" style="text-align: center; margin-top: 30px; padding: 20px; background: #f8f9fa; border-radius: 8px;">
                <h3 style="margin: 0 0 15px 0; color: #2c3e50;">Escanea el código QR para ver la factura</h3>
                <img src="generar_qr.php?id=<?php echo $factura_id; ?>" alt="Código QR" style="border: 2px solid #dee2e6; border-radius: 8px; padding: 10px; background: white;" />
            </div>
        </div>

        <div class="factura-footer">
            <p>¡Gracias por su compra!</p>
            <p>Sistema de Inventario - <?php echo date('Y'); ?></p>
        </div>
    </div>

    <!-- Scripts para PDF -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>

    <script>
        async function generarPDF() {
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF();

            // Capturar el contenido de la factura
            const element = document.getElementById('factura-content');

            try {
                const canvas = await html2canvas(element, {
                    scale: 2,
                    useCORS: true,
                    allowTaint: true
                });

                const imgData = canvas.toDataURL('image/png');
                const imgWidth = 210; // A4 width in mm
                const pageHeight = 295; // A4 height in mm
                const imgHeight = canvas.height * imgWidth / canvas.width;
                let heightLeft = imgHeight;

                let position = 0;

                // Agregar primera página
                doc.addImage(imgData, 'PNG', 0, position, imgWidth, imgHeight);
                heightLeft -= pageHeight;

                // Agregar páginas adicionales si es necesario
                while (heightLeft >= 0) {
                    position = heightLeft - imgHeight;
                    doc.addPage();
                    doc.addImage(imgData, 'PNG', 0, position, imgWidth, imgHeight);
                    heightLeft -= pageHeight;
                }

                // Descargar el PDF
                doc.save('factura_<?php echo $factura['numero_factura']; ?>.pdf');

            } catch (error) {
                alert('Error al generar el PDF: ' + error.message);
            }
        }
    </script>
</body>
</html>