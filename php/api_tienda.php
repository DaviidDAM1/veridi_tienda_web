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

function getArrayParam(string $key): array
{
    if (!isset($_GET[$key])) {
        return [];
    }

    $value = $_GET[$key];
    if (is_array($value)) {
        return array_values(array_filter(array_map('trim', $value), static fn($v) => $v !== ''));
    }

    if (is_string($value) && $value !== '') {
        return [trim($value)];
    }

    return [];
}

$productosPorPagina = 16;
$paginaActual = isset($_GET['pagina']) ? max(1, (int)$_GET['pagina']) : 1;
$inicio = ($paginaActual - 1) * $productosPorPagina;

$buscar = trim($_GET['buscar'] ?? '');
$categoria = isset($_GET['categoria']) ? (int)$_GET['categoria'] : 0;
$precioMin = isset($_GET['precio_min']) && $_GET['precio_min'] !== '' ? (float)$_GET['precio_min'] : null;
$precioMax = isset($_GET['precio_max']) && $_GET['precio_max'] !== '' ? (float)$_GET['precio_max'] : null;
$tallas = getArrayParam('talla');
$colores = getArrayParam('color');
$estilos = getArrayParam('estilo');
$ordenar = trim($_GET['ordenar'] ?? '');

$whereParts = ['(p.oculto = 0 OR p.oculto IS NULL)'];
$params = [];

if ($buscar !== '') {
    $whereParts[] = 'p.nombre LIKE :buscar';
    $params[':buscar'] = '%' . $buscar . '%';
}

if ($categoria > 0) {
    $whereParts[] = 'p.id_categoria = :categoria';
    $params[':categoria'] = $categoria;
}

if ($precioMin !== null) {
    $whereParts[] = 'p.precio >= :precio_min';
    $params[':precio_min'] = $precioMin;
}

if ($precioMax !== null) {
    $whereParts[] = 'p.precio <= :precio_max';
    $params[':precio_max'] = $precioMax;
}

if (!empty($tallas)) {
    $placeholders = [];
    foreach ($tallas as $index => $talla) {
        $ph = ':talla_' . $index;
        $placeholders[] = $ph;
        $params[$ph] = $talla;
    }

    $whereParts[] = 'p.id_producto IN (
        SELECT pt.id_producto
        FROM producto_tallas pt
        INNER JOIN tallas t ON pt.id_talla = t.id_talla
        WHERE t.nombre IN (' . implode(',', $placeholders) . ')
    )';
}

if (!empty($colores)) {
    $placeholders = [];
    foreach ($colores as $index => $color) {
        $ph = ':color_' . $index;
        $placeholders[] = $ph;
        $params[$ph] = $color;
    }
    $whereParts[] = 'p.color IN (' . implode(',', $placeholders) . ')';
}

if (!empty($estilos)) {
    $placeholders = [];
    foreach ($estilos as $index => $estilo) {
        $ph = ':estilo_' . $index;
        $placeholders[] = $ph;
        $params[$ph] = $estilo;
    }
    $whereParts[] = 'p.estilo IN (' . implode(',', $placeholders) . ')';
}

$whereSql = 'WHERE ' . implode(' AND ', $whereParts);

$ordenamiento = 'ORDER BY p.id_producto ASC';
switch ($ordenar) {
    case 'nombre_asc':
        $ordenamiento = 'ORDER BY p.nombre ASC';
        break;
    case 'nombre_desc':
        $ordenamiento = 'ORDER BY p.nombre DESC';
        break;
    case 'precio_asc':
        $ordenamiento = 'ORDER BY p.precio ASC';
        break;
    case 'precio_desc':
        $ordenamiento = 'ORDER BY p.precio DESC';
        break;
}

$sqlCount = "SELECT COUNT(*) AS total FROM productos p LEFT JOIN categorias c ON p.id_categoria = c.id_categoria $whereSql";
$stmtCount = $conexion->prepare($sqlCount);
foreach ($params as $key => $value) {
    $stmtCount->bindValue($key, $value);
}
$stmtCount->execute();
$totalProductos = (int)($stmtCount->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);
$totalPaginas = max(1, (int)ceil($totalProductos / $productosPorPagina));

