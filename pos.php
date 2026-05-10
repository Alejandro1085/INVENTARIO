<?php
session_start();

// Verificar si el usuario está logueado
if (!isset($_SESSION['usuario'])) {
    header("Location: index.php");
    exit();
}

require_once 'conexion.php';

$usuario_id = $_SESSION['usuario_id'];
$mensaje = "";
$tipo_mensaje = "";

// Generar número de factura único
function generarNumeroFactura() {
    return 'FAC-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
}

// Procesar formulario de nueva factura
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['crear_factura'])) {
    $cliente_nombre = trim($_POST['cliente_nombre']);
    $cliente_documento = trim($_POST['cliente_documento']);
    $productos_factura = isset($_POST['productos']) ? $_POST['productos'] : [];
    $cantidades = isset($_POST['cantidades']) ? $_POST['cantidades'] : [];
    $descuento = floatval($_POST['descuento']);
    $impuesto = floatval($_POST['impuesto']);
    $metodo_pago = isset($_POST['metodo_pago']) ? $_POST['metodo_pago'] : 'efectivo';

    if (empty($productos_factura)) {
        $mensaje = "Debe agregar al menos un producto a la factura";
        $tipo_mensaje = "error";
    } else {
        // Calcular total
        $total = 0;
        $detalles = [];

        foreach ($productos_factura as $index => $producto_id) {
            $cantidad = intval($cantidades[$index]);

            // Obtener precio del producto
            $sql_precio = "SELECT precio FROM inventario WHERE id = ? AND activo = 1";
            $stmt_precio = $conexion->prepare($sql_precio);
            $stmt_precio->bind_param("i", $producto_id);
            $stmt_precio->execute();
            $resultado_precio = $stmt_precio->get_result();

            if ($resultado_precio->num_rows > 0) {
                $producto = $resultado_precio->fetch_assoc();
                $precio_unitario = $producto['precio'];
                $subtotal = $precio_unitario * $cantidad;
                $total += $subtotal;

                $detalles[] = [
                    'producto_id' => $producto_id,
                    'cantidad' => $cantidad,
                    'precio_unitario' => $precio_unitario,
                    'subtotal' => $subtotal
                ];
            }
            $stmt_precio->close();
        }

        $total_con_descuento = $total - $descuento;
        $total_final = $total_con_descuento + ($total_con_descuento * $impuesto / 100);

        // Crear factura
        $numero_factura = generarNumeroFactura();

        $sql_factura = "INSERT INTO facturas (numero_factura, cliente_nombre, cliente_documento, total, impuesto, descuento, metodo_pago, usuario_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt_factura = $conexion->prepare($sql_factura);
        $stmt_factura->bind_param("sssdddsi", $numero_factura, $cliente_nombre, $cliente_documento, $total_final, $impuesto, $descuento, $metodo_pago, $usuario_id);

        if ($stmt_factura->execute()) {
            $factura_id = $conexion->insert_id;

            // Insertar detalles
            foreach ($detalles as $detalle) {
                $sql_detalle = "INSERT INTO factura_detalles (factura_id, producto_id, cantidad, precio_unitario, subtotal) VALUES (?, ?, ?, ?, ?)";
                $stmt_detalle = $conexion->prepare($sql_detalle);
                $stmt_detalle->bind_param("iiidd", $factura_id, $detalle['producto_id'], $detalle['cantidad'], $detalle['precio_unitario'], $detalle['subtotal']);
                $stmt_detalle->execute();
                $stmt_detalle->close();

                // Actualizar inventario
                $sql_update = "UPDATE inventario SET cantidad = cantidad - ? WHERE id = ?";
                $stmt_update = $conexion->prepare($sql_update);
                $stmt_update->bind_param("ii", $detalle['cantidad'], $detalle['producto_id']);
                $stmt_update->execute();
                $stmt_update->close();
            }

            $mensaje = "✓ Factura creada exitosamente. Número: " . $numero_factura;
            $tipo_mensaje = "success";

            // Redirigir para mostrar la factura
            header("Location: ver_factura.php?id=" . $factura_id);
            exit();
        } else {
            $mensaje = "Error al crear la factura: " . htmlspecialchars($conexion->error);
            $tipo_mensaje = "error";
        }
        $stmt_factura->close();
    }
}

// Obtener productos disponibles
$sql_productos = "SELECT id, codigo, nombre, precio, cantidad FROM inventario WHERE activo = 1 AND cantidad > 0 ORDER BY nombre";
$resultado_productos = $conexion->query($sql_productos);
$productos = $resultado_productos->fetch_all(MYSQLI_ASSOC);

