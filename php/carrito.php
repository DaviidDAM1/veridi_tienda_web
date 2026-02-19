<?php
require_once "../config/conexion.php";
require_once "carrito_storage.php";

if (session_status() === PHP_SESSION_NONE) {
	session_start();
}

if (!isset($_SESSION['carrito']) || !is_array($_SESSION['carrito'])) {
	$_SESSION['carrito'] = [];
}

$action = $_POST['action'] ?? '';
$idProducto = isset($_POST['id_producto']) ? (int)$_POST['id_producto'] : 0;
$redirect = $_POST['redirect'] ?? '../carrito.php';

if ($redirect === '') {
	$redirect = '../carrito.php';
}

switch ($action) {
	case 'add':
		if ($idProducto <= 0) {
			break;
		}

		$nombre = trim($_POST['nombre'] ?? 'Producto');
		$precio = isset($_POST['precio']) ? (float)$_POST['precio'] : 0;
		$imagen = trim($_POST['imagen'] ?? '');

		if (isset($_SESSION['carrito'][$idProducto])) {
			$_SESSION['carrito'][$idProducto]['cantidad']++;
		} else {
			$_SESSION['carrito'][$idProducto] = [
				'id_producto' => $idProducto,
				'nombre' => $nombre,
				'precio' => $precio,
				'cantidad' => 1,
				'imagen' => $imagen,
			];
		}

		$separador = str_contains($redirect, '?') ? '&' : '?';
		$redirect .= $separador . 'carrito_msg=added';
		break;

	case 'delta':
		$delta = isset($_POST['delta']) ? (int)$_POST['delta'] : 0;
		if ($idProducto > 0 && isset($_SESSION['carrito'][$idProducto]) && $delta !== 0) {
			$_SESSION['carrito'][$idProducto]['cantidad'] += $delta;
			if ($_SESSION['carrito'][$idProducto]['cantidad'] <= 0) {
				unset($_SESSION['carrito'][$idProducto]);
			}
		}
		break;

	case 'remove':
		if ($idProducto > 0 && isset($_SESSION['carrito'][$idProducto])) {
			unset($_SESSION['carrito'][$idProducto]);
		}
		break;

	case 'pay':
		if (!empty($_SESSION['carrito'])) {
			$_SESSION['carrito'] = [];
		}
		$separador = str_contains($redirect, '?') ? '&' : '?';
		$redirect .= $separador . 'carrito_msg=paid';
		break;

	default:
		break;
}

if (isset($_SESSION['usuario_id'])) {
	carritoSaveByUser($conexion, (int)$_SESSION['usuario_id'], $_SESSION['carrito']);
}

header('Location: ' . $redirect);
exit();
?>
