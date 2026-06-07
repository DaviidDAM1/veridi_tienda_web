<?php
require_once "../config/conexion.php";
require_once "../config/imagenes.php";
require_once __DIR__ . '/ofertas.php';

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

function limpiarNombreProducto(string $nombre): string
{
    $sinFormal = preg_replace('/\bformal\b/i', '', $nombre);
    $sinEspaciosDobles = preg_replace('/\s+/', ' ', (string)$sinFormal);
    return trim((string)$sinEspaciosDobles);
}

$idProducto = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($idProducto <= 0) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'message' => 'ID de producto inválido']);
    exit;
}

ensureValoracionesProductoSchema($conexion);

$stmt = $conexion->prepare("SELECT p.*, c.nombre AS categoria FROM productos p LEFT JOIN categorias c ON p.id_categoria = c.id_categoria WHERE p.id_producto = :id AND (p.oculto = 0 OR p.oculto IS NULL)");
$stmt->bindParam(':id', $idProducto, PDO::PARAM_INT);
$stmt->execute();
$producto = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$producto) {
    http_response_code(404);
    echo json_encode(['ok' => false, 'message' => 'Producto no encontrado']);
    exit;
}

if (isset($producto['categoria']) && strtolower(trim((string)$producto['categoria'])) === 'gorras') {
    $producto['estilo'] = 'casual';
}

$categoriasRopa = ['Camisetas', 'Chaquetas', 'Abrigos', 'Sudaderas', 'Pantalones', 'Vaqueros'];
$categoriasCalzado = ['Calzado'];
$categoriasAccesorios = ['Gorras', 'Calcetines', 'Accesorios'];

$tallasPermitidas = [];
if (in_array($producto['categoria'], $categoriasRopa, true)) {
    $tallasPermitidas = ['S', 'M', 'L', 'XL'];
} elseif (in_array($producto['categoria'], $categoriasCalzado, true)) {
    $tallasPermitidas = ['40', '41', '42'];
} elseif (in_array($producto['categoria'], $categoriasAccesorios, true)) {
    $tallasPermitidas = ['Única'];
}

if (!empty($tallasPermitidas)) {
    $placeholders = implode(',', array_fill(0, count($tallasPermitidas), '?'));
    $stmtTallas = $conexion->prepare("SELECT t.id_talla, t.nombre, pt.stock FROM tallas t INNER JOIN producto_tallas pt ON t.id_talla = pt.id_talla WHERE pt.id_producto = ? AND pt.stock > 0 AND t.nombre IN ($placeholders) ORDER BY CASE WHEN t.nombre = 'Única' THEN 0 WHEN t.nombre IN ('S','M','L','XL') THEN 1 ELSE 2 END, FIELD(t.nombre, 'S','M','L','XL','40','41','42','Única')");
    $params = array_merge([$idProducto], $tallasPermitidas);
    $stmtTallas->execute($params);
} else {
    $stmtTallas = $conexion->prepare("SELECT t.id_talla, t.nombre, pt.stock FROM tallas t INNER JOIN producto_tallas pt ON t.id_talla = pt.id_talla WHERE pt.id_producto = :id AND pt.stock > 0 ORDER BY CASE WHEN t.nombre = 'Única' THEN 0 WHEN t.nombre IN ('S','M','L','XL') THEN 1 ELSE 2 END, FIELD(t.nombre, 'S','M','L','XL','40','41','42','Única')");
    $stmtTallas->bindParam(':id', $idProducto, PDO::PARAM_INT);
    $stmtTallas->execute();
}
$tallas = $stmtTallas->fetchAll(PDO::FETCH_ASSOC);

$stmtRelacionados = $conexion->prepare("SELECT p.id_producto, p.nombre, p.precio, p.descripcion FROM productos p WHERE p.id_categoria = :id_categoria AND p.id_producto != :id_producto AND (p.oculto = 0 OR p.oculto IS NULL) LIMIT 4");
$stmtRelacionados->bindParam(':id_categoria', $producto['id_categoria'], PDO::PARAM_INT);
$stmtRelacionados->bindParam(':id_producto', $idProducto, PDO::PARAM_INT);
$stmtRelacionados->execute();
$relacionados = $stmtRelacionados->fetchAll(PDO::FETCH_ASSOC);

