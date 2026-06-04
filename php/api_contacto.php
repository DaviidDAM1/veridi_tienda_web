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
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With, Authorization');
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// Función para asegurar que la tabla contacto existe
function ensureContactoTableExists(PDO $conexion): bool
{
    try {
        $checkTable = $conexion->query("SHOW TABLES LIKE 'contacto'");
        if ($checkTable && $checkTable->rowCount() === 0) {
            // Crear la tabla si no existe
            $createTableSQL = "
            CREATE TABLE IF NOT EXISTS contacto (
                id_contacto INT AUTO_INCREMENT PRIMARY KEY,
                nombre VARCHAR(100) NOT NULL,
                email VARCHAR(150) NOT NULL,
                asunto VARCHAR(200),
                mensaje TEXT NOT NULL,
                fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                leido BOOLEAN DEFAULT FALSE
            )
            ";
            $conexion->exec($createTableSQL);
            error_log("Tabla contacto creada exitosamente");
            return true;
        }
        return true;
    } catch (Exception $e) {
        error_log("Error asegurando tabla contacto: " . $e->getMessage());
        return false;
    }
}

$emailWeb = 'info@veridi.com';

function getUsuarioActual(PDO $conexion): ?array
{
    if (!isset($_SESSION['usuario_id'])) {
        return null;
    }

    $stmt = $conexion->prepare("SELECT email, nombre FROM usuarios WHERE id_usuario = ? LIMIT 1");
    $stmt->execute([$_SESSION['usuario_id']]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$usuario) {
        return null;
    }

    return [
        'email' => (string)($usuario['email'] ?? ''),
        'nombre' => (string)($usuario['nombre'] ?? '')
    ];
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $usuario = getUsuarioActual($conexion);

    echo json_encode([
        'ok' => true,
        'contacto' => [
            'email_web' => $emailWeb,
            'logueado' => $usuario !== null,
            'email_usuario' => $usuario['email'] ?? '',
            'nombre_usuario' => $usuario['nombre'] ?? ''
        ]
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'ok' => false,
        'message' => 'Método no permitido.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$usuario = getUsuarioActual($conexion);
if ($usuario === null) {
    echo json_encode([
        'ok' => false,
        'message' => '❌ Debes iniciar sesión para enviar un mensaje desde Login / Registro en el encabezado.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$payload = json_decode(file_get_contents('php://input'), true);
if (!is_array($payload)) {
    $payload = [];
}

$nombre = trim((string)($payload['nombre'] ?? ''));
$email = trim((string)($payload['email'] ?? ''));
$tipo = trim((string)($payload['tipo'] ?? ''));
$mensaje = trim((string)($payload['mensaje'] ?? ''));

if ($nombre === '') {
    $nombre = (string)($usuario['nombre'] ?? '');
}
if ($email === '') {
    $email = (string)($usuario['email'] ?? '');
}

if ($email !== $usuario['email']) {
    echo json_encode([
        'ok' => false,
        'message' => '❌ El email ingresado no coincide con tu email de cuenta (' . $usuario['email'] . '). Por seguridad, debes usar el email asociado a tu cuenta.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($tipo === '' || $mensaje === '') {
    echo json_encode([
        'ok' => false,
        'message' => '❌ Debes completar tipo de asunto y mensaje.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode([
        'ok' => false,
        'message' => '❌ El email no es válido.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($nombre === '') {
    echo json_encode([
        'ok' => false,
        'message' => '❌ El nombre es requerido.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Asegurar que la tabla existe antes de insertar
if (!ensureContactoTableExists($conexion)) {
    echo json_encode([
        'ok' => false,
        'message' => '❌ Error de base de datos: no se pudo verificar la tabla de contacto.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $stmt = $conexion->prepare("INSERT INTO contacto (nombre, email, asunto, mensaje) VALUES (?, ?, ?, ?)");
    if (!$stmt) {
        throw new Exception("Error preparando consulta SQL");
    }
    
    $result = $stmt->execute([$nombre, $email, $tipo, $mensaje]);
    if (!$result) {
        $errorInfo = $stmt->errorInfo();
        throw new Exception("Error SQL: " . ($errorInfo[2] ?? "Desconocido"));
    }
} catch (Exception $e) {
    error_log("Contact form error: " . $e->getMessage());
    echo json_encode([
        'ok' => false,
        'message' => '❌ Error: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

echo json_encode([
    'ok' => true,
    'message' => '✓ Mensaje enviado correctamente. Te responderemos pronto a ' . $email
], JSON_UNESCAPED_UNICODE);
