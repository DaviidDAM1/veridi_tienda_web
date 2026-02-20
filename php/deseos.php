<?php
require_once "../config/conexion.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['deseos']) || !is_array($_SESSION['deseos'])) {
    $_SESSION['deseos'] = [];
}

if (!isset($_SESSION['carrito']) || !is_array($_SESSION['carrito'])) {
    $_SESSION['carrito'] = [];
}

$action = $_POST['action'] ?? '';
$idProducto = isset($_POST['id_producto']) ? (int)$_POST['id_producto'] : 0;
$redirect = $_POST['redirect'] ?? '../lista-deseos.php';

if ($redirect === '') {
    $redirect = '../lista-deseos.php';
}

// Validar redirect - si no contiene ../ al inicio, agregarlo
if (!empty($redirect) && strpos($redirect, '../') !== 0 && strpos($redirect, 'http') !== 0) {
    $redirect = '../' . $redirect;
}

switch ($action) {
    case 'add':
        if ($idProducto <= 0) {
            break;
        }

        $nombre = trim($_POST['nombre'] ?? 'Producto');
        $precio = isset($_POST['precio']) ? (float)$_POST['precio'] : 0;
        $imagen = trim($_POST['imagen'] ?? '');

        $_SESSION['deseos'][$idProducto] = [
            'id_producto' => $idProducto,
            'nombre' => $nombre,
            'precio' => $precio,
            'imagen' => $imagen,
        ];

        $separador = str_contains($redirect, '?') ? '&' : '?';
        $redirect .= $separador . 'deseos_msg=added';
        break;

    case 'remove':
        if ($idProducto > 0 && isset($_SESSION['deseos'][$idProducto])) {
            unset($_SESSION['deseos'][$idProducto]);
        }
        break;

    case 'move_to_cart':
        if ($idProducto > 0 && isset($_SESSION['deseos'][$idProducto])) {
            // Simplemente redirigir a la página del producto para que seleccione la talla
            header("Location: ../producto-detalle.php?id=$idProducto");
            exit();
        }
        break;

    default:
        break;
}

// El carrito y deseos se mantienen en sesión
// (Se pueden guardar en BD si es necesario en el futuro)

header('Location: ' . $redirect);
exit();