$stmtRatingsResumen = $conexion->prepare(
    "SELECT COUNT(*) AS total, AVG(estrellas) AS promedio
     FROM valoraciones_producto
     WHERE id_producto = :id_producto"
);
$stmtRatingsResumen->bindParam(':id_producto', $idProducto, PDO::PARAM_INT);
$stmtRatingsResumen->execute();
$ratingsResumen = $stmtRatingsResumen->fetch(PDO::FETCH_ASSOC) ?: ['total' => 0, 'promedio' => null];

$stmtRatings = $conexion->prepare(
    "SELECT vp.id_valoracion_producto, vp.estrellas, vp.comentario, vp.fecha, u.nombre
     FROM valoraciones_producto vp
     INNER JOIN usuarios u ON u.id_usuario = vp.id_usuario
     WHERE vp.id_producto = :id_producto
     ORDER BY vp.fecha DESC
     LIMIT 30"
);
$stmtRatings->bindParam(':id_producto', $idProducto, PDO::PARAM_INT);
$stmtRatings->execute();
$ratings = $stmtRatings->fetchAll(PDO::FETCH_ASSOC) ?: [];

$esFavorito = false;
if (isset($_SESSION['deseos']) && is_array($_SESSION['deseos'])) {
    foreach ($_SESSION['deseos'] as $fav) {
        if ((int)($fav['id_producto'] ?? 0) === $idProducto) {
            $esFavorito = true;
            break;
        }
    }
}

$productoOut = [
    'id_producto' => (int)$producto['id_producto'],
    'nombre' => limpiarNombreProducto((string)($producto['nombre'] ?? '')),
    'descripcion' => $producto['descripcion'] ?? '',
    'precio' => (float)$producto['precio'],
    'categoria' => $producto['categoria'] ?? '',
    'id_categoria' => (int)$producto['id_categoria'],
    'color' => $producto['color'] ?? '',
    'material' => $producto['material'] ?? '',
    'estilo' => $producto['estilo'] ?? '',
    'imagen' => obtenerImagenProducto($idProducto, limpiarNombreProducto((string)($producto['nombre'] ?? '')))
];

$productoOut = veridiAplicarOfertaProducto($productoOut);

$relacionadosOut = array_map(static function ($prod) {
    $prod = veridiAplicarOfertaProducto($prod);
    $idRel = (int)$prod['id_producto'];
    $nombreLimpio = limpiarNombreProducto((string)($prod['nombre'] ?? ''));
    return [
        'id_producto' => $idRel,
        'nombre' => $nombreLimpio,
        'precio' => (float)$prod['precio'],
        'precio_original' => isset($prod['precio_original']) ? (float)$prod['precio_original'] : null,
        'descuento_porcentaje' => (float)($prod['descuento_porcentaje'] ?? 0),
        'en_oferta' => (bool)($prod['en_oferta'] ?? false),
        'descripcion' => $prod['descripcion'] ?? '',
        'imagen' => obtenerImagenProducto($idRel, $nombreLimpio)
    ];
}, $relacionados);

echo json_encode([
    'ok' => true,
    'producto' => $productoOut,
    'tallas' => $tallas,
    'relacionados' => $relacionadosOut,
    'valoraciones_producto' => [
        'resumen' => [
            'total' => (int)($ratingsResumen['total'] ?? 0),
            'promedio' => isset($ratingsResumen['promedio']) ? (float)$ratingsResumen['promedio'] : 0
        ],
        'items' => array_map(static function ($row) {
            return [
                'id_valoracion_producto' => (int)$row['id_valoracion_producto'],
                'nombre_usuario' => (string)($row['nombre'] ?? 'Usuario'),
                'estrellas' => (int)$row['estrellas'],
                'comentario' => (string)($row['comentario'] ?? ''),
                'fecha' => (string)$row['fecha']
            ];
        }, $ratings)
    ],
    'usuario' => [
        'logueado' => isset($_SESSION['usuario_id']),
        'id' => isset($_SESSION['usuario_id']) ? (int)$_SESSION['usuario_id'] : null,
        'esFavorito' => $esFavorito
    ]
], JSON_UNESCAPED_UNICODE);