$sqlProductos = "
    SELECT p.*, c.nombre AS categoria
    FROM productos p
    LEFT JOIN categorias c ON p.id_categoria = c.id_categoria
    $whereSql
    $ordenamiento
    LIMIT :inicio, :limite
";
$stmtProductos = $conexion->prepare($sqlProductos);
foreach ($params as $key => $value) {
    $stmtProductos->bindValue($key, $value);
}
$stmtProductos->bindValue(':inicio', $inicio, PDO::PARAM_INT);
$stmtProductos->bindValue(':limite', $productosPorPagina, PDO::PARAM_INT);
$stmtProductos->execute();
$productos = $stmtProductos->fetchAll(PDO::FETCH_ASSOC);

$deseosIds = [];
if (!empty($_SESSION['deseos']) && is_array($_SESSION['deseos'])) {
    foreach ($_SESSION['deseos'] as $key => $favorito) {
        $idDesdeClave = (int)$key;
        if ($idDesdeClave > 0) {
            $deseosIds[$idDesdeClave] = true;
        }

        if (is_array($favorito)) {
            $idDesdeItem = (int)($favorito['id_producto'] ?? 0);
            if ($idDesdeItem > 0) {
                $deseosIds[$idDesdeItem] = true;
            }
        }
    }
}

$productosOut = array_map(static function ($producto) use ($deseosIds) {
    $idProducto = (int)$producto['id_producto'];
    return [
        'id_producto' => $idProducto,
        'nombre' => $producto['nombre'],
        'descripcion' => $producto['descripcion'],
        'precio' => (float)$producto['precio'],
        'categoria' => $producto['categoria'],
        'imagen' => obtenerImagenProducto($idProducto),
        'es_favorito' => isset($deseosIds[$idProducto])
    ];
}, $productos);

$stmtCat = $conexion->query("SELECT id_categoria, nombre FROM categorias ORDER BY nombre ASC");
$categorias = $stmtCat->fetchAll(PDO::FETCH_ASSOC);

$stmtTallas = $conexion->query("SELECT t.nombre FROM tallas t INNER JOIN producto_tallas pt ON t.id_talla = pt.id_talla INNER JOIN productos p ON p.id_producto = pt.id_producto WHERE (p.oculto = 0 OR p.oculto IS NULL) GROUP BY t.id_talla, t.nombre ORDER BY t.id_talla ASC");
$tallasDisponibles = array_map(static fn($row) => $row['nombre'], $stmtTallas->fetchAll(PDO::FETCH_ASSOC));

$stmtColores = $conexion->query("SELECT DISTINCT color FROM productos WHERE color IS NOT NULL AND color != '' AND (oculto = 0 OR oculto IS NULL) ORDER BY color ASC");
$coloresDisponibles = array_map(static fn($row) => $row['color'], $stmtColores->fetchAll(PDO::FETCH_ASSOC));

$stmtEstilos = $conexion->query("SELECT DISTINCT estilo FROM productos WHERE estilo IS NOT NULL AND estilo != '' AND (oculto = 0 OR oculto IS NULL) ORDER BY estilo ASC");
$estilosDisponibles = array_map(static fn($row) => $row['estilo'], $stmtEstilos->fetchAll(PDO::FETCH_ASSOC));

$cantidadCarrito = 0;
if (!empty($_SESSION['carrito']) && is_array($_SESSION['carrito'])) {
    foreach ($_SESSION['carrito'] as $item) {
        $cantidadCarrito += (int)($item['cantidad'] ?? 0);
    }
}

$cantidadDeseos = 0;
if (!empty($_SESSION['deseos']) && is_array($_SESSION['deseos'])) {
    $cantidadDeseos = count($_SESSION['deseos']);
}

echo json_encode([
    'ok' => true,
    'filtros' => [
        'categorias' => $categorias,
        'tallas' => $tallasDisponibles,
        'colores' => $coloresDisponibles,
        'estilos' => $estilosDisponibles
    ],
    'productos' => $productosOut,
    'paginacion' => [
        'paginaActual' => $paginaActual,
        'totalPaginas' => $totalPaginas,
        'totalProductos' => $totalProductos,
        'productosPorPagina' => $productosPorPagina
    ],
    'contador' => [
        'carrito' => $cantidadCarrito,
        'deseos' => $cantidadDeseos
    ]
], JSON_UNESCAPED_UNICODE);