$conexion->close();
?>

<?php
$titulo_pagina = "Sistema POS - Facturación";
require_once 'layout.php';
?>

<div class="content-wrapper">
    <div class="page-header">
        <h1><i class="fas fa-cash-register"></i> Sistema POS - Nueva Factura</h1>
        <div class="header-actions">
            <a href="facturas.php" class="btn btn-secondary">
                <i class="fas fa-list"></i> Ver Facturas
            </a>
        </div>
    </div>

    <?php if (!empty($mensaje)): ?>
        <div class="alert alert-<?php echo $tipo_mensaje; ?>">
            <?php echo htmlspecialchars($mensaje); ?>
        </div>
    <?php endif; ?>

    <form method="POST" id="factura-form">
        <!-- Información del cliente -->
        <div class="form-section">
            <h3>Información del Cliente</h3>
            <div class="form-grid">
                <div class="form-group">
                    <label for="cliente_nombre">Nombre del Cliente</label>
                    <input type="text" id="cliente_nombre" name="cliente_nombre" placeholder="Cliente final" required>
                </div>
                <div class="form-group">
                    <label for="cliente_documento">Documento/ID</label>
                    <input type="text" id="cliente_documento" name="cliente_documento" placeholder="Opcional">
                </div>
                <div class="form-group">
                    <label for="metodo_pago">Método de Pago</label>
                    <select id="metodo_pago" name="metodo_pago" required>
                        <option value="efectivo">Efectivo</option>
                        <option value="tarjeta_credito">Tarjeta de Crédito</option>
                        <option value="tarjeta_debito">Tarjeta de Débito</option>
                        <option value="transferencia">Transferencia Bancaria</option>
                        <option value="cheque">Cheque</option>
                        <option value="otro">Otro</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Selección de productos -->
        <div class="form-section">
            <h3>Productos</h3>
            <div class="productos-grid">
                <?php foreach ($productos as $producto): ?>
                    <div class="producto-card" data-id="<?php echo $producto['id']; ?>" data-nombre="<?php echo htmlspecialchars($producto['nombre']); ?>" data-precio="<?php echo $producto['precio']; ?>" data-stock="<?php echo $producto['cantidad']; ?>">
                        <div class="producto-info">
                            <h4><?php echo htmlspecialchars($producto['nombre']); ?></h4>
                            <p class="codigo">Código: <?php echo htmlspecialchars($producto['codigo']); ?></p>
                            <p class="precio">$<?php echo number_format($producto['precio'], 2); ?></p>
                            <p class="stock">Stock: <?php echo $producto['cantidad']; ?></p>
                        </div>
                        <button type="button" class="btn btn-primary btn-sm agregar-producto">
                            <i class="fas fa-plus"></i> Agregar
                        </button>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Carrito de compras -->
        <div class="form-section">
            <h3>Carrito de Compras</h3>
            <div class="carrito-container">
                <table id="carrito-table">
                    <thead>
                        <tr>
                            <th>Producto</th>
                            <th>Cantidad</th>
                            <th>Precio Unit.</th>
                            <th>Subtotal</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="carrito-body">
                        <!-- Los productos se agregarán aquí dinámicamente -->
                    </tbody>
                </table>
            </div>

            <!-- Totales -->
            <div class="totales-container">
                <div class="total-row">
                    <label for="descuento">Descuento ($):</label>
                    <input type="number" id="descuento" name="descuento" value="0" min="0" step="0.01">
                </div>
                <div class="total-row">
                    <label for="impuesto">Impuesto (%):</label>
                    <input type="number" id="impuesto" name="impuesto" value="0" min="0" step="0.01">
                </div>
                <div class="total-row total-final">
                    <strong>Total: $<span id="total-final">0.00</span></strong>
                </div>
            </div>
        </div>

        <div class="form-actions">
            <button type="submit" name="crear_factura" class="btn btn-primary btn-large">
                <i class="fas fa-save"></i> Crear Factura
            </button>
            <button type="button" onclick="limpiarCarrito()" class="btn btn-secondary">
                <i class="fas fa-trash"></i> Limpiar Carrito
            </button>
        </div>

        <!-- Campos ocultos para productos -->
        <div id="productos-hidden"></div>
    </form>
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

.productos-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.producto-card {
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 15px;
    background: white;
    transition: box-shadow 0.3s ease;
}

