<?php
require_once "../config/conexion.php";

if (PHP_SESSION_NONE === session_status()) {
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => $isHttps,
        'httponly' => true,
        'samesite' => $isHttps ? 'None' : 'Lax'
    ]);
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if ($origin !== '' && preg_match('#^https?://(localhost|127\.0\.0\.1)(:\d+)?$|^https?://([a-z0-9-]+\.)*vercel\.app$|^https?://([a-z0-9-]+\.)*masterendaw\.es$#i', $origin)) {
    header('Access-Control-Allow-Origin: ' . $origin);
    header('Access-Control-Allow-Credentials: true');
}
header('Vary: Origin');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['ok' => false, 'requiresLogin' => true, 'message' => 'Debes iniciar sesión.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$payload = json_decode(file_get_contents('php://input'), true);
if (!is_array($payload)) {
    $payload = [];
}

$idUsuario = (int)$_SESSION['usuario_id'];
$idPedido = (int)($payload['id_pedido'] ?? 0);
$estrellas = (int)($payload['estrellas'] ?? 0);
$comentario = trim((string)($payload['comentario'] ?? ''));

if ($idPedido <= 0 || $estrellas < 1 || $estrellas > 5) {
    echo json_encode(['ok' => false, 'message' => 'Datos de valoración inválidos.'], JSON_UNESCAPED_UNICODE);
    exit;
}

if (mb_strlen($comentario) > 500) {
    $comentario = mb_substr($comentario, 0, 500);
}

$stmtPedido = $conexion->prepare("SELECT id_pedido FROM pedidos WHERE id_pedido = :id_pedido AND id_usuario = :id_usuario LIMIT 1");
$stmtPedido->bindParam(':id_pedido', $idPedido, PDO::PARAM_INT);
$stmtPedido->bindParam(':id_usuario', $idUsuario, PDO::PARAM_INT);
$stmtPedido->execute();

if (!$stmtPedido->fetch(PDO::FETCH_ASSOC)) {
    echo json_encode(['ok' => false, 'message' => 'Pedido no válido para valorar.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$stmtExiste = $conexion->prepare("SELECT id_valoracion FROM valoraciones WHERE id_usuario = :id_usuario AND id_pedido = :id_pedido LIMIT 1");
$stmtExiste->bindParam(':id_usuario', $idUsuario, PDO::PARAM_INT);
$stmtExiste->bindParam(':id_pedido', $idPedido, PDO::PARAM_INT);
$stmtExiste->execute();
$existe = $stmtExiste->fetch(PDO::FETCH_ASSOC);

if ($existe) {
    $stmtUpdate = $conexion->prepare("UPDATE valoraciones SET estrellas = :estrellas, comentario = :comentario, fecha = CURRENT_TIMESTAMP WHERE id_valoracion = :id_valoracion");
    $stmtUpdate->bindParam(':estrellas', $estrellas, PDO::PARAM_INT);
    $stmtUpdate->bindParam(':comentario', $comentario);
    $stmtUpdate->bindParam(':id_valoracion', $existe['id_valoracion'], PDO::PARAM_INT);
    $stmtUpdate->execute();
} else {
    $stmtInsert = $conexion->prepare("INSERT INTO valoraciones (id_usuario, id_pedido, estrellas, comentario) VALUES (:id_usuario, :id_pedido, :estrellas, :comentario)");
    $stmtInsert->bindParam(':id_usuario', $idUsuario, PDO::PARAM_INT);
    $stmtInsert->bindParam(':id_pedido', $idPedido, PDO::PARAM_INT);
    $stmtInsert->bindParam(':estrellas', $estrellas, PDO::PARAM_INT);
    $stmtInsert->bindParam(':comentario', $comentario);
    $stmtInsert->execute();
}

echo json_encode(['ok' => true, 'message' => 'Valoración guardada correctamente.'], JSON_UNESCAPED_UNICODE);
