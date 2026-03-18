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

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'message' => 'Método no permitido.'], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!isset($_SESSION['carrito']) || !is_array($_SESSION['carrito'])) {
    $_SESSION['carrito'] = [];
}
if (!isset($_SESSION['deseos']) || !is_array($_SESSION['deseos'])) {
    $_SESSION['deseos'] = [];
}

$cantidadCarrito = 0;
foreach ($_SESSION['carrito'] as $item) {
    $cantidadCarrito += (int)($item['cantidad'] ?? 0);
}

$base = [
    'ok' => true,
    'logueado' => isset($_SESSION['usuario_id']),
    'contador' => [
        'carrito' => $cantidadCarrito,
        'deseos' => count($_SESSION['deseos'])
    ]
];

if (!isset($_SESSION['usuario_id'])) {
    echo json_encode($base, JSON_UNESCAPED_UNICODE);
    exit;
}

$idUsuario = (int)$_SESSION['usuario_id'];

try {
    $conexion->exec("ALTER TABLE usuarios ADD COLUMN foto_perfil VARCHAR(255) NULL AFTER password");
} catch (Exception $e) {
}

$stmtPerfil = $conexion->prepare("SELECT id_usuario, nombre, email, rol, foto_perfil FROM usuarios WHERE id_usuario = :id LIMIT 1");
$stmtPerfil->bindParam(':id', $idUsuario, PDO::PARAM_INT);
$stmtPerfil->execute();
$perfil = $stmtPerfil->fetch(PDO::FETCH_ASSOC);

if (!$perfil) {
    echo json_encode($base, JSON_UNESCAPED_UNICODE);
    exit;
}

$_SESSION['usuario_nombre'] = $perfil['nombre'] ?? ($_SESSION['usuario_nombre'] ?? 'Usuario');
$_SESSION['usuario_rol'] = $perfil['rol'] ?? ($_SESSION['usuario_rol'] ?? 'cliente');

$stmtPedidos = $conexion->prepare("SELECT id_pedido, total, estado, fecha FROM pedidos WHERE id_usuario = :id_usuario ORDER BY fecha DESC LIMIT 10");
$stmtPedidos->bindParam(':id_usuario', $idUsuario, PDO::PARAM_INT);
$stmtPedidos->execute();
$historial = $stmtPedidos->fetchAll(PDO::FETCH_ASSOC) ?: [];

$stmtValoraciones = $conexion->prepare("SELECT estrellas, comentario, fecha, id_pedido FROM valoraciones WHERE id_usuario = :id_usuario ORDER BY fecha DESC LIMIT 10");
$stmtValoraciones->bindParam(':id_usuario', $idUsuario, PDO::PARAM_INT);
$stmtValoraciones->execute();
$valoraciones = $stmtValoraciones->fetchAll(PDO::FETCH_ASSOC) ?: [];

$usuario = [
    'id_usuario' => (int)$perfil['id_usuario'],
    'nombre' => (string)($perfil['nombre'] ?? 'Usuario'),
    'email' => (string)($perfil['email'] ?? ''),
    'rol' => (string)($perfil['rol'] ?? 'cliente'),
    'foto_perfil' => trim((string)($perfil['foto_perfil'] ?? '')),
    'password_masked' => '********'
];

echo json_encode(array_merge($base, [
    'usuario' => $usuario,
    'historial_pedidos' => array_map(static function ($item) {
        return [
            'id_pedido' => (int)$item['id_pedido'],
            'total' => (float)$item['total'],
            'estado' => (string)($item['estado'] ?? ''),
            'fecha' => (string)($item['fecha'] ?? '')
        ];
    }, $historial),
    'valoraciones' => array_map(static function ($item) {
        return [
            'id_pedido' => (int)$item['id_pedido'],
            'estrellas' => (int)$item['estrellas'],
            'comentario' => (string)($item['comentario'] ?? ''),
            'fecha' => (string)($item['fecha'] ?? '')
        ];
    }, $valoraciones)
]), JSON_UNESCAPED_UNICODE);
