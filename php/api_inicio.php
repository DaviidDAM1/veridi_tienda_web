<?php
require_once "../config/conexion.php";
require_once "../config/imagenes.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if ($origin !== '' && preg_match('#^https?://(localhost|127\.0\.0\.1)(:\d+)?$#', $origin)) {
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

function limpiarNombreProducto(string $nombre): string
{
    $sinFormal = preg_replace('/\bformal\b/i', '', $nombre);
    $sinEspaciosDobles = preg_replace('/\s+/', ' ', (string)$sinFormal);
    return trim((string)$sinEspaciosDobles);
}

$stmt = $conexion->query("SELECT p.id_producto, p.nombre, p.descripcion, p.precio, p.fecha_creacion, COALESCE(SUM(pd.cantidad), 0) AS total_vendido FROM productos p LEFT JOIN pedido_detalle pd ON pd.id_producto = p.id_producto WHERE (p.oculto = 0 OR p.oculto IS NULL) GROUP BY p.id_producto, p.nombre, p.descripcion, p.precio, p.fecha_creacion");
$productos = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

if (empty($productos)) {
    echo json_encode([
        'ok' => true,
        'destacados' => [
            'mas_vendido' => null,
            'nuevo' => null,
            'oferta' => null
        ]
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$normalizar = static function (array $producto): array {
    $id = (int)$producto['id_producto'];
    $nombreLimpio = limpiarNombreProducto((string)($producto['nombre'] ?? ''));
    return [
        'id_producto' => $id,
        'nombre' => $nombreLimpio,
        'descripcion' => (string)($producto['descripcion'] ?? ''),
        'precio' => (float)$producto['precio'],
        'imagen' => obtenerImagenProducto($id, $nombreLimpio)
    ];
};

$productosVentas = $productos;
usort($productosVentas, static function ($a, $b) {
    $ventasA = (int)($a['total_vendido'] ?? 0);
    $ventasB = (int)($b['total_vendido'] ?? 0);
    if ($ventasA === $ventasB) {
        return (int)$a['id_producto'] <=> (int)$b['id_producto'];
    }
    return $ventasB <=> $ventasA;
});
$masVendido = $productosVentas[0] ?? null;

$excluidos = [];
if ($masVendido) {
    $excluidos[(int)$masVendido['id_producto']] = true;
}

$productosNuevos = array_values(array_filter($productos, static function ($item) use ($excluidos) {
    return !isset($excluidos[(int)$item['id_producto']]);
}));
if (empty($productosNuevos)) {
    $productosNuevos = $productos;
}
usort($productosNuevos, static function ($a, $b) {
    $fechaA = strtotime((string)($a['fecha_creacion'] ?? '')) ?: 0;
    $fechaB = strtotime((string)($b['fecha_creacion'] ?? '')) ?: 0;
    if ($fechaA === $fechaB) {
        return (int)$b['id_producto'] <=> (int)$a['id_producto'];
    }
    return $fechaB <=> $fechaA;
});
$nuevo = $productosNuevos[0] ?? null;

if ($nuevo) {
    $excluidos[(int)$nuevo['id_producto']] = true;
}

$productosOferta = array_values(array_filter($productos, static function ($item) use ($excluidos) {
    return !isset($excluidos[(int)$item['id_producto']]);
}));
if (empty($productosOferta)) {
    $productosOferta = $productos;
}
usort($productosOferta, static function ($a, $b) {
    $precioA = (float)$a['precio'];
    $precioB = (float)$b['precio'];
    if ($precioA === $precioB) {
        return (int)$a['id_producto'] <=> (int)$b['id_producto'];
    }
    return $precioA <=> $precioB;
});
$oferta = $productosOferta[0] ?? null;

echo json_encode([
    'ok' => true,
    'destacados' => [
        'mas_vendido' => $masVendido ? $normalizar($masVendido) : null,
        'nuevo' => $nuevo ? $normalizar($nuevo) : null,
        'oferta' => $oferta ? $normalizar($oferta) : null
    ]
], JSON_UNESCAPED_UNICODE);
