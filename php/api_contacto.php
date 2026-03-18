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

$emailWeb = 'info@veridi.com';

function getUsuarioActual(PDO $conexion): ?array
{
    if (!isset($_SESSION['usuario_id'])) {
        return null;
    }

    $stmt = $conexion->prepare("SELECT email, nombre, password FROM usuarios WHERE id_usuario = ? LIMIT 1");
    $stmt->execute([$_SESSION['usuario_id']]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$usuario) {
        return null;
    }

    return [
        'email' => (string)($usuario['email'] ?? ''),
        'nombre' => (string)($usuario['nombre'] ?? ''),
        'password' => (string)($usuario['password'] ?? '')
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
$contrasena = trim((string)($payload['contrasena'] ?? ''));

if ($email !== $usuario['email']) {
    echo json_encode([
        'ok' => false,
        'message' => '❌ El email ingresado no coincide con tu email de cuenta (' . $usuario['email'] . '). Por seguridad, debes usar el email asociado a tu cuenta.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($email === '' || $tipo === '' || $mensaje === '' || $contrasena === '') {
    echo json_encode([
        'ok' => false,
        'message' => '❌ Todos los campos son obligatorios.'
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

if (strlen($contrasena) < 6) {
    echo json_encode([
        'ok' => false,
        'message' => '❌ La contraseña debe tener al menos 6 caracteres.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!password_verify($contrasena, $usuario['password'])) {
    echo json_encode([
        'ok' => false,
        'message' => '❌ La contraseña ingresada es incorrecta.'
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

$stmt = $conexion->prepare("INSERT INTO contacto (nombre, email, asunto, mensaje, contrasena) VALUES (?, ?, ?, ?, ?)");
$ok = $stmt->execute([$nombre, $email, $tipo, $mensaje, $contrasena]);

if (!$ok) {
    echo json_encode([
        'ok' => false,
        'message' => '❌ Hubo un error al enviar el mensaje. Intenta de nuevo.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

echo json_encode([
    'ok' => true,
    'message' => '✓ Mensaje enviado correctamente. Te responderemos pronto a ' . $email
], JSON_UNESCAPED_UNICODE);
