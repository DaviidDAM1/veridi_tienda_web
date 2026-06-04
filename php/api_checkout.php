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
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if (!isset($_SESSION['usuario_id'])) {
    echo json_encode([
        'ok' => false,
        'requiresLogin' => true,
        'message' => 'Debes iniciar sesión para acceder al checkout.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!isset($_SESSION['carrito']) || !is_array($_SESSION['carrito'])) {
    $_SESSION['carrito'] = [];
}

function getCheckoutData(PDO $conexion): array
{
    $carrito = $_SESSION['carrito'] ?? [];
    $tallasNombres = [];
    $total = 0;
    $items = [];

    $idsTallas = [];
    foreach ($carrito as $item) {
        if (!empty($item['id_talla'])) {
            $idsTallas[] = (int)$item['id_talla'];
        }
    }

    if (!empty($idsTallas)) {
        $idsTallas = array_values(array_unique($idsTallas));
        $placeholders = implode(',', array_fill(0, count($idsTallas), '?'));
        $stmt = $conexion->prepare("SELECT id_talla, nombre FROM tallas WHERE id_talla IN ($placeholders)");
        $stmt->execute($idsTallas);
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $tallasNombres[(int)$row['id_talla']] = $row['nombre'];
        }
    }

    foreach ($carrito as $item) {
        $cantidad = (int)($item['cantidad'] ?? 0);
        $precio = (float)($item['precio'] ?? 0);
        $subtotal = $precio * $cantidad;
        $total += $subtotal;

        $idTalla = (int)($item['id_talla'] ?? 0);
        $items[] = [
            'id_producto' => (int)($item['id_producto'] ?? 0),
            'id_talla' => $idTalla,
            'nombre' => (string)($item['nombre'] ?? 'Producto'),
            'cantidad' => $cantidad,
            'precio' => $precio,
            'subtotal' => $subtotal,
            'talla' => $tallasNombres[$idTalla] ?? 'N/A'
        ];
    }

    $stmtUsuario = $conexion->prepare("SELECT nombre, email FROM usuarios WHERE id_usuario = :id");
    $stmtUsuario->bindParam(':id', $_SESSION['usuario_id'], PDO::PARAM_INT);
    $stmtUsuario->execute();
    $usuario = $stmtUsuario->fetch(PDO::FETCH_ASSOC) ?: ['nombre' => '', 'email' => ''];

    return [
        'usuario' => [
            'nombre' => $usuario['nombre'] ?? '',
            'email' => $usuario['email'] ?? ''
        ],
        'items' => $items,
        'total' => $total,
        'isEmpty' => empty($items)
    ];
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $data = getCheckoutData($conexion);
    echo json_encode([
        'ok' => true,
        'checkout' => $data
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$payload = json_decode(file_get_contents('php://input'), true);
if (!is_array($payload)) {
    $payload = [];
}

$email = trim((string)($payload['email'] ?? ''));
$calle = trim((string)($payload['calle'] ?? ''));
$ciudad = trim((string)($payload['ciudad'] ?? ''));
$codigoPostal = trim((string)($payload['codigo_postal'] ?? ''));
$pais = trim((string)($payload['pais'] ?? ''));

if (empty($_SESSION['carrito'])) {
    echo json_encode(['ok' => false, 'message' => 'Tu carrito está vacío.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$stmt = $conexion->prepare("SELECT id_usuario, email FROM usuarios WHERE id_usuario = :id_usuario LIMIT 1");
$stmt->bindValue(':id_usuario', (int)$_SESSION['usuario_id'], PDO::PARAM_INT);
$stmt->execute();
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$usuario) {
    echo json_encode(['ok' => false, 'message' => 'Tu sesión ya no es válida. Inicia sesión de nuevo.'], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($email === '') {
    $email = (string)($usuario['email'] ?? '');
}

$missing = [];
if ($email === '') $missing[] = 'email';
if ($calle === '') $missing[] = 'calle';
if ($ciudad === '') $missing[] = 'ciudad';
if ($codigoPostal === '') $missing[] = 'codigo_postal';
if ($pais === '') $missing[] = 'pais';

if (!empty($missing)) {
    echo json_encode(['ok' => false, 'message' => 'Faltan campos: ' . implode(', ', $missing) . '.'], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['ok' => false, 'message' => 'Email inválido.'], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!preg_match('/^\d{5}$/', $codigoPostal)) {
    echo json_encode(['ok' => false, 'message' => 'El código postal debe tener exactamente 5 números.'], JSON_UNESCAPED_UNICODE);
    exit;
}

if (strcasecmp((string)$email, (string)($usuario['email'] ?? '')) !== 0) {
    echo json_encode(['ok' => false, 'message' => 'El email debe coincidir con el de tu cuenta.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$idUsuario = (int)$usuario['id_usuario'];
$direccion = $calle . ', ' . $codigoPostal . ' ' . $ciudad . ', ' . $pais;
$total = 0;
foreach ($_SESSION['carrito'] as $item) {
    $total += (float)$item['precio'] * (int)$item['cantidad'];
}

$carritoAgrupado = [];
foreach ($_SESSION['carrito'] as $item) {
    $idProducto = (int)($item['id_producto'] ?? 0);
    $idTalla = (int)($item['id_talla'] ?? 0);
    $cantidad = max(0, (int)($item['cantidad'] ?? 0));

    if ($idProducto <= 0 || $idTalla <= 0 || $cantidad <= 0) {
        continue;
    }

    $key = $idProducto . '_' . $idTalla;
    if (!isset($carritoAgrupado[$key])) {
        $carritoAgrupado[$key] = [
            'id_producto' => $idProducto,
            'id_talla' => $idTalla,
            'cantidad' => 0
        ];
    }
    $carritoAgrupado[$key]['cantidad'] += $cantidad;
}

try {
    $conexion->beginTransaction();

    $stmtStockForUpdate = $conexion->prepare(
        "SELECT stock FROM producto_tallas WHERE id_producto = :id_producto AND id_talla = :id_talla LIMIT 1 FOR UPDATE"
    );
    $stmtUpdateStock = $conexion->prepare(
        "UPDATE producto_tallas SET stock = :stock WHERE id_producto = :id_producto AND id_talla = :id_talla"
    );

    foreach ($carritoAgrupado as $itemStock) {
        $idProducto = (int)$itemStock['id_producto'];
        $idTalla = (int)$itemStock['id_talla'];
        $cantidad = (int)$itemStock['cantidad'];

        $stmtStockForUpdate->bindValue(':id_producto', $idProducto, PDO::PARAM_INT);
        $stmtStockForUpdate->bindValue(':id_talla', $idTalla, PDO::PARAM_INT);
        $stmtStockForUpdate->execute();
        $rowStock = $stmtStockForUpdate->fetch(PDO::FETCH_ASSOC);

        if (!$rowStock) {
            throw new RuntimeException('No existe stock configurado para uno de los productos/tallas del carrito.');
        }

        $stockActual = max(0, (int)$rowStock['stock']);
        if ($stockActual < $cantidad) {
            throw new RuntimeException('Stock insuficiente para completar la compra. Revisa tu carrito e inténtalo de nuevo.');
        }

        $stockNuevo = $stockActual - $cantidad;
        $stmtUpdateStock->bindValue(':stock', $stockNuevo, PDO::PARAM_INT);
        $stmtUpdateStock->bindValue(':id_producto', $idProducto, PDO::PARAM_INT);
        $stmtUpdateStock->bindValue(':id_talla', $idTalla, PDO::PARAM_INT);
        $stmtUpdateStock->execute();
    }

    $stmtPedido = $conexion->prepare("INSERT INTO pedidos (id_usuario, direccion, total, estado) VALUES (:id_usuario, :direccion, :total, 'pagado')");
    $stmtPedido->bindParam(':id_usuario', $idUsuario, PDO::PARAM_INT);
    $stmtPedido->bindParam(':direccion', $direccion);
    $stmtPedido->bindParam(':total', $total);
    $stmtPedido->execute();

    $idPedido = (int)$conexion->lastInsertId();

    $stmtDetalle = $conexion->prepare("INSERT INTO pedido_detalle (id_pedido, id_producto, id_talla, cantidad, precio_unitario) VALUES (:id_pedido, :id_producto, :id_talla, :cantidad, :precio_unitario)");

    foreach ($_SESSION['carrito'] as $item) {
        $idProducto = (int)($item['id_producto'] ?? 0);
        $idTalla = (int)($item['id_talla'] ?? 0);
        $cantidad = (int)($item['cantidad'] ?? 0);
        $precio = (float)($item['precio'] ?? 0);

        $stmtDetalle->bindParam(':id_pedido', $idPedido, PDO::PARAM_INT);
        $stmtDetalle->bindParam(':id_producto', $idProducto, PDO::PARAM_INT);
        $stmtDetalle->bindParam(':id_talla', $idTalla, PDO::PARAM_INT);
        $stmtDetalle->bindParam(':cantidad', $cantidad, PDO::PARAM_INT);
        $stmtDetalle->bindParam(':precio_unitario', $precio);
        $stmtDetalle->execute();
    }

    // Limpiar carrito en DB para todos los carritos del usuario (evita depender de carrito_id en sesión).
    $stmtDelDetalle = $conexion->prepare(
        "DELETE cd
         FROM carrito_detalle cd
         INNER JOIN carrito c ON c.id_carrito = cd.id_carrito
         WHERE c.id_usuario = :id_usuario"
    );
    $stmtDelDetalle->bindParam(':id_usuario', $idUsuario, PDO::PARAM_INT);
    $stmtDelDetalle->execute();

    $conexion->commit();
    $_SESSION['carrito'] = [];
    unset($_SESSION['carrito_id']);

    $reactBase = rtrim((string)(getenv('REACT_APP_URL') ?: 'http://localhost/veridi_tienda_web/frontend/dist'), '/');

    echo json_encode([
        'ok' => true,
        'id_pedido' => $idPedido,
        'redirect' => $reactBase . '/#/confirmacion/' . $idPedido
    ], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    if ($conexion->inTransaction()) {
        $conexion->rollBack();
    }

    $message = 'Error al procesar el pago. Inténtalo de nuevo.';
    if ($e instanceof RuntimeException) {
        $message = $e->getMessage();
    }

    echo json_encode([
        'ok' => false,
        'message' => $message
    ], JSON_UNESCAPED_UNICODE);
}
