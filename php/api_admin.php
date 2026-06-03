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

function categoriaEsGorras(PDO $conexion, int $idCategoria): bool
{
    if ($idCategoria <= 0) {
        return false;
    }

    $stmt = $conexion->prepare("SELECT nombre FROM categorias WHERE id_categoria = :id_categoria LIMIT 1");
    $stmt->bindParam(':id_categoria', $idCategoria, PDO::PARAM_INT);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        return false;
    }

    return mb_strtolower(trim((string)$row['nombre']), 'UTF-8') === 'gorras';
}

function procesarImagenProductoAdmin(array $archivo, int $idProducto): string
{
    if (($archivo['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return '';
    }

    if (($archivo['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        throw new InvalidArgumentException('No se pudo procesar la imagen seleccionada.');
    }

    $tamanoMaximo = 5 * 1024 * 1024;
    if (($archivo['size'] ?? 0) > $tamanoMaximo) {
        throw new InvalidArgumentException('La imagen supera el tamaño máximo (5MB).');
    }

    $tmp = (string)($archivo['tmp_name'] ?? '');
    $mime = '';
    if ($tmp !== '') {
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = (string)$finfo->file($tmp);
    }

    $permitidos = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp'
    ];

    if (!isset($permitidos[$mime])) {
        throw new InvalidArgumentException('Formato de imagen no permitido (solo JPG, PNG o WEBP).');
    }

    $extension = $permitidos[$mime];
    $nombreArchivo = 'producto_' . $idProducto . '_' . time() . '.' . $extension;
    $directorioAbs = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'imgnuevas' . DIRECTORY_SEPARATOR . 'admin';
    if (!is_dir($directorioAbs)) {
        @mkdir($directorioAbs, 0777, true);
    }

    $rutaAbs = $directorioAbs . DIRECTORY_SEPARATOR . $nombreArchivo;
    $rutaRel = 'imgnuevas/admin/' . $nombreArchivo;

    if (!move_uploaded_file($tmp, $rutaAbs)) {
        throw new InvalidArgumentException('No se pudo guardar la imagen del producto.');
    }

    return $rutaRel;
}

function eliminarArchivoRelativo(string $rutaRelativa): void
{
    $rutaRelativa = trim($rutaRelativa);
    if ($rutaRelativa === '') {
        return;
    }

    $rutaAbs = dirname(__DIR__) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $rutaRelativa);
    if (is_file($rutaAbs)) {
        @unlink($rutaAbs);
    }
}

function getAdminData(PDO $conexion): array
{
    $stmtCategorias = $conexion->query("SELECT id_categoria, nombre FROM categorias ORDER BY nombre ASC");
    $categorias = $stmtCategorias->fetchAll(PDO::FETCH_ASSOC) ?: [];

    $stmtTallas = $conexion->query("SELECT id_talla, nombre FROM tallas ORDER BY id_talla ASC");
    $tallas = $stmtTallas->fetchAll(PDO::FETCH_ASSOC) ?: [];

    $stmtProductos = $conexion->query("
        SELECT p.id_producto, p.nombre, p.precio,
               CASE WHEN LOWER(TRIM(c.nombre)) = 'gorras' THEN 'casual' ELSE p.estilo END AS estilo,
               p.oculto, c.nombre AS categoria, COALESCE(SUM(pt.stock), 0) AS stock_total
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


$isMultipart = stripos((string)($_SERVER['CONTENT_TYPE'] ?? ''), 'multipart/form-data') !== false;
if ($isMultipart) {
    $payload = $_POST;
} else {
    $payload = json_decode(file_get_contents('php://input'), true);
    if (!is_array($payload)) {
        $payload = [];
    }
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

            if (categoriaEsGorras($conexion, $idCategoria)) {
                $estilo = 'casual';
            }

            if ($nombre === '' || $precio <= 0 || $idCategoria <= 0 || !estiloValido($estilo)) {
                throw new InvalidArgumentException('Datos inválidos al crear producto.');
            }

            if ($stockInicial < 0) {
                $stockInicial = 0;
            }

            $rutaImagenSubida = '';
            $conexion->beginTransaction();

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

            if (isset($_FILES['imagen_producto']) && is_array($_FILES['imagen_producto'])) {
                $rutaImagenSubida = procesarImagenProductoAdmin($_FILES['imagen_producto'], $idProducto);
                if ($rutaImagenSubida !== '') {
                    $guardada = guardarImagenProductoPersonalizada($idProducto, $rutaImagenSubida);
                    if (!$guardada) {
                        throw new RuntimeException('No se pudo registrar la imagen del producto.');
                    }
                }
            }

            $conexion->commit();

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

            if (categoriaEsGorras($conexion, $idCategoria)) {
                $estilo = 'casual';
            }

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
    if ($conexion->inTransaction()) {
        $conexion->rollBack();
    }
    echo json_encode([
        'ok' => false,
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    if ($conexion->inTransaction()) {
        $conexion->rollBack();
    }
    echo json_encode([
        'ok' => false,
        'message' => 'Ocurrió un error al procesar la acción.'
    ], JSON_UNESCAPED_UNICODE);
}
