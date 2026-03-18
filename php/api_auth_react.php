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

        echo json_encode(['ok' => true, 'message' => 'Sesión iniciada correctamente.'], JSON_UNESCAPED_UNICODE);
        exit;
    } catch (Exception $e) {
        echo json_encode(['ok' => false, 'message' => 'Ha ocurrido un error. Inténtalo de nuevo.'], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

echo json_encode(['ok' => false, 'message' => 'Acción no reconocida.'], JSON_UNESCAPED_UNICODE);
