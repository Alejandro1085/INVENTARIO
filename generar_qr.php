<?php
require_once 'libs/phpqrcode-master/qrlib.php';

// Obtener el ID de la factura
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) {
    die('ID inválido');
}

// URL de la factura
$host = $_SERVER['HTTP_HOST'];
if ($host === 'localhost' || $host === '127.0.0.1') {
    $ips = gethostbynamel(gethostname());
    foreach ($ips as $ip) {
        if ($ip !== '127.0.0.1') {
            $host = $ip;
            break;
        }
    }
}
$url = 'http://' . $host . '/P%20PERSON/ver_factura_publica.php?id=' . $id;

// Generar código QR
QRcode::png($url, false, QR_ECLEVEL_L, 10, 4);
?>
