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
header('Access-Control-Allow-Methods: GET, OPTIONS');
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
        'message' => 'Debes iniciar sesión para ver la confirmación.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$idPedido = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($idPedido <= 0) {
    echo json_encode(['ok' => false, 'message' => 'ID de pedido inválido.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$stmt = $conexion->prepare("SELECT p.id_pedido, p.total, p.fecha, p.estado, p.direccion, u.nombre, u.email FROM pedidos p LEFT JOIN usuarios u ON p.id_usuario = u.id_usuario WHERE p.id_pedido = :id AND p.id_usuario = :id_usuario");
$stmt->bindParam(':id', $idPedido, PDO::PARAM_INT);
$stmt->bindParam(':id_usuario', $_SESSION['usuario_id'], PDO::PARAM_INT);
$stmt->execute();
$pedido = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$pedido) {
    echo json_encode(['ok' => false, 'message' => 'Pedido no encontrado.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$stmtDetalle = $conexion->prepare("SELECT pd.id_detalle, pd.cantidad, pd.precio_unitario, pr.nombre AS producto_nombre, pr.id_producto, t.nombre AS talla_nombre FROM pedido_detalle pd JOIN productos pr ON pd.id_producto = pr.id_producto JOIN tallas t ON pd.id_talla = t.id_talla WHERE pd.id_pedido = :id");
$stmtDetalle->bindParam(':id', $idPedido, PDO::PARAM_INT);
$stmtDetalle->execute();
$detalles = $stmtDetalle->fetchAll(PDO::FETCH_ASSOC);

$stmtValor = $conexion->prepare("SELECT id_valoracion FROM valoraciones WHERE id_pedido = :id_pedido AND id_usuario = :id_usuario LIMIT 1");
$stmtValor->bindParam(':id_pedido', $idPedido, PDO::PARAM_INT);
$stmtValor->bindParam(':id_usuario', $_SESSION['usuario_id'], PDO::PARAM_INT);
$stmtValor->execute();
$yaValoro = (bool)$stmtValor->fetch(PDO::FETCH_ASSOC);

echo json_encode([
    'ok' => true,
    'pedido' => [
        'id_pedido' => (int)$pedido['id_pedido'],
        'total' => (float)$pedido['total'],
        'fecha' => $pedido['fecha'],
        'estado' => $pedido['estado'],
        'direccion' => $pedido['direccion'],
        'nombre' => $pedido['nombre'] ?? '',
        'email' => $pedido['email'] ?? ''
    ],
    'detalles' => array_map(static function ($d) {
        return [
            'id_detalle' => (int)$d['id_detalle'],
            'cantidad' => (int)$d['cantidad'],
            'precio_unitario' => (float)$d['precio_unitario'],
            'producto_nombre' => $d['producto_nombre'],
            'id_producto' => (int)$d['id_producto'],
            'talla_nombre' => $d['talla_nombre']
        ];
    }, $detalles),
    'valoracion' => [
        'yaValoro' => $yaValoro
    ]
], JSON_UNESCAPED_UNICODE);
