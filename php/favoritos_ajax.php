<?php
require_once "../config/conexion.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

if (!isset($_SESSION['deseos']) || !is_array($_SESSION['deseos'])) {
    $_SESSION['deseos'] = [];
}

$action = $_POST['action'] ?? '';
$idProducto = isset($_POST['id_producto']) ? (int)$_POST['id_producto'] : 0;
$response = ['success' => false, 'message' => '', 'esFavorito' => false];

switch ($action) {
    case 'add':
        if ($idProducto <= 0) {
            $response['message'] = 'Producto inválido';
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

        $response['success'] = true;
        $response['message'] = 'Agregado a favoritos';
        $response['esFavorito'] = true;
        break;

    case 'remove':
        if ($idProducto > 0) {
            // Buscar y eliminar por id_producto
            $encontrado = false;
            foreach ($_SESSION['deseos'] as $key => $fav) {
                if ($fav['id_producto'] == $idProducto) {
                    unset($_SESSION['deseos'][$key]);
                    $encontrado = true;
                    break;
                }
            }
            
            // También intentar eliminarlo como clave directa
            if (isset($_SESSION['deseos'][$idProducto])) {
                unset($_SESSION['deseos'][$idProducto]);
                $encontrado = true;
            }
            
            if ($encontrado) {
                $response['success'] = true;
                $response['message'] = 'Eliminado de favoritos';
                $response['esFavorito'] = false;
            } else {
                $response['success'] = true; // Aceptar aunque no se encontrara
                $response['message'] = 'Ya no está en favoritos';
                $response['esFavorito'] = false;
            }
        }
        break;

    case 'check':
        // Verificar si está en favoritos
        if ($idProducto > 0) {
            $esFav = false;
            
            // Buscar por id_producto en los valores
            foreach ($_SESSION['deseos'] as $fav) {
                if ($fav['id_producto'] == $idProducto) {
                    $esFav = true;
                    break;
                }
            }
            
            // También verificar como clave
            if (isset($_SESSION['deseos'][$idProducto])) {
                $esFav = true;
            }
            
            $response['success'] = true;
            $response['esFavorito'] = $esFav;
        }
        break;

    default:
        $response['message'] = 'Acción no reconocida';
}

echo json_encode($response);
exit;
?>
