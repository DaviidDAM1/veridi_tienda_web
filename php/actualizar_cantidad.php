<?php
require_once "../config/conexion.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Inicializar carrito
if (!isset($_SESSION['carrito']) || !is_array($_SESSION['carrito'])) {
    $_SESSION['carrito'] = [];
    header("Location: ../carrito.php");
    exit;
}

// Obtener datos
$idProducto = isset($_POST['id_producto']) ? (int)$_POST['id_producto'] : 0;
$idTalla = isset($_POST['id_talla']) ? (int)$_POST['id_talla'] : 0;
$delta = isset($_POST['delta']) ? (int)$_POST['delta'] : 0;
$redirect = isset($_POST['redirect']) ? $_POST['redirect'] : '../carrito.php';

// Validar redirect - si no está vacío y no contiene ../ al inicio, agregarlo
if (!empty($redirect) && strpos($redirect, '../') !== 0 && strpos($redirect, 'http') !== 0) {
    $redirect = '../' . $redirect;
}
if (empty($redirect)) {
    $redirect = '../carrito.php';
}

$itemKey = $idProducto . '_' . $idTalla;

// Actualizar cantidad si el producto existe
if ($idProducto > 0 && $idTalla > 0 && isset($_SESSION['carrito'][$itemKey])) {
    $_SESSION['carrito'][$itemKey]['cantidad'] += $delta;
    
    // Eliminar si cantidad llega a 0 o menos
    if ($_SESSION['carrito'][$itemKey]['cantidad'] <= 0) {
        unset($_SESSION['carrito'][$itemKey]);
    }
}

// Guardar en BD si está autenticado
if (isset($_SESSION['usuario_id']) && $_SESSION['usuario_id'] > 0) {
    try {
        // carritoSaveByUser($conexion, $_SESSION['usuario_id'], $_SESSION['carrito']);
    } catch (Exception $e) {
        // Log error silenciosamente
    }
}

header("Location: $redirect");
exit;
?>
