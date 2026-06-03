<?php
require_once "../config/conexion.php";
require_once "../config/imagenes.php";

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
        'message' => 'Debes iniciar sesión para ver el carrito.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!isset($_SESSION['carrito']) || !is_array($_SESSION['carrito'])) {
    $_SESSION['carrito'] = [];
}

function getOrCreateCartId(PDO $conexion, int $idUsuario): int
{
    $stmt = $conexion->prepare("SELECT id_carrito FROM carrito WHERE id_usuario = :id_usuario ORDER BY id_carrito DESC LIMIT 1");
    $stmt->bindValue(':id_usuario', $idUsuario, PDO::PARAM_INT);
    $stmt->execute();
    $idCarrito = (int)($stmt->fetchColumn() ?: 0);
    if ($idCarrito > 0) {
        return $idCarrito;
    }

    $insert = $conexion->prepare("INSERT INTO carrito (id_usuario) VALUES (:id_usuario)");
    $insert->bindValue(':id_usuario', $idUsuario, PDO::PARAM_INT);
    $insert->execute();
    return (int)$conexion->lastInsertId();
}

function loadCartFromDb(PDO $conexion, int $idUsuario): array
{
    $idCarrito = getOrCreateCartId($conexion, $idUsuario);
    $stmt = $conexion->prepare(
        "SELECT cd.id_producto, cd.id_talla, cd.cantidad, p.nombre, p.precio
         FROM carrito_detalle cd
         INNER JOIN productos p ON p.id_producto = cd.id_producto
         WHERE cd.id_carrito = :id_carrito
           AND (p.oculto = 0 OR p.oculto IS NULL)"
    );
    $stmt->bindValue(':id_carrito', $idCarrito, PDO::PARAM_INT);
    $stmt->execute();

    $items = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $idProducto = (int)($row['id_producto'] ?? 0);
        $idTalla = (int)($row['id_talla'] ?? 0);
        if ($idProducto <= 0 || $idTalla <= 0) {
            continue;
        }
        $key = $idProducto . '_' . $idTalla;
        $nombre = (string)($row['nombre'] ?? 'Producto');
        $items[$key] = [
            'id_producto' => $idProducto,
            'id_talla' => $idTalla,
            'nombre' => $nombre,
            'precio' => (float)($row['precio'] ?? 0),
            'imagen' => obtenerImagenProducto($idProducto, $nombre),
            'cantidad' => max(1, (int)($row['cantidad'] ?? 1))
        ];
    }

    return $items;
}

function saveCartToDb(PDO $conexion, int $idUsuario, array $carrito): void
{
    $idCarrito = getOrCreateCartId($conexion, $idUsuario);

    $delete = $conexion->prepare("DELETE FROM carrito_detalle WHERE id_carrito = :id_carrito");
    $delete->bindValue(':id_carrito', $idCarrito, PDO::PARAM_INT);
    $delete->execute();

    if (empty($carrito)) {
        return;
    }

    $insert = $conexion->prepare(
        "INSERT INTO carrito_detalle (id_carrito, id_producto, id_talla, cantidad)
         VALUES (:id_carrito, :id_producto, :id_talla, :cantidad)"
    );

    foreach ($carrito as $item) {
        $idProducto = (int)($item['id_producto'] ?? 0);
        $idTalla = (int)($item['id_talla'] ?? 0);
        $cantidad = max(1, (int)($item['cantidad'] ?? 1));
        if ($idProducto <= 0 || $idTalla <= 0) {
            continue;
        }
        $insert->bindValue(':id_carrito', $idCarrito, PDO::PARAM_INT);
        $insert->bindValue(':id_producto', $idProducto, PDO::PARAM_INT);
        $insert->bindValue(':id_talla', $idTalla, PDO::PARAM_INT);
        $insert->bindValue(':cantidad', $cantidad, PDO::PARAM_INT);
        $insert->execute();
    }
}

function syncSessionCartFromDb(PDO $conexion): void
{
    $idUsuario = (int)($_SESSION['usuario_id'] ?? 0);
    if ($idUsuario <= 0) {
        return;
    }
    $_SESSION['carrito'] = loadCartFromDb($conexion, $idUsuario);
}

function persistSessionCartToDb(PDO $conexion): void
{
    $idUsuario = (int)($_SESSION['usuario_id'] ?? 0);
    if ($idUsuario <= 0) {
        return;
    }
    $carrito = $_SESSION['carrito'] ?? [];
    if (!is_array($carrito)) {
        $carrito = [];
    }
    saveCartToDb($conexion, $idUsuario, $carrito);
}

