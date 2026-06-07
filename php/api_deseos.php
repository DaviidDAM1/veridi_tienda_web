<?php
require_once "../config/conexion.php";
require_once "../config/imagenes.php";
require_once "../config/ofertas.php";

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
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if (!isset($_SESSION['deseos']) || !is_array($_SESSION['deseos'])) {
    $_SESSION['deseos'] = [];
}

function ensureDeseosTable(PDO $conexion): void
{
    $conexion->exec(
        "CREATE TABLE IF NOT EXISTS deseos_usuario (
            id_deseo INT AUTO_INCREMENT PRIMARY KEY,
            id_usuario INT NOT NULL,
            id_producto INT NOT NULL,
            fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uk_deseos_usuario_producto (id_usuario, id_producto)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    );
}

function loadDeseosFromDb(PDO $conexion, int $idUsuario): array
{
    $stmt = $conexion->prepare(
        "SELECT p.id_producto, p.nombre, p.precio
         FROM deseos_usuario d
         INNER JOIN productos p ON p.id_producto = d.id_producto
         WHERE d.id_usuario = :id_usuario
           AND (p.oculto = 0 OR p.oculto IS NULL)
         ORDER BY d.id_deseo DESC"
    );
    $stmt->bindValue(':id_usuario', $idUsuario, PDO::PARAM_INT);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    $out = [];
    foreach ($rows as $row) {
        $idProducto = (int)($row['id_producto'] ?? 0);
        if ($idProducto <= 0) {
            continue;
        }
        $nombre = (string)($row['nombre'] ?? 'Producto');
        $pricing = veridiCalcularPrecioOferta($nombre, (float)($row['precio'] ?? 0));
        $out[$idProducto] = [
            'id_producto' => $idProducto,
            'nombre' => $nombre,
            'precio' => (float)$pricing['precio'],
            'precio_original' => $pricing['precio_original'] !== null ? (float)$pricing['precio_original'] : null,
            'descuento_porcentaje' => (float)$pricing['descuento_porcentaje'],
            'en_oferta' => (bool)$pricing['en_oferta'],
            'imagen' => obtenerImagenProducto($idProducto, $nombre)
        ];
    }

    return $out;
}

function syncSessionDeseos(PDO $conexion, int $idUsuario): array
{
    ensureDeseosTable($conexion);
    $deseos = loadDeseosFromDb($conexion, $idUsuario);
    $_SESSION['deseos'] = $deseos;
    return $deseos;
}

