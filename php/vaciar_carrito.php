<?php
require_once "../config/conexion.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Vaciar carrito
$_SESSION['carrito'] = [];

// Guardar en BD si está autenticado
if (isset($_SESSION['usuario_id']) && $_SESSION['usuario_id'] > 0) {
    try {
        // carritoSaveByUser($conexion, $_SESSION['usuario_id'], []);
    } catch (Exception $e) {
        // Log error silenciosamente
    }
}

$redirect = isset($_POST['redirect']) ? $_POST['redirect'] : '../carrito.php';
if (empty($redirect)) {
    $redirect = '../carrito.php';
}

// Validar redirect - si no contiene ../ al inicio, agregarlo
if (strpos($redirect, '../') !== 0 && strpos($redirect, 'http') !== 0) {
    $redirect = '../' . $redirect;
}

$separador = str_contains($redirect, '?') ? '&' : '?';
$redirect .= $separador . 'carrito_msg=vaciar';

header("Location: $redirect");
exit;
?>