function outputCart(PDO $conexion): void
{
    syncSessionCartFromDb($conexion);
    $carrito = $_SESSION['carrito'] ?? [];
    $tallasNombres = [];
    $tallasPorProducto = [];
    $total = 0;

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

    $idsProductos = [];
    foreach ($carrito as $item) {
        $idProducto = (int)($item['id_producto'] ?? 0);
        if ($idProducto > 0) {
            $idsProductos[$idProducto] = true;
        }
    }

    if (!empty($idsProductos)) {
        $productoIds = array_keys($idsProductos);
        $placeholdersProductos = implode(',', array_fill(0, count($productoIds), '?'));
        $stmtSizes = $conexion->prepare(
            "SELECT pt.id_producto, pt.id_talla, t.nombre, pt.stock
             FROM producto_tallas pt
             INNER JOIN tallas t ON t.id_talla = pt.id_talla
             WHERE pt.id_producto IN ($placeholdersProductos)
               AND pt.stock > 0
             ORDER BY pt.id_producto ASC, pt.id_talla ASC"
        );
        $stmtSizes->execute($productoIds);

        while ($row = $stmtSizes->fetch(PDO::FETCH_ASSOC)) {
            $idProducto = (int)$row['id_producto'];
            if (!isset($tallasPorProducto[$idProducto])) {
                $tallasPorProducto[$idProducto] = [];
            }
            $tallasPorProducto[$idProducto][] = [
                'id_talla' => (int)$row['id_talla'],
                'nombre' => (string)$row['nombre'],
                'stock' => (int)$row['stock']
            ];
        }
    }

    $itemsOut = [];
    foreach ($carrito as $itemKey => $item) {
        $cantidad = (int)($item['cantidad'] ?? 0);
        $precio = (float)($item['precio'] ?? 0);
        $subtotal = $cantidad * $precio;
        $total += $subtotal;

        $idTalla = (int)($item['id_talla'] ?? 0);
        $idProducto = (int)($item['id_producto'] ?? 0);
        $itemsOut[] = [
            'item_key' => $itemKey,
            'id_producto' => $idProducto,
            'id_talla' => $idTalla,
            'nombre' => (string)($item['nombre'] ?? 'Producto'),
            'precio' => $precio,
            'cantidad' => $cantidad,
            'imagen' => (string)($item['imagen'] ?? ''),
            'subtotal' => $subtotal,
            'talla_nombre' => $idTalla > 0 ? ($tallasNombres[$idTalla] ?? ('ID: ' . $idTalla)) : '',
            'available_tallas' => $tallasPorProducto[$idProducto] ?? []
        ];
    }

    echo json_encode([
        'ok' => true,
        'items' => $itemsOut,
        'total' => $total,
        'totalItems' => count($itemsOut)
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    outputCart($conexion);
}

$rawBody = file_get_contents('php://input');
$payload = [];
if ($rawBody) {
    $json = json_decode($rawBody, true);
    if (is_array($json)) {
        $payload = $json;
    }
}

$action = trim((string)($payload['action'] ?? ($_POST['action'] ?? '')));
$idProducto = isset($payload['id_producto']) ? (int)$payload['id_producto'] : (isset($_POST['id_producto']) ? (int)$_POST['id_producto'] : 0);
$idTalla = isset($payload['id_talla']) ? (int)$payload['id_talla'] : (isset($_POST['id_talla']) ? (int)$_POST['id_talla'] : 0);
$delta = isset($payload['delta']) ? (int)$payload['delta'] : (isset($_POST['delta']) ? (int)$_POST['delta'] : 0);

// Always start POST actions from DB state to avoid stale session data overwriting persisted cart.
syncSessionCartFromDb($conexion);

$itemKey = $idProducto . '_' . $idTalla;

switch ($action) {
    case 'add_item':
        if ($idProducto <= 0 || $idTalla <= 0) {
            echo json_encode([
                'ok' => false,
                'message' => 'Selecciona un producto y talla válidos.'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $stmtStock = $conexion->prepare("SELECT stock FROM producto_tallas WHERE id_producto = :id_producto AND id_talla = :id_talla LIMIT 1");
        $stmtStock->bindParam(':id_producto', $idProducto, PDO::PARAM_INT);
        $stmtStock->bindParam(':id_talla', $idTalla, PDO::PARAM_INT);
        $stmtStock->execute();
        $filaStock = $stmtStock->fetch(PDO::FETCH_ASSOC);

        if (!$filaStock) {
            echo json_encode([
                'ok' => false,
                'message' => 'La talla seleccionada no está disponible para este producto.'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $stockDisponible = max(0, (int)$filaStock['stock']);
        if ($stockDisponible <= 0) {
            echo json_encode([
                'ok' => false,
                'message' => 'No hay stock disponible para la talla seleccionada.'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $nombre = trim((string)($payload['nombre'] ?? 'Producto'));
        $precio = (float)($payload['precio'] ?? 0);
        $imagen = trim((string)($payload['imagen'] ?? ''));

        if (!isset($_SESSION['carrito'][$itemKey])) {
            $_SESSION['carrito'][$itemKey] = [
                'id_producto' => $idProducto,
                'id_talla' => $idTalla,
                'nombre' => $nombre,
                'precio' => $precio,
                'imagen' => $imagen,
                'cantidad' => 1
            ];
        } else {
            $cantidadActual = (int)($_SESSION['carrito'][$itemKey]['cantidad'] ?? 0);
            if ($cantidadActual < $stockDisponible) {
                $_SESSION['carrito'][$itemKey]['cantidad'] = $cantidadActual + 1;
            }
        }

        if ((int)$_SESSION['carrito'][$itemKey]['cantidad'] > $stockDisponible) {
            $_SESSION['carrito'][$itemKey]['cantidad'] = $stockDisponible;
        }
        persistSessionCartToDb($conexion);
        break;

    case 'update_quantity':
        if ($idProducto > 0 && $idTalla > 0 && isset($_SESSION['carrito'][$itemKey])) {
            $stmtStock = $conexion->prepare("SELECT stock FROM producto_tallas WHERE id_producto = :id_producto AND id_talla = :id_talla LIMIT 1");
            $stmtStock->bindParam(':id_producto', $idProducto, PDO::PARAM_INT);
            $stmtStock->bindParam(':id_talla', $idTalla, PDO::PARAM_INT);
            $stmtStock->execute();
            $filaStock = $stmtStock->fetch(PDO::FETCH_ASSOC);
            $stockDisponible = $filaStock ? max(0, (int)$filaStock['stock']) : 0;

            $_SESSION['carrito'][$itemKey]['cantidad'] += $delta;
            if ($stockDisponible > 0 && (int)$_SESSION['carrito'][$itemKey]['cantidad'] > $stockDisponible) {
                $_SESSION['carrito'][$itemKey]['cantidad'] = $stockDisponible;
            }
            if ((int)$_SESSION['carrito'][$itemKey]['cantidad'] <= 0) {
                unset($_SESSION['carrito'][$itemKey]);
            }
        }
        persistSessionCartToDb($conexion);
        break;

    case 'update_size':
        $newIdTalla = isset($payload['new_id_talla']) ? (int)$payload['new_id_talla'] : (isset($_POST['new_id_talla']) ? (int)$_POST['new_id_talla'] : 0);

        if ($idProducto <= 0 || $idTalla <= 0 || $newIdTalla <= 0 || !isset($_SESSION['carrito'][$itemKey])) {
            echo json_encode([
                'ok' => false,
                'message' => 'Datos de talla no validos.'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        if ($newIdTalla === $idTalla) {
            outputCart($conexion);
        }

        $stmtStock = $conexion->prepare("SELECT stock FROM producto_tallas WHERE id_producto = :id_producto AND id_talla = :id_talla LIMIT 1");
        $stmtStock->bindValue(':id_producto', $idProducto, PDO::PARAM_INT);
        $stmtStock->bindValue(':id_talla', $newIdTalla, PDO::PARAM_INT);
        $stmtStock->execute();
        $stockRow = $stmtStock->fetch(PDO::FETCH_ASSOC);

        if (!$stockRow || (int)$stockRow['stock'] <= 0) {
            echo json_encode([
                'ok' => false,
                'message' => 'La talla seleccionada no tiene stock disponible.'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $stockDestino = (int)$stockRow['stock'];
        $oldQty = (int)($_SESSION['carrito'][$itemKey]['cantidad'] ?? 0);
        if ($oldQty <= 0) {
            unset($_SESSION['carrito'][$itemKey]);
            outputCart($conexion);
        }

        $newItemKey = $idProducto . '_' . $newIdTalla;
        $destQty = isset($_SESSION['carrito'][$newItemKey])
            ? (int)($_SESSION['carrito'][$newItemKey]['cantidad'] ?? 0)
            : 0;

        $capacity = max(0, $stockDestino - $destQty);
        if ($capacity <= 0) {
            echo json_encode([
                'ok' => false,
                'message' => 'No puedes mover mas unidades a esa talla porque alcanzaste su stock.'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $moveQty = min($oldQty, $capacity);

        $sourceItem = $_SESSION['carrito'][$itemKey];
        if (!isset($_SESSION['carrito'][$newItemKey])) {
            $_SESSION['carrito'][$newItemKey] = [
                'id_producto' => $idProducto,
                'id_talla' => $newIdTalla,
                'nombre' => (string)($sourceItem['nombre'] ?? 'Producto'),
                'precio' => (float)($sourceItem['precio'] ?? 0),
                'imagen' => (string)($sourceItem['imagen'] ?? ''),
                'cantidad' => $moveQty
            ];
        } else {
            $_SESSION['carrito'][$newItemKey]['cantidad'] = $destQty + $moveQty;
        }

        $_SESSION['carrito'][$itemKey]['cantidad'] = $oldQty - $moveQty;
        if ((int)$_SESSION['carrito'][$itemKey]['cantidad'] <= 0) {
            unset($_SESSION['carrito'][$itemKey]);
        }
        persistSessionCartToDb($conexion);
        break;

    case 'remove_item':
        if ($idProducto > 0 && $idTalla > 0 && isset($_SESSION['carrito'][$itemKey])) {
            unset($_SESSION['carrito'][$itemKey]);
        }
        persistSessionCartToDb($conexion);
        break;

    case 'clear_cart':
        $_SESSION['carrito'] = [];
        persistSessionCartToDb($conexion);
        break;

    case 'add_outfit':
        $productIdsRaw = $payload['product_ids'] ?? [];
        if (!is_array($productIdsRaw) || empty($productIdsRaw)) {
            echo json_encode([
                'ok' => false,
                'message' => 'No hay productos para agregar al carrito.'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $productIds = [];
        foreach ($productIdsRaw as $rawId) {
            $id = (int)$rawId;
            if ($id > 0) {
                $productIds[$id] = true;
            }
        }

        if (empty($productIds)) {
            echo json_encode([
                'ok' => false,
                'message' => 'Los productos del outfit no son validos.'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $stmtProduct = $conexion->prepare(
            "SELECT id_producto, nombre, precio FROM productos
             WHERE id_producto = :id_producto AND (oculto = 0 OR oculto IS NULL)
             LIMIT 1"
        );
        $stmtSize = $conexion->prepare(
            "SELECT id_talla, stock FROM producto_tallas
             WHERE id_producto = :id_producto AND stock > 0
             ORDER BY id_talla ASC
             LIMIT 1"
        );

        foreach (array_keys($productIds) as $idProductoOutfit) {
            $stmtProduct->bindValue(':id_producto', $idProductoOutfit, PDO::PARAM_INT);
            $stmtProduct->execute();
            $producto = $stmtProduct->fetch(PDO::FETCH_ASSOC);
            if (!$producto) {
                continue;
            }

            $stmtSize->bindValue(':id_producto', $idProductoOutfit, PDO::PARAM_INT);
            $stmtSize->execute();
            $sizeRow = $stmtSize->fetch(PDO::FETCH_ASSOC);
            if (!$sizeRow) {
                continue;
            }

            $idTallaOutfit = (int)$sizeRow['id_talla'];
            $stockDisponible = max(0, (int)$sizeRow['stock']);
            if ($idTallaOutfit <= 0 || $stockDisponible <= 0) {
                continue;
            }

            $itemKeyOutfit = $idProductoOutfit . '_' . $idTallaOutfit;

            if (!isset($_SESSION['carrito'][$itemKeyOutfit])) {
                $_SESSION['carrito'][$itemKeyOutfit] = [
                    'id_producto' => (int)$producto['id_producto'],
                    'id_talla' => $idTallaOutfit,
                    'nombre' => (string)$producto['nombre'],
                    'precio' => (float)$producto['precio'],
                    'imagen' => obtenerImagenProducto((int)$producto['id_producto'], (string)$producto['nombre']),
                    'cantidad' => 1
                ];
            } else {
                $cantidadActual = (int)($_SESSION['carrito'][$itemKeyOutfit]['cantidad'] ?? 0);
                if ($cantidadActual < $stockDisponible) {
                    $_SESSION['carrito'][$itemKeyOutfit]['cantidad'] = $cantidadActual + 1;
                }
            }

            if ((int)$_SESSION['carrito'][$itemKeyOutfit]['cantidad'] > $stockDisponible) {
                $_SESSION['carrito'][$itemKeyOutfit]['cantidad'] = $stockDisponible;
            }
        }
        persistSessionCartToDb($conexion);
        break;

    default:
        echo json_encode([
            'ok' => false,
            'message' => 'Acción no válida.'
        ], JSON_UNESCAPED_UNICODE);
        exit;
}

outputCart($conexion);
