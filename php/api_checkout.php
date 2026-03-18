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
        'message' => 'Debes iniciar sesión para acceder al checkout.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!isset($_SESSION['carrito']) || !is_array($_SESSION['carrito'])) {
    $_SESSION['carrito'] = [];
}

function getCheckoutData(PDO $conexion): array
{
    $carrito = $_SESSION['carrito'] ?? [];
    $tallasNombres = [];
    $total = 0;
    $items = [];

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

    foreach ($carrito as $item) {
        $cantidad = (int)($item['cantidad'] ?? 0);
        $precio = (float)($item['precio'] ?? 0);
        $subtotal = $precio * $cantidad;
        $total += $subtotal;

        $idTalla = (int)($item['id_talla'] ?? 0);
        $items[] = [
            'id_producto' => (int)($item['id_producto'] ?? 0),
            'id_talla' => $idTalla,
            'nombre' => (string)($item['nombre'] ?? 'Producto'),
            'cantidad' => $cantidad,
            'precio' => $precio,
            'subtotal' => $subtotal,
            'talla' => $tallasNombres[$idTalla] ?? 'N/A'
        ];
    }

    $stmtUsuario = $conexion->prepare("SELECT nombre, email FROM usuarios WHERE id_usuario = :id");
    $stmtUsuario->bindParam(':id', $_SESSION['usuario_id'], PDO::PARAM_INT);
    $stmtUsuario->execute();
    $usuario = $stmtUsuario->fetch(PDO::FETCH_ASSOC) ?: ['nombre' => '', 'email' => ''];

    return [
        'usuario' => [
            'nombre' => $usuario['nombre'] ?? '',
            'email' => $usuario['email'] ?? ''
        ],
        'items' => $items,
        'total' => $total,
        'isEmpty' => empty($items)
    ];
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $data = getCheckoutData($conexion);
    echo json_encode([
        'ok' => true,
        'checkout' => $data
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$payload = json_decode(file_get_contents('php://input'), true);
if (!is_array($payload)) {
    $payload = [];
}

$email = trim((string)($payload['email'] ?? ''));
$password = (string)($payload['password'] ?? '');
$calle = trim((string)($payload['calle'] ?? ''));
$ciudad = trim((string)($payload['ciudad'] ?? ''));
$codigoPostal = trim((string)($payload['codigo_postal'] ?? ''));
$pais = trim((string)($payload['pais'] ?? ''));

if (empty($_SESSION['carrito'])) {
    echo json_encode(['ok' => false, 'message' => 'Tu carrito está vacío.'], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($email === '' || $password === '' || $calle === '' || $ciudad === '' || $codigoPostal === '' || $pais === '') {
    echo json_encode(['ok' => false, 'message' => 'Todos los campos de dirección son requeridos.'], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['ok' => false, 'message' => 'Email inválido.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$stmt = $conexion->prepare("SELECT id_usuario, password FROM usuarios WHERE email = :email LIMIT 1");
$stmt->bindParam(':email', $email);
$stmt->execute();
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$usuario || !password_verify($password, $usuario['password'])) {
    echo json_encode(['ok' => false, 'message' => 'Email o contraseña incorrectos.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$idUsuario = (int)$usuario['id_usuario'];
$direccion = $calle . ', ' . $codigoPostal . ' ' . $ciudad . ', ' . $pais;
$total = 0;
foreach ($_SESSION['carrito'] as $item) {
    $total += (float)$item['precio'] * (int)$item['cantidad'];
}

try {
    $conexion->beginTransaction();

    $stmtPedido = $conexion->prepare("INSERT INTO pedidos (id_usuario, direccion, total, estado) VALUES (:id_usuario, :direccion, :total, 'pagado')");
    $stmtPedido->bindParam(':id_usuario', $idUsuario, PDO::PARAM_INT);
    $stmtPedido->bindParam(':direccion', $direccion);
    $stmtPedido->bindParam(':total', $total);
    $stmtPedido->execute();

    $idPedido = (int)$conexion->lastInsertId();

    $stmtDetalle = $conexion->prepare("INSERT INTO pedido_detalle (id_pedido, id_producto, id_talla, cantidad, precio_unitario) VALUES (:id_pedido, :id_producto, :id_talla, :cantidad, :precio_unitario)");

    foreach ($_SESSION['carrito'] as $item) {
        $idProducto = (int)($item['id_producto'] ?? 0);
        $idTalla = (int)($item['id_talla'] ?? 0);
        $cantidad = (int)($item['cantidad'] ?? 0);
        $precio = (float)($item['precio'] ?? 0);

        $stmtDetalle->bindParam(':id_pedido', $idPedido, PDO::PARAM_INT);
        $stmtDetalle->bindParam(':id_producto', $idProducto, PDO::PARAM_INT);
        $stmtDetalle->bindParam(':id_talla', $idTalla, PDO::PARAM_INT);
        $stmtDetalle->bindParam(':cantidad', $cantidad, PDO::PARAM_INT);
        $stmtDetalle->bindParam(':precio_unitario', $precio);
        $stmtDetalle->execute();
    }

    $conexion->commit();
    unset($_SESSION['carrito']);

    echo json_encode([
        'ok' => true,
        'id_pedido' => $idPedido,
        'redirect' => 'http://localhost/veridi_tienda_web/confirmacion_pedido.php?id=' . $idPedido
    ], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    if ($conexion->inTransaction()) {
        $conexion->rollBack();
    }

    echo json_encode([
        'ok' => false,
        'message' => 'Error al procesar el pago. Inténtalo de nuevo.'
    ], JSON_UNESCAPED_UNICODE);
}
