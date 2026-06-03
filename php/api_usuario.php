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
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
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
    $stmtCart = $conexion->prepare(
        "SELECT cd.id_producto, cd.id_talla, cd.cantidad, p.nombre, p.precio
         FROM carrito c
         INNER JOIN carrito_detalle cd ON cd.id_carrito = c.id_carrito
         INNER JOIN productos p ON p.id_producto = cd.id_producto
         WHERE c.id_usuario = :id_usuario
           AND (p.oculto = 0 OR p.oculto IS NULL)
         ORDER BY cd.id_detalle DESC"
    );
    $stmtCart->bindValue(':id_usuario', $idUsuario, PDO::PARAM_INT);
    $stmtCart->execute();
    $carritoDb = [];
    while ($row = $stmtCart->fetch(PDO::FETCH_ASSOC)) {
        $idProducto = (int)($row['id_producto'] ?? 0);
        $idTalla = (int)($row['id_talla'] ?? 0);
        if ($idProducto <= 0 || $idTalla <= 0) {
            continue;
        }
        $key = $idProducto . '_' . $idTalla;
        if (isset($carritoDb[$key])) {
            continue;
        }
        $carritoDb[$key] = [
            'id_producto' => $idProducto,
            'id_talla' => $idTalla,
            'nombre' => (string)($row['nombre'] ?? 'Producto'),
            'precio' => (float)($row['precio'] ?? 0),
            'imagen' => (string)($_SESSION['carrito'][$key]['imagen'] ?? ''),
            'cantidad' => max(1, (int)($row['cantidad'] ?? 1))
        ];
    }
    $_SESSION['carrito'] = $carritoDb;
} catch (Exception $e) {
}

try {
    $conexion->exec(
        "CREATE TABLE IF NOT EXISTS deseos_usuario (
            id_deseo INT AUTO_INCREMENT PRIMARY KEY,
            id_usuario INT NOT NULL,
            id_producto INT NOT NULL,
            fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uk_deseos_usuario_producto (id_usuario, id_producto)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    );

    $stmtDeseos = $conexion->prepare(
        "SELECT p.id_producto, p.nombre, p.precio
         FROM deseos_usuario d
         INNER JOIN productos p ON p.id_producto = d.id_producto
         WHERE d.id_usuario = :id_usuario
           AND (p.oculto = 0 OR p.oculto IS NULL)"
    );
    $stmtDeseos->bindValue(':id_usuario', $idUsuario, PDO::PARAM_INT);
    $stmtDeseos->execute();
    $deseosDb = [];
    while ($row = $stmtDeseos->fetch(PDO::FETCH_ASSOC)) {
        $idProducto = (int)($row['id_producto'] ?? 0);
        if ($idProducto <= 0) {
            continue;
        }
        $deseosDb[$idProducto] = [
            'id_producto' => $idProducto,
            'nombre' => (string)($row['nombre'] ?? 'Producto'),
            'precio' => (float)($row['precio'] ?? 0),
            'imagen' => (string)($_SESSION['deseos'][$idProducto]['imagen'] ?? '')
        ];
    }
    $_SESSION['deseos'] = $deseosDb;
} catch (Exception $e) {
}

$cantidadCarrito = 0;
foreach ($_SESSION['carrito'] as $item) {
    $cantidadCarrito += (int)($item['cantidad'] ?? 0);
}

$base['contador'] = [
    'carrito' => $cantidadCarrito,
    'deseos' => count($_SESSION['deseos'])
];

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
