<?php
require_once "../config/conexion.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Inicializar carrito en sesión
if (!isset($_SESSION['carrito']) || !is_array($_SESSION['carrito'])) {
    $_SESSION['carrito'] = [];
}

// Obtener datos del formulario
$idProducto = isset($_POST['id_producto']) ? (int)$_POST['id_producto'] : 0;
$idTalla = isset($_POST['id_talla']) ? (int)$_POST['id_talla'] : 0;
$nombre = isset($_POST['nombre']) ? trim($_POST['nombre']) : 'Producto';
$precio = isset($_POST['precio']) ? (float)$_POST['precio'] : 0;
$imagen = isset($_POST['imagen']) ? trim($_POST['imagen']) : '';

// Redireccion por defecto
$redirect = isset($_POST['redirect']) ? $_POST['redirect'] : '../tienda.php';
if (empty($redirect)) {
    $redirect = '../tienda.php';
}

// Validar redirect - si no contiene ../ al inicio, agregarlo
if (strpos($redirect, '../') !== 0 && strpos($redirect, 'http') !== 0) {
    $redirect = '../' . $redirect;
}

// Validación básica
if ($idProducto <= 0 || $idTalla <= 0) {
    header("Location: $redirect");
    exit;
}

// Crear clave única: productId_tallaId
$itemKey = $idProducto . '_' . $idTalla;

// Si ya existe, incrementar cantidad
if (isset($_SESSION['carrito'][$itemKey])) {
    $_SESSION['carrito'][$itemKey]['cantidad']++;
} else {
    // Agregar nuevo producto
    $_SESSION['carrito'][$itemKey] = [
        'id_producto' => $idProducto,
        'id_talla' => $idTalla,
        'nombre' => $nombre,
        'precio' => $precio,
        'cantidad' => 1,
        'imagen' => $imagen,
    ];
}

// Guardar carrito en BD si usuario está autenticado
if (isset($_SESSION['usuario_id']) && $_SESSION['usuario_id'] > 0) {
    try {
        // Aquí podrías guardar el carrito en BD
        // carritoSaveByUser($conexion, $_SESSION['usuario_id'], $_SESSION['carrito']);
    } catch (Exception $e) {
        // Log error silenciosamente
    }
}

// Agregar parámetro de éxito
$separador = str_contains($redirect, '?') ? '&' : '?';
$redirect .= $separador . 'carrito_msg=added';

header("Location: $redirect");
exit;
?>