.producto-card:hover {
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.producto-info h4 {
    margin: 0 0 10px 0;
    color: #2c3e50;
}

.producto-info .codigo {
    color: #666;
    font-size: 14px;
    margin: 5px 0;
}

.producto-info .precio {
    font-size: 18px;
    font-weight: bold;
    color: #28a745;
    margin: 10px 0;
}

.producto-info .stock {
    color: #666;
    font-size: 14px;
}

.carrito-container {
    margin-bottom: 30px;
}

#carrito-table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 20px;
}

#carrito-table th, #carrito-table td {
    padding: 12px;
    text-align: left;
    border-bottom: 1px solid #ddd;
}

#carrito-table th {
    background-color: #f8f9fa;
    font-weight: 600;
}

.totales-container {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 8px;
    max-width: 400px;
    margin-left: auto;
}

.total-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
}

.total-row input {
    width: 100px;
    text-align: right;
}

.total-final {
    font-size: 20px;
    color: #28a745;
    border-top: 2px solid #dee2e6;
    padding-top: 15px;
    margin-top: 15px;
}

.btn-large {
    padding: 15px 30px;
    font-size: 16px;
}
</style>

<script>
let carrito = [];

function agregarProducto(id, nombre, precio, stock) {
    const cantidad = prompt(`¿Cuántas unidades de "${nombre}"? (Stock disponible: ${stock})`);
    if (cantidad && cantidad > 0 && cantidad <= stock) {
        const productoExistente = carrito.find(p => p.id === id);
        if (productoExistente) {
            productoExistente.cantidad += parseInt(cantidad);
        } else {
            carrito.push({
                id: id,
                nombre: nombre,
                precio: parseFloat(precio),
                cantidad: parseInt(cantidad)
            });
        }
        actualizarCarrito();
    }
}

function actualizarCarrito() {
    const tbody = document.getElementById('carrito-body');
    const hiddenContainer = document.getElementById('productos-hidden');

    tbody.innerHTML = '';
    hiddenContainer.innerHTML = '';

    let total = 0;

    carrito.forEach((producto, index) => {
        const subtotal = producto.precio * producto.cantidad;
        total += subtotal;

        tbody.innerHTML += `
            <tr>
                <td>${producto.nombre}</td>
                <td>
                    <input type="number" value="${producto.cantidad}" min="1"
                           onchange="cambiarCantidad(${index}, this.value)" style="width: 60px;">
                </td>
                <td>$${producto.precio.toFixed(2)}</td>
                <td>$${subtotal.toFixed(2)}</td>
                <td>
                    <button type="button" onclick="eliminarProducto(${index})" class="btn btn-danger btn-sm">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            </tr>
        `;

        hiddenContainer.innerHTML += `
            <input type="hidden" name="productos[]" value="${producto.id}">
            <input type="hidden" name="cantidades[]" value="${producto.cantidad}">
        `;
    });

    calcularTotal();
}

function cambiarCantidad(index, nuevaCantidad) {
    if (nuevaCantidad > 0) {
        carrito[index].cantidad = parseInt(nuevaCantidad);
        actualizarCarrito();
    }
}

function eliminarProducto(index) {
    carrito.splice(index, 1);
    actualizarCarrito();
}

function calcularTotal() {
    let subtotal = 0;
    carrito.forEach(producto => {
        subtotal += producto.precio * producto.cantidad;
    });

    const descuento = parseFloat(document.getElementById('descuento').value) || 0;
    const impuesto = parseFloat(document.getElementById('impuesto').value) || 0;

    const subtotalConDescuento = subtotal - descuento;
    const totalFinal = subtotalConDescuento + (subtotalConDescuento * impuesto / 100);

    document.getElementById('total-final').textContent = totalFinal.toFixed(2);
}

function limpiarCarrito() {
    if (confirm('¿Estás seguro de limpiar el carrito?')) {
        carrito = [];
        actualizarCarrito();
    }
}

// Event listeners
document.addEventListener('DOMContentLoaded', function() {
    // Agregar event listeners a los botones de productos
    document.querySelectorAll('.agregar-producto').forEach(btn => {
        btn.addEventListener('click', function() {
            const card = this.closest('.producto-card');
            const id = card.dataset.id;
            const nombre = card.dataset.nombre;
            const precio = card.dataset.precio;
            const stock = card.dataset.stock;
            agregarProducto(id, nombre, precio, stock);
        });
    });

    // Calcular total cuando cambien descuento o impuesto
    document.getElementById('descuento').addEventListener('input', calcularTotal);
    document.getElementById('impuesto').addEventListener('input', calcularTotal);
});
</script>

<?php include 'layout_footer.php'; ?>