<?php
require_once "../config/conexion.php";
require_once "../config/imagenes.php";

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

$idProducto = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($idProducto <= 0) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'message' => 'ID de producto inválido']);
    exit;
}

$stmt = $conexion->prepare("SELECT p.*, c.nombre AS categoria FROM productos p LEFT JOIN categorias c ON p.id_categoria = c.id_categoria WHERE p.id_producto = :id AND (p.oculto = 0 OR p.oculto IS NULL)");
$stmt->bindParam(':id', $idProducto, PDO::PARAM_INT);
$stmt->execute();
$producto = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$producto) {
    http_response_code(404);
    echo json_encode(['ok' => false, 'message' => 'Producto no encontrado']);
    exit;
}

$categoriasRopa = ['Camisetas', 'Chaquetas', 'Abrigos', 'Sudaderas', 'Pantalones', 'Vaqueros'];
$categoriasCalzado = ['Calzado'];
$categoriasAccesorios = ['Gorras', 'Calcetines', 'Accesorios'];

$tallasPermitidas = [];
if (in_array($producto['categoria'], $categoriasRopa, true)) {
    $tallasPermitidas = ['S', 'M', 'L', 'XL'];
} elseif (in_array($producto['categoria'], $categoriasCalzado, true)) {
    $tallasPermitidas = ['40', '41', '42', '43', '44', '45'];
} elseif (in_array($producto['categoria'], $categoriasAccesorios, true)) {
    $tallasPermitidas = ['Única'];
}

if (!empty($tallasPermitidas)) {
    $placeholders = implode(',', array_fill(0, count($tallasPermitidas), '?'));
    $stmtTallas = $conexion->prepare("SELECT t.id_talla, t.nombre, pt.stock FROM tallas t INNER JOIN producto_tallas pt ON t.id_talla = pt.id_talla WHERE pt.id_producto = ? AND pt.stock > 0 AND t.nombre IN ($placeholders) ORDER BY CASE WHEN t.nombre = 'Única' THEN 0 WHEN t.nombre IN ('S','M','L','XL') THEN 1 ELSE 2 END, FIELD(t.nombre, 'S','M','L','XL','40','41','42','43','44','45','Única')");
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
    'nombre' => $producto['nombre'],
    'descripcion' => $producto['descripcion'] ?? '',
    'precio' => (float)$producto['precio'],
    'categoria' => $producto['categoria'] ?? '',
    'id_categoria' => (int)$producto['id_categoria'],
    'color' => $producto['color'] ?? '',
    'material' => $producto['material'] ?? '',
    'estilo' => $producto['estilo'] ?? '',
    'imagen' => obtenerImagenProducto($idProducto)
];

$relacionadosOut = array_map(static function ($prod) {
    $idRel = (int)$prod['id_producto'];
    return [
        'id_producto' => $idRel,
        'nombre' => $prod['nombre'],
        'precio' => (float)$prod['precio'],
        'descripcion' => $prod['descripcion'] ?? '',
        'imagen' => obtenerImagenProducto($idRel)
    ];
}, $relacionados);

echo json_encode([
    'ok' => true,
    'producto' => $productoOut,
    'tallas' => $tallas,
    'relacionados' => $relacionadosOut,
    'usuario' => [
        'logueado' => isset($_SESSION['usuario_id']),
        'id' => isset($_SESSION['usuario_id']) ? (int)$_SESSION['usuario_id'] : null,
        'esFavorito' => $esFavorito
    ]
], JSON_UNESCAPED_UNICODE);
