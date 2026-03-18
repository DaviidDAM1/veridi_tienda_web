<?php
require_once "../config/conexion.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if ($origin === 'http://localhost:5173' || $origin === 'http://127.0.0.1:5173') {
    header('Access-Control-Allow-Origin: ' . $origin);
    header('Access-Control-Allow-Credentials: true');
}
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

try {
    $conexion->exec("ALTER TABLE productos ADD COLUMN oculto TINYINT(1) NOT NULL DEFAULT 0");
} catch (Exception $e) {
}

if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode([
        'ok' => false,
        'requiresLogin' => true,
        'message' => 'Debes iniciar sesión.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if (($_SESSION['usuario_rol'] ?? 'cliente') !== 'admin') {
    http_response_code(403);
    echo json_encode([
        'ok' => false,
        'requiresAdmin' => true,
        'message' => 'No tienes permisos de administrador.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

function estiloValido(string $estilo): bool
{
    return in_array($estilo, ['casual', 'formal', 'deportivo'], true);
}

function getAdminData(PDO $conexion): array
{
    $stmtCategorias = $conexion->query("SELECT id_categoria, nombre FROM categorias ORDER BY nombre ASC");
    $categorias = $stmtCategorias->fetchAll(PDO::FETCH_ASSOC) ?: [];

    $stmtTallas = $conexion->query("SELECT id_talla, nombre FROM tallas ORDER BY id_talla ASC");
    $tallas = $stmtTallas->fetchAll(PDO::FETCH_ASSOC) ?: [];

    $stmtProductos = $conexion->query("
        SELECT p.id_producto, p.nombre, p.precio, p.estilo, p.oculto, c.nombre AS categoria, COALESCE(SUM(pt.stock), 0) AS stock_total
        FROM productos p
        LEFT JOIN categorias c ON c.id_categoria = p.id_categoria
        LEFT JOIN producto_tallas pt ON pt.id_producto = p.id_producto
        GROUP BY p.id_producto
        ORDER BY p.id_producto DESC
    ");
    $productos = $stmtProductos->fetchAll(PDO::FETCH_ASSOC) ?: [];

    $stmtUsuarios = $conexion->query("SELECT id_usuario, nombre, email, password, rol FROM usuarios ORDER BY id_usuario DESC");
    $usuarios = $stmtUsuarios->fetchAll(PDO::FETCH_ASSOC) ?: [];

    return [
        'categorias' => array_map(static function ($item) {
            return [
                'id_categoria' => (int)$item['id_categoria'],
                'nombre' => (string)$item['nombre']
            ];
        }, $categorias),
        'tallas' => array_map(static function ($item) {
            return [
                'id_talla' => (int)$item['id_talla'],
                'nombre' => (string)$item['nombre']
            ];
        }, $tallas),
        'productos' => array_map(static function ($item) {
            return [
                'id_producto' => (int)$item['id_producto'],
                'nombre' => (string)$item['nombre'],
                'precio' => (float)$item['precio'],
                'estilo' => (string)$item['estilo'],
                'categoria' => (string)($item['categoria'] ?? 'Sin categoría'),
                'oculto' => (int)$item['oculto'],
                'stock_total' => (int)$item['stock_total']
            ];
        }, $productos),
        'usuarios' => array_map(static function ($item) {
            return [
                'id_usuario' => (int)$item['id_usuario'],
                'nombre' => (string)$item['nombre'],
                'email' => (string)$item['email'],
                'rol' => (string)$item['rol'],
                'password' => (string)$item['password']
            ];
        }, $usuarios)
    ];
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    echo json_encode([
        'ok' => true,
        'data' => getAdminData($conexion)
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'message' => 'Método no permitido.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$payload = json_decode(file_get_contents('php://input'), true);
if (!is_array($payload)) {
    $payload = [];
}

$action = trim((string)($payload['action'] ?? ''));

try {
    switch ($action) {
        case 'create_product': {
            $nombre = trim((string)($payload['nombre'] ?? ''));
            $descripcion = trim((string)($payload['descripcion'] ?? ''));
            $precio = (float)($payload['precio'] ?? 0);
            $color = trim((string)($payload['color'] ?? ''));
            $estilo = trim((string)($payload['estilo'] ?? 'casual'));
            $material = trim((string)($payload['material'] ?? ''));
            $idCategoria = (int)($payload['id_categoria'] ?? 0);
            $stockInicial = (int)($payload['stock_inicial'] ?? 0);

            if ($nombre === '' || $precio <= 0 || $idCategoria <= 0 || !estiloValido($estilo)) {
                throw new InvalidArgumentException('Datos inválidos al crear producto.');
            }

            if ($stockInicial < 0) {
                $stockInicial = 0;
            }

            $stmt = $conexion->prepare("INSERT INTO productos (nombre, descripcion, precio, color, estilo, material, id_categoria, oculto) VALUES (:nombre, :descripcion, :precio, :color, :estilo, :material, :id_categoria, 0)");
            $stmt->bindParam(':nombre', $nombre);
            $stmt->bindParam(':descripcion', $descripcion);
            $stmt->bindParam(':precio', $precio);
            $stmt->bindParam(':color', $color);
            $stmt->bindParam(':estilo', $estilo);
            $stmt->bindParam(':material', $material);
            $stmt->bindParam(':id_categoria', $idCategoria, PDO::PARAM_INT);
            $stmt->execute();

            $idProducto = (int)$conexion->lastInsertId();

            $stmtTallas = $conexion->query("SELECT id_talla FROM tallas");
            $tallas = $stmtTallas->fetchAll(PDO::FETCH_ASSOC) ?: [];

            $stmtInsertTalla = $conexion->prepare("INSERT INTO producto_tallas (id_producto, id_talla, stock) VALUES (:id_producto, :id_talla, :stock)");
            foreach ($tallas as $talla) {
                $idTalla = (int)$talla['id_talla'];
                $stmtInsertTalla->bindParam(':id_producto', $idProducto, PDO::PARAM_INT);
                $stmtInsertTalla->bindParam(':id_talla', $idTalla, PDO::PARAM_INT);
                $stmtInsertTalla->bindParam(':stock', $stockInicial, PDO::PARAM_INT);
                $stmtInsertTalla->execute();
            }

            $message = 'Producto creado correctamente.';
            break;
        }

        case 'edit_product': {
            $idProducto = (int)($payload['id_producto'] ?? 0);
            $nombre = trim((string)($payload['nombre'] ?? ''));
            $descripcion = trim((string)($payload['descripcion'] ?? ''));
            $precio = (float)($payload['precio'] ?? 0);
            $color = trim((string)($payload['color'] ?? ''));
            $estilo = trim((string)($payload['estilo'] ?? 'casual'));
            $material = trim((string)($payload['material'] ?? ''));
            $idCategoria = (int)($payload['id_categoria'] ?? 0);

            if ($idProducto <= 0 || $nombre === '' || $precio <= 0 || $idCategoria <= 0 || !estiloValido($estilo)) {
                throw new InvalidArgumentException('Datos inválidos al editar producto.');
            }

            $stmt = $conexion->prepare("UPDATE productos SET nombre = :nombre, descripcion = :descripcion, precio = :precio, color = :color, estilo = :estilo, material = :material, id_categoria = :id_categoria WHERE id_producto = :id_producto");
            $stmt->bindParam(':nombre', $nombre);
            $stmt->bindParam(':descripcion', $descripcion);
            $stmt->bindParam(':precio', $precio);
            $stmt->bindParam(':color', $color);
            $stmt->bindParam(':estilo', $estilo);
            $stmt->bindParam(':material', $material);
            $stmt->bindParam(':id_categoria', $idCategoria, PDO::PARAM_INT);
            $stmt->bindParam(':id_producto', $idProducto, PDO::PARAM_INT);
            $stmt->execute();

            $message = 'Producto editado correctamente.';
            break;
        }

        case 'delete_product': {
            $idProducto = (int)($payload['id_producto'] ?? 0);
            if ($idProducto <= 0) {
                throw new InvalidArgumentException('ID de producto inválido para eliminar.');
            }

            $stmt = $conexion->prepare("DELETE FROM productos WHERE id_producto = :id_producto");
            $stmt->bindParam(':id_producto', $idProducto, PDO::PARAM_INT);
            $stmt->execute();

            $message = 'Producto eliminado correctamente.';
            break;
        }

        case 'toggle_hide': {
            $idProducto = (int)($payload['id_producto'] ?? 0);
            $oculto = ((int)($payload['oculto'] ?? 0) === 1) ? 1 : 0;

            if ($idProducto <= 0) {
                throw new InvalidArgumentException('No se pudo cambiar la visibilidad.');
            }

            $stmt = $conexion->prepare("UPDATE productos SET oculto = :oculto WHERE id_producto = :id_producto");
            $stmt->bindParam(':oculto', $oculto, PDO::PARAM_INT);
            $stmt->bindParam(':id_producto', $idProducto, PDO::PARAM_INT);
            $stmt->execute();

            $message = 'Estado de visibilidad actualizado.';
            break;
        }

        case 'adjust_stock': {
            $idProducto = (int)($payload['id_producto'] ?? 0);
            $idTalla = (int)($payload['id_talla'] ?? 0);
            $delta = (int)($payload['delta'] ?? 0);

            if ($idProducto <= 0 || $idTalla <= 0 || $delta === 0) {
                throw new InvalidArgumentException('Datos inválidos al actualizar stock.');
            }

            $stmtActual = $conexion->prepare("SELECT stock FROM producto_tallas WHERE id_producto = :id_producto AND id_talla = :id_talla LIMIT 1");
            $stmtActual->bindParam(':id_producto', $idProducto, PDO::PARAM_INT);
            $stmtActual->bindParam(':id_talla', $idTalla, PDO::PARAM_INT);
            $stmtActual->execute();
            $fila = $stmtActual->fetch(PDO::FETCH_ASSOC);

            if ($fila) {
                $stockNuevo = (int)$fila['stock'] + $delta;
                if ($stockNuevo < 0) {
                    $stockNuevo = 0;
                }

                $stmtUpdate = $conexion->prepare("UPDATE producto_tallas SET stock = :stock WHERE id_producto = :id_producto AND id_talla = :id_talla");
                $stmtUpdate->bindParam(':stock', $stockNuevo, PDO::PARAM_INT);
                $stmtUpdate->bindParam(':id_producto', $idProducto, PDO::PARAM_INT);
                $stmtUpdate->bindParam(':id_talla', $idTalla, PDO::PARAM_INT);
                $stmtUpdate->execute();
            } else {
                $stockNuevo = max(0, $delta);
                $stmtInsert = $conexion->prepare("INSERT INTO producto_tallas (id_producto, id_talla, stock) VALUES (:id_producto, :id_talla, :stock)");
                $stmtInsert->bindParam(':id_producto', $idProducto, PDO::PARAM_INT);
                $stmtInsert->bindParam(':id_talla', $idTalla, PDO::PARAM_INT);
                $stmtInsert->bindParam(':stock', $stockNuevo, PDO::PARAM_INT);
                $stmtInsert->execute();
            }

            $message = 'Stock actualizado correctamente.';
            break;
        }

        default:
            throw new InvalidArgumentException('Acción de administrador no válida.');
    }

    echo json_encode([
        'ok' => true,
        'message' => $message,
        'data' => getAdminData($conexion)
    ], JSON_UNESCAPED_UNICODE);
} catch (InvalidArgumentException $e) {
    echo json_encode([
        'ok' => false,
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    echo json_encode([
        'ok' => false,
        'message' => 'Ocurrió un error al procesar la acción.'
    ], JSON_UNESCAPED_UNICODE);
}
