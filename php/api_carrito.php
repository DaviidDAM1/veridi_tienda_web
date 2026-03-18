<?php
require_once "../config/conexion.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if ($origin === 'http://localhost:5173' || $origin === 'http://127.0.0.1:5173') {
    header('Access-Control-Allow-Origin: ' . $origin);
    header('Access-Control-Allow-Credentials: true');
}
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if (!isset($_SESSION['usuario_id'])) {
    echo json_encode([
        'ok' => false,
        'requiresLogin' => true,
        'message' => 'Debes iniciar sesión para ver el carrito.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!isset($_SESSION['carrito']) || !is_array($_SESSION['carrito'])) {
    $_SESSION['carrito'] = [];
}

function outputCart(PDO $conexion): void
{
    $carrito = $_SESSION['carrito'] ?? [];
    $tallasNombres = [];
    $total = 0;

    $idsTallas = [];
    foreach ($carrito as $item) {
        if (!empty($item['id_talla'])) {
            $idsTallas[] = (int)$item['id_talla'];
        }
    }

    if (!empty($idsTallas)) {
        $idsTallas = array_values(array_unique($idsTallas));
        $placeholders = implode(',', array_fill(0, count($idsTallas), '?'));
        $stmt = $conexion->prepare("SELECT id_talla, nombre FROM tallas WHERE id_talla IN ($placeholders)");
        $stmt->execute($idsTallas);
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $tallasNombres[(int)$row['id_talla']] = $row['nombre'];
        }
    }

    $itemsOut = [];
    foreach ($carrito as $itemKey => $item) {
        $cantidad = (int)($item['cantidad'] ?? 0);
        $precio = (float)($item['precio'] ?? 0);
        $subtotal = $cantidad * $precio;
        $total += $subtotal;

        $idTalla = (int)($item['id_talla'] ?? 0);
        $itemsOut[] = [
            'item_key' => $itemKey,
            'id_producto' => (int)($item['id_producto'] ?? 0),
            'id_talla' => $idTalla,
            'nombre' => (string)($item['nombre'] ?? 'Producto'),
            'precio' => $precio,
            'cantidad' => $cantidad,
            'imagen' => (string)($item['imagen'] ?? ''),
            'subtotal' => $subtotal,
            'talla_nombre' => $idTalla > 0 ? ($tallasNombres[$idTalla] ?? ('ID: ' . $idTalla)) : ''
        ];
    }

    echo json_encode([
        'ok' => true,
        'items' => $itemsOut,
        'total' => $total,
        'totalItems' => count($itemsOut)
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    outputCart($conexion);
}

$rawBody = file_get_contents('php://input');
$payload = [];
if ($rawBody) {
    $json = json_decode($rawBody, true);
    if (is_array($json)) {
        $payload = $json;
    }
}

$action = trim((string)($payload['action'] ?? ($_POST['action'] ?? '')));
$idProducto = isset($payload['id_producto']) ? (int)$payload['id_producto'] : (isset($_POST['id_producto']) ? (int)$_POST['id_producto'] : 0);
$idTalla = isset($payload['id_talla']) ? (int)$payload['id_talla'] : (isset($_POST['id_talla']) ? (int)$_POST['id_talla'] : 0);
$delta = isset($payload['delta']) ? (int)$payload['delta'] : (isset($_POST['delta']) ? (int)$_POST['delta'] : 0);

$itemKey = $idProducto . '_' . $idTalla;

switch ($action) {
    case 'add_item':
        if ($idProducto <= 0 || $idTalla <= 0) {
            echo json_encode([
                'ok' => false,
                'message' => 'Selecciona un producto y talla válidos.'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $stmtStock = $conexion->prepare("SELECT stock FROM producto_tallas WHERE id_producto = :id_producto AND id_talla = :id_talla LIMIT 1");
        $stmtStock->bindParam(':id_producto', $idProducto, PDO::PARAM_INT);
        $stmtStock->bindParam(':id_talla', $idTalla, PDO::PARAM_INT);
        $stmtStock->execute();
        $filaStock = $stmtStock->fetch(PDO::FETCH_ASSOC);

        if (!$filaStock) {
            echo json_encode([
                'ok' => false,
                'message' => 'La talla seleccionada no está disponible para este producto.'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $stockDisponible = max(0, (int)$filaStock['stock']);
        if ($stockDisponible <= 0) {
            echo json_encode([
                'ok' => false,
                'message' => 'No hay stock disponible para la talla seleccionada.'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $nombre = trim((string)($payload['nombre'] ?? 'Producto'));
        $precio = (float)($payload['precio'] ?? 0);
        $imagen = trim((string)($payload['imagen'] ?? ''));

        if (!isset($_SESSION['carrito'][$itemKey])) {
            $_SESSION['carrito'][$itemKey] = [
                'id_producto' => $idProducto,
                'id_talla' => $idTalla,
                'nombre' => $nombre,
                'precio' => $precio,
                'imagen' => $imagen,
                'cantidad' => 1
            ];
        } else {
            $cantidadActual = (int)($_SESSION['carrito'][$itemKey]['cantidad'] ?? 0);
            if ($cantidadActual < $stockDisponible) {
                $_SESSION['carrito'][$itemKey]['cantidad'] = $cantidadActual + 1;
            }
        }

        if ((int)$_SESSION['carrito'][$itemKey]['cantidad'] > $stockDisponible) {
            $_SESSION['carrito'][$itemKey]['cantidad'] = $stockDisponible;
        }
        break;

    case 'update_quantity':
        if ($idProducto > 0 && $idTalla > 0 && isset($_SESSION['carrito'][$itemKey])) {
            $stmtStock = $conexion->prepare("SELECT stock FROM producto_tallas WHERE id_producto = :id_producto AND id_talla = :id_talla LIMIT 1");
            $stmtStock->bindParam(':id_producto', $idProducto, PDO::PARAM_INT);
            $stmtStock->bindParam(':id_talla', $idTalla, PDO::PARAM_INT);
            $stmtStock->execute();
            $filaStock = $stmtStock->fetch(PDO::FETCH_ASSOC);
            $stockDisponible = $filaStock ? max(0, (int)$filaStock['stock']) : 0;

            $_SESSION['carrito'][$itemKey]['cantidad'] += $delta;
            if ($stockDisponible > 0 && (int)$_SESSION['carrito'][$itemKey]['cantidad'] > $stockDisponible) {
                $_SESSION['carrito'][$itemKey]['cantidad'] = $stockDisponible;
            }
            if ((int)$_SESSION['carrito'][$itemKey]['cantidad'] <= 0) {
                unset($_SESSION['carrito'][$itemKey]);
            }
        }
        break;

    case 'remove_item':
        if ($idProducto > 0 && $idTalla > 0 && isset($_SESSION['carrito'][$itemKey])) {
            unset($_SESSION['carrito'][$itemKey]);
        }
        break;

    case 'clear_cart':
        $_SESSION['carrito'] = [];
        break;

    default:
        echo json_encode([
            'ok' => false,
            'message' => 'Acción no válida.'
        ], JSON_UNESCAPED_UNICODE);
        exit;
}

outputCart($conexion);
