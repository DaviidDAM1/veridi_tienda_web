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
header('Access-Control-Allow-Methods: GET, OPTIONS');
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

function formatearFechaPedidoMadrid($fechaRaw)
{
    $fechaRaw = trim((string)$fechaRaw);
    if ($fechaRaw === '') {
        return '';
    }

    try {
        // DB timestamp usually arrives without timezone and is stored in UTC.
        if (preg_match('/(Z|[+\-]\d{2}:?\d{2})$/', $fechaRaw) === 1) {
            $fecha = new DateTime($fechaRaw);
        } else {
            $fecha = DateTime::createFromFormat('Y-m-d H:i:s', $fechaRaw, new DateTimeZone('UTC'));
            if (!$fecha) {
                $fecha = new DateTime($fechaRaw, new DateTimeZone('UTC'));
            }
        }

        $fecha->setTimezone(new DateTimeZone('Europe/Madrid'));
        return $fecha->format(DateTime::ATOM);
    } catch (Exception $e) {
        return $fechaRaw;
    }
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

ensureValoracionesProductoSchema($conexion);

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

$valoracionesProductoUsuario = [];
$productosPedido = [];
foreach ($detalles as $d) {
    $idProductoDetalle = (int)($d['id_producto'] ?? 0);
    if ($idProductoDetalle > 0) {
        $productosPedido[$idProductoDetalle] = true;
    }
}

if (!empty($productosPedido)) {
    $ids = array_keys($productosPedido);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmtProdRated = $conexion->prepare(
        "SELECT id_producto
         FROM valoraciones_producto
         WHERE id_usuario = ? AND id_producto IN ($placeholders)"
    );
    $stmtProdRated->execute(array_merge([(int)$_SESSION['usuario_id']], $ids));
    while ($row = $stmtProdRated->fetch(PDO::FETCH_ASSOC)) {
        $valoracionesProductoUsuario[] = (int)$row['id_producto'];
    }
}

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
        'fecha' => formatearFechaPedidoMadrid($pedido['fecha']),
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
    ],
    'valoraciones_producto_usuario' => $valoracionesProductoUsuario
], JSON_UNESCAPED_UNICODE);