function jsonResponse(array $payload, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

function requireLogin(): void
{
    if (!isset($_SESSION['usuario_id'])) {
        jsonResponse([
            'ok' => false,
            'requiresLogin' => true,
            'message' => 'Debes iniciar sesión para acceder a tus favoritos.'
        ], 401);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    requireLogin();

    $idUsuario = (int)($_SESSION['usuario_id'] ?? 0);
    $dbDeseos = $idUsuario > 0 ? syncSessionDeseos($conexion, $idUsuario) : [];

    $deseos = array_values(array_map(static function ($item) {
        return [
            'id_producto' => (int)($item['id_producto'] ?? 0),
            'nombre' => (string)($item['nombre'] ?? 'Producto'),
            'precio' => (float)($item['precio'] ?? 0),
            'precio_original' => isset($item['precio_original']) && $item['precio_original'] !== null ? (float)$item['precio_original'] : null,
            'descuento_porcentaje' => (float)($item['descuento_porcentaje'] ?? 0),
            'en_oferta' => (bool)($item['en_oferta'] ?? false),
            'imagen' => (string)($item['imagen'] ?? '')
        ];
    }, $dbDeseos));

    jsonResponse([
        'ok' => true,
        'deseos' => $deseos,
        'total' => count($deseos)
    ]);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse([
        'ok' => false,
        'message' => 'Método no permitido.'
    ], 405);
}

requireLogin();

$idUsuario = (int)($_SESSION['usuario_id'] ?? 0);
if ($idUsuario <= 0) {
    jsonResponse([
        'ok' => false,
        'requiresLogin' => true,
        'message' => 'Debes iniciar sesión para acceder a tus favoritos.'
    ], 401);
}

ensureDeseosTable($conexion);
$_SESSION['deseos'] = loadDeseosFromDb($conexion, $idUsuario);

$payload = json_decode(file_get_contents('php://input'), true);
if (!is_array($payload)) {
    $payload = [];
}

$action = trim((string)($payload['action'] ?? ''));
$idProducto = (int)($payload['id_producto'] ?? 0);

if ($idProducto <= 0) {
    jsonResponse([
        'ok' => false,
        'message' => 'Producto inválido.'
    ], 400);
}

switch ($action) {
    case 'add': {
        $stmtProducto = $conexion->prepare(
            "SELECT id_producto, nombre, precio
             FROM productos
             WHERE id_producto = :id_producto
               AND (oculto = 0 OR oculto IS NULL)
             LIMIT 1"
        );
        $stmtProducto->bindValue(':id_producto', $idProducto, PDO::PARAM_INT);
        $stmtProducto->execute();
        $producto = $stmtProducto->fetch(PDO::FETCH_ASSOC);

        if (!$producto) {
            jsonResponse([
                'ok' => false,
                'message' => 'Producto no disponible.'
            ], 404);
        }

        $nombre = trim((string)($producto['nombre'] ?? 'Producto'));
        $pricing = veridiCalcularPrecioOferta($nombre, (float)($producto['precio'] ?? 0));

        $stmtUpsert = $conexion->prepare(
            "INSERT INTO deseos_usuario (id_usuario, id_producto)
             VALUES (:id_usuario, :id_producto)
             ON DUPLICATE KEY UPDATE id_producto = VALUES(id_producto)"
        );
        $stmtUpsert->bindValue(':id_usuario', $idUsuario, PDO::PARAM_INT);
        $stmtUpsert->bindValue(':id_producto', $idProducto, PDO::PARAM_INT);
        $stmtUpsert->execute();

        $_SESSION['deseos'][$idProducto] = [
            'id_producto' => $idProducto,
            'nombre' => $nombre,
            'precio' => (float)$pricing['precio'],
            'precio_original' => $pricing['precio_original'] !== null ? (float)$pricing['precio_original'] : null,
            'descuento_porcentaje' => (float)$pricing['descuento_porcentaje'],
            'en_oferta' => (bool)$pricing['en_oferta'],
            'imagen' => obtenerImagenProducto($idProducto, $nombre)
        ];

        jsonResponse([
            'ok' => true,
            'message' => 'Agregado a favoritos.',
            'esFavorito' => true,
            'total' => count($_SESSION['deseos'])
        ]);
    }

    case 'remove': {
        $stmtDelete = $conexion->prepare("DELETE FROM deseos_usuario WHERE id_usuario = :id_usuario AND id_producto = :id_producto");
        $stmtDelete->bindValue(':id_usuario', $idUsuario, PDO::PARAM_INT);
        $stmtDelete->bindValue(':id_producto', $idProducto, PDO::PARAM_INT);
        $stmtDelete->execute();

        foreach ($_SESSION['deseos'] as $key => $fav) {
            if ((int)($fav['id_producto'] ?? 0) === $idProducto) {
                unset($_SESSION['deseos'][$key]);
            }
        }

        if (isset($_SESSION['deseos'][$idProducto])) {
            unset($_SESSION['deseos'][$idProducto]);
        }

        jsonResponse([
            'ok' => true,
            'message' => 'Eliminado de favoritos.',
            'esFavorito' => false,
            'total' => count($_SESSION['deseos'])
        ]);
    }

    case 'move_to_cart': {
        if (!isset($_SESSION['deseos'][$idProducto])) {
            foreach ($_SESSION['deseos'] as $fav) {
                if ((int)($fav['id_producto'] ?? 0) === $idProducto) {
                    $_SESSION['deseos'][$idProducto] = $fav;
                    break;
                }
            }
        }

        jsonResponse([
            'ok' => true,
            'message' => 'Selecciona la talla para agregar al carrito.',
            'redirect' => '/producto/' . $idProducto
        ]);
    }

    case 'check': {
        if (!isset($_SESSION['deseos']) || !is_array($_SESSION['deseos']) || empty($_SESSION['deseos'])) {
            $_SESSION['deseos'] = loadDeseosFromDb($conexion, $idUsuario);
        }

        $esFavorito = false;
        foreach ($_SESSION['deseos'] as $fav) {
            if ((int)($fav['id_producto'] ?? 0) === $idProducto) {
                $esFavorito = true;
                break;
            }
        }

        if (isset($_SESSION['deseos'][$idProducto])) {
            $esFavorito = true;
        }

        jsonResponse([
            'ok' => true,
            'esFavorito' => $esFavorito,
            'total' => count($_SESSION['deseos'])
        ]);
    }

    default:
        jsonResponse([
            'ok' => false,
            'message' => 'Acción no reconocida.'
        ], 400);
}
