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
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
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

function loadCartSessionData(PDO $conexion, int $idUsuario): array
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

    $carrito = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $idProducto = (int)($row['id_producto'] ?? 0);
        $idTalla = (int)($row['id_talla'] ?? 0);
        if ($idProducto <= 0 || $idTalla <= 0) {
            continue;
        }
        $key = $idProducto . '_' . $idTalla;
        $carrito[$key] = [
            'id_producto' => $idProducto,
            'id_talla' => $idTalla,
            'nombre' => (string)($row['nombre'] ?? 'Producto'),
            'precio' => (float)($row['precio'] ?? 0),
            'imagen' => '',
            'cantidad' => max(1, (int)($row['cantidad'] ?? 1))
        ];
    }

    return $carrito;
}

function ensureDeseosTable(PDO $conexion): void
{
    $conexion->exec(
        "CREATE TABLE IF NOT EXISTS deseos_usuario (
            id_deseo INT AUTO_INCREMENT PRIMARY KEY,
            id_usuario INT NOT NULL,
            id_producto INT NOT NULL,
            fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uk_deseos_usuario_producto (id_usuario, id_producto)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    );
}

function loadDeseosSessionData(PDO $conexion, int $idUsuario): array
{
    ensureDeseosTable($conexion);

    $stmt = $conexion->prepare(
        "SELECT p.id_producto, p.nombre, p.precio
         FROM deseos_usuario d
         INNER JOIN productos p ON p.id_producto = d.id_producto
         WHERE d.id_usuario = :id_usuario
           AND (p.oculto = 0 OR p.oculto IS NULL)"
    );
    $stmt->bindValue(':id_usuario', $idUsuario, PDO::PARAM_INT);
    $stmt->execute();

    $deseos = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $idProducto = (int)($row['id_producto'] ?? 0);
        if ($idProducto <= 0) {
            continue;
        }
        $deseos[$idProducto] = [
            'id_producto' => $idProducto,
            'nombre' => (string)($row['nombre'] ?? 'Producto'),
            'precio' => (float)($row['precio'] ?? 0),
            'imagen' => ''
        ];
    }

    return $deseos;
}

$action = trim((string)($payload['action'] ?? ''));

if ($action === 'logout') {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();

    echo json_encode(['ok' => true, 'message' => 'Sesión cerrada correctamente.'], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($action === 'register') {
    $nombre = trim((string)($payload['nombre'] ?? ''));
    $email = trim((string)($payload['email'] ?? ''));
    $password = (string)($payload['password'] ?? '');
    $passwordConfirm = (string)($payload['password_confirm'] ?? '');

    if ($nombre === '' || $email === '' || $password === '') {
        echo json_encode(['ok' => false, 'message' => 'Completa todos los campos.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if (strlen($password) < 6) {
        echo json_encode(['ok' => false, 'message' => 'La contraseña debe tener al menos 6 caracteres.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($password !== $passwordConfirm) {
        echo json_encode(['ok' => false, 'message' => 'Las contraseñas no coinciden.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    try {
        $stmtExiste = $conexion->prepare("SELECT id_usuario FROM usuarios WHERE email = :email LIMIT 1");
        $stmtExiste->bindParam(':email', $email);
        $stmtExiste->execute();

        if ($stmtExiste->fetch(PDO::FETCH_ASSOC)) {
            echo json_encode(['ok' => false, 'message' => 'Este email ya está registrado.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        $stmtInsert = $conexion->prepare("INSERT INTO usuarios (nombre, email, password) VALUES (:nombre, :email, :password)");
        $stmtInsert->bindParam(':nombre', $nombre);
        $stmtInsert->bindParam(':email', $email);
        $stmtInsert->bindParam(':password', $passwordHash);
        $stmtInsert->execute();

        echo json_encode(['ok' => true, 'message' => 'Registro completado. Ahora puedes iniciar sesión.'], JSON_UNESCAPED_UNICODE);
        exit;
    } catch (Exception $e) {
        echo json_encode(['ok' => false, 'message' => 'Ha ocurrido un error. Inténtalo de nuevo.'], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

if ($action === 'login') {
    $email = trim((string)($payload['email'] ?? ''));
    $password = (string)($payload['password'] ?? '');

    if ($email === '' || $password === '') {
        echo json_encode(['ok' => false, 'message' => 'Completa todos los campos.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    try {
        $stmt = $conexion->prepare("SELECT * FROM usuarios WHERE email = :email LIMIT 1");
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$usuario || !password_verify($password, $usuario['password'])) {
            echo json_encode(['ok' => false, 'message' => 'Email o contraseña incorrectos.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $_SESSION['usuario_id'] = (int)$usuario['id_usuario'];
        $_SESSION['usuario_nombre'] = (string)$usuario['nombre'];
        $_SESSION['usuario_rol'] = (string)($usuario['rol'] ?? 'cliente');

        if (!isset($_SESSION['carrito']) || !is_array($_SESSION['carrito'])) {
            $_SESSION['carrito'] = [];
        }
        if (!isset($_SESSION['deseos']) || !is_array($_SESSION['deseos'])) {
            $_SESSION['deseos'] = [];
        }

        $_SESSION['carrito'] = loadCartSessionData($conexion, (int)$usuario['id_usuario']);
        $_SESSION['deseos'] = loadDeseosSessionData($conexion, (int)$usuario['id_usuario']);

        echo json_encode(['ok' => true, 'message' => 'Sesión iniciada correctamente.'], JSON_UNESCAPED_UNICODE);
        exit;
    } catch (Exception $e) {
        echo json_encode(['ok' => false, 'message' => 'Ha ocurrido un error. Inténtalo de nuevo.'], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

echo json_encode(['ok' => false, 'message' => 'Acción no reconocida.'], JSON_UNESCAPED_UNICODE);
