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

function limpiarNombreProducto(string $nombre): string
{
    $sinFormal = preg_replace('/\bformal\b/i', '', $nombre);
    $sinEspaciosDobles = preg_replace('/\s+/', ' ', (string)$sinFormal);
    return trim((string)$sinEspaciosDobles);
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

function normalizarTallaFiltro(string $nombre): ?string
{
    $raw = trim($nombre);
    if ($raw === '') {
        return null;
    }

    $lower = strtolower($raw);
    $ascii = strtr($lower, [
        'á' => 'a',
        'é' => 'e',
        'í' => 'i',
        'ó' => 'o',
        'ú' => 'u',
        'ü' => 'u'
    ]);

    // Defensive fix for typo/encoding artifacts like "asnica" / "Ãšnica".
    if (in_array($ascii, ['unica', 'asnica', 'ašnica', 'asn ica', 'ãšnica'], true)) {
        return 'Única';
    }

    if (in_array($ascii, ['s', 'm', 'l', 'xl', '40', '41', '42'], true)) {
        return strtoupper($ascii);
    }

    return null;
}

$limiteSolicitado = isset($_GET['limite']) ? (int)$_GET['limite'] : 0;
$productosPorPagina = $limiteSolicitado > 0 ? min(200, $limiteSolicitado) : 1000;
$paginaActual = isset($_GET['pagina']) ? max(1, (int)$_GET['pagina']) : 1;
$inicio = ($paginaActual - 1) * $productosPorPagina;

$buscar = trim($_GET['buscar'] ?? '');
$categoria = isset($_GET['categoria']) ? (int)$_GET['categoria'] : 0;
$precioMin = isset($_GET['precio_min']) && $_GET['precio_min'] !== '' ? (float)$_GET['precio_min'] : null;
$precioMax = isset($_GET['precio_max']) && $_GET['precio_max'] !== '' ? (float)$_GET['precio_max'] : null;
$tallas = array_values(array_filter(array_unique(array_map('normalizarTallaFiltro', getArrayParam('talla')))));
$colores = getArrayParam('color');
$estilos = getArrayParam('estilo');
$ordenar = trim($_GET['ordenar'] ?? '');

$whereParts = ['(p.oculto = 0 OR p.oculto IS NULL)'];
$params = [];
$precioOfertaSql = "CASE WHEN p.nombre = 'Gorra Roja Veridi' THEN GREATEST(ROUND(p.precio * 0.60, 2), 0.01) ELSE p.precio END";

if ($buscar !== '') {
    $whereParts[] = 'p.nombre LIKE :buscar';
    $params[':buscar'] = '%' . $buscar . '%';
}

if ($categoria > 0) {
    $whereParts[] = 'p.id_categoria = :categoria';
    $params[':categoria'] = $categoria;
}

if ($precioMin !== null) {
    $whereParts[] = $precioOfertaSql . ' >= :precio_min';
    $params[':precio_min'] = $precioMin;
}

if ($precioMax !== null) {
    $whereParts[] = $precioOfertaSql . ' <= :precio_max';
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
    $whereParts[] = '(CASE WHEN LOWER(TRIM(c.nombre)) = "gorras" THEN "casual" ELSE p.estilo END) IN (' . implode(',', $placeholders) . ')';
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
        $ordenamiento = 'ORDER BY ' . $precioOfertaSql . ' ASC';
        break;
    case 'precio_desc':
        $ordenamiento = 'ORDER BY ' . $precioOfertaSql . ' DESC';
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
    $producto = veridiAplicarOfertaProducto($producto);
    $idProducto = (int)$producto['id_producto'];
    $nombreLimpio = limpiarNombreProducto((string)($producto['nombre'] ?? ''));
    return [
        'id_producto' => $idProducto,
        'nombre' => $nombreLimpio,
        'descripcion' => $producto['descripcion'],
        'precio' => (float)$producto['precio'],
        'precio_original' => isset($producto['precio_original']) ? (float)$producto['precio_original'] : null,
        'descuento_porcentaje' => (float)($producto['descuento_porcentaje'] ?? 0),
        'en_oferta' => (bool)($producto['en_oferta'] ?? false),
        'categoria' => $producto['categoria'],
        'imagen' => obtenerImagenProducto($idProducto, $nombreLimpio),
        'es_favorito' => isset($deseosIds[$idProducto])
    ];
}, $productos);

$stmtCat = $conexion->query("SELECT id_categoria, nombre FROM categorias ORDER BY nombre ASC");
$categorias = $stmtCat->fetchAll(PDO::FETCH_ASSOC);

$stmtTallas = $conexion->query("SELECT t.nombre FROM tallas t INNER JOIN producto_tallas pt ON t.id_talla = pt.id_talla INNER JOIN productos p ON p.id_producto = pt.id_producto WHERE (p.oculto = 0 OR p.oculto IS NULL) GROUP BY t.id_talla, t.nombre ORDER BY t.id_talla ASC");
$tallasRaw = array_map(static fn($row) => normalizarTallaFiltro((string)($row['nombre'] ?? '')), $stmtTallas->fetchAll(PDO::FETCH_ASSOC));
$tallasDisponibles = array_values(array_filter(array_unique($tallasRaw)));

$ordenTallas = ['S', 'M', 'L', 'XL', '40', '41', '42', 'Única'];
$ordenMapa = array_flip($ordenTallas);
usort($tallasDisponibles, static function ($a, $b) use ($ordenMapa) {
    $oa = $ordenMapa[$a] ?? 999;
    $ob = $ordenMapa[$b] ?? 999;
    if ($oa === $ob) {
        return strcmp($a, $b);
    }
    return $oa <=> $ob;
});

$stmtColores = $conexion->query("SELECT DISTINCT color FROM productos WHERE color IS NOT NULL AND color != '' AND (oculto = 0 OR oculto IS NULL) ORDER BY color ASC");
$coloresDisponibles = array_map(static fn($row) => $row['color'], $stmtColores->fetchAll(PDO::FETCH_ASSOC));

$stmtEstilos = $conexion->query("SELECT DISTINCT CASE WHEN LOWER(TRIM(c.nombre)) = 'gorras' THEN 'casual' ELSE p.estilo END AS estilo FROM productos p LEFT JOIN categorias c ON p.id_categoria = c.id_categoria WHERE p.estilo IS NOT NULL AND p.estilo != '' AND (p.oculto = 0 OR p.oculto IS NULL) ORDER BY estilo ASC");
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
