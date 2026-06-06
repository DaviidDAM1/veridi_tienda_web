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

function ensureValoracionesProductoSchema(PDO $conexion): void
{
    $conexion->exec(
        "CREATE TABLE IF NOT EXISTS valoraciones_producto (
            id_valoracion_producto INT AUTO_INCREMENT PRIMARY KEY,
            id_usuario INT NOT NULL,
            id_producto INT NOT NULL,
            id_pedido INT NULL,
            estrellas TINYINT NOT NULL,
            comentario TEXT NULL,
            fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            CONSTRAINT chk_valoracion_producto_estrellas CHECK (estrellas BETWEEN 1 AND 5),
            UNIQUE KEY uk_valoracion_producto_usuario_producto (id_usuario, id_producto),
            INDEX idx_valoracion_producto_producto (id_producto),
            INDEX idx_valoracion_producto_fecha (fecha),
            CONSTRAINT fk_valoracion_producto_usuario FOREIGN KEY (id_usuario)
                REFERENCES usuarios(id_usuario) ON DELETE CASCADE,
            CONSTRAINT fk_valoracion_producto_producto FOREIGN KEY (id_producto)
                REFERENCES productos(id_producto) ON DELETE CASCADE,
            CONSTRAINT fk_valoracion_producto_pedido FOREIGN KEY (id_pedido)
                REFERENCES pedidos(id_pedido) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    );
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
$idProducto = (int)($payload['id_producto'] ?? 0);
$idPedido = (int)($payload['id_pedido'] ?? 0);
$estrellas = (int)($payload['estrellas'] ?? 0);
$comentario = trim((string)($payload['comentario'] ?? ''));

if ($idProducto <= 0 || $estrellas < 1 || $estrellas > 5) {
    echo json_encode(['ok' => false, 'message' => 'Datos de valoración inválidos.'], JSON_UNESCAPED_UNICODE);
    exit;
}

if (mb_strlen($comentario) > 500) {
    $comentario = mb_substr($comentario, 0, 500);
}

ensureValoracionesProductoSchema($conexion);

$sqlCompra = "
    SELECT 1
    FROM pedidos p
    INNER JOIN pedido_detalle pd ON pd.id_pedido = p.id_pedido
    WHERE p.id_usuario = :id_usuario
      AND pd.id_producto = :id_producto
      AND p.estado <> 'cancelado'
";
if ($idPedido > 0) {
    $sqlCompra .= " AND p.id_pedido = :id_pedido";
}
$sqlCompra .= " LIMIT 1";

$stmtCompra = $conexion->prepare($sqlCompra);
$stmtCompra->bindParam(':id_usuario', $idUsuario, PDO::PARAM_INT);
$stmtCompra->bindParam(':id_producto', $idProducto, PDO::PARAM_INT);
if ($idPedido > 0) {
    $stmtCompra->bindParam(':id_pedido', $idPedido, PDO::PARAM_INT);
}
$stmtCompra->execute();

if (!$stmtCompra->fetch(PDO::FETCH_ASSOC)) {
    echo json_encode(['ok' => false, 'message' => 'Solo puedes valorar productos que hayas comprado.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$stmtExiste = $conexion->prepare(
    "SELECT id_valoracion_producto
     FROM valoraciones_producto
     WHERE id_usuario = :id_usuario AND id_producto = :id_producto
     LIMIT 1"
);
$stmtExiste->bindParam(':id_usuario', $idUsuario, PDO::PARAM_INT);
$stmtExiste->bindParam(':id_producto', $idProducto, PDO::PARAM_INT);
$stmtExiste->execute();
$existe = $stmtExiste->fetch(PDO::FETCH_ASSOC);

if ($existe) {
    $stmtUpdate = $conexion->prepare(
        "UPDATE valoraciones_producto
         SET estrellas = :estrellas,
             comentario = :comentario,
             id_pedido = CASE WHEN :id_pedido > 0 THEN :id_pedido ELSE id_pedido END,
             fecha = CURRENT_TIMESTAMP
         WHERE id_valoracion_producto = :id_valoracion_producto"
    );
    $stmtUpdate->bindParam(':estrellas', $estrellas, PDO::PARAM_INT);
    $stmtUpdate->bindParam(':comentario', $comentario);
    $stmtUpdate->bindParam(':id_pedido', $idPedido, PDO::PARAM_INT);
    $stmtUpdate->bindParam(':id_valoracion_producto', $existe['id_valoracion_producto'], PDO::PARAM_INT);
    $stmtUpdate->execute();
} else {
    $stmtInsert = $conexion->prepare(
        "INSERT INTO valoraciones_producto (id_usuario, id_producto, id_pedido, estrellas, comentario)
         VALUES (:id_usuario, :id_producto, :id_pedido, :estrellas, :comentario)"
    );
    $stmtInsert->bindParam(':id_usuario', $idUsuario, PDO::PARAM_INT);
    $stmtInsert->bindParam(':id_producto', $idProducto, PDO::PARAM_INT);
    if ($idPedido > 0) {
        $stmtInsert->bindParam(':id_pedido', $idPedido, PDO::PARAM_INT);
    } else {
        $nullPedido = null;
        $stmtInsert->bindParam(':id_pedido', $nullPedido, PDO::PARAM_NULL);
    }
    $stmtInsert->bindParam(':estrellas', $estrellas, PDO::PARAM_INT);
    $stmtInsert->bindParam(':comentario', $comentario);
    $stmtInsert->execute();
}

echo json_encode(['ok' => true, 'message' => 'Valoración del producto guardada correctamente.'], JSON_UNESCAPED_UNICODE);
