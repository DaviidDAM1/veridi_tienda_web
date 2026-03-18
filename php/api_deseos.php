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

if (!isset($_SESSION['deseos']) || !is_array($_SESSION['deseos'])) {
    $_SESSION['deseos'] = [];
}

function jsonResponse(array $payload, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

function requireLogin(): void
{
    if (!isset($_SESSION['usuario_id'])) {
        jsonResponse([
            'ok' => false,
            'requiresLogin' => true,
            'message' => 'Debes iniciar sesión para acceder a tus favoritos.'
        ], 401);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    requireLogin();

    $deseos = array_values(array_map(static function ($item) {
        return [
            'id_producto' => (int)($item['id_producto'] ?? 0),
            'nombre' => (string)($item['nombre'] ?? 'Producto'),
            'precio' => (float)($item['precio'] ?? 0),
            'imagen' => (string)($item['imagen'] ?? '')
        ];
    }, $_SESSION['deseos']));

    jsonResponse([
        'ok' => true,
        'deseos' => $deseos,
        'total' => count($deseos)
    ]);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse([
        'ok' => false,
        'message' => 'Método no permitido.'
    ], 405);
}

requireLogin();

$payload = json_decode(file_get_contents('php://input'), true);
if (!is_array($payload)) {
    $payload = [];
}

$action = trim((string)($payload['action'] ?? ''));
$idProducto = (int)($payload['id_producto'] ?? 0);

if ($idProducto <= 0) {
    jsonResponse([
        'ok' => false,
        'message' => 'Producto inválido.'
    ], 400);
}

switch ($action) {
    case 'add': {
        $nombre = trim((string)($payload['nombre'] ?? 'Producto'));
        $precio = (float)($payload['precio'] ?? 0);
        $imagen = trim((string)($payload['imagen'] ?? ''));

        $_SESSION['deseos'][$idProducto] = [
            'id_producto' => $idProducto,
            'nombre' => $nombre,
            'precio' => $precio,
            'imagen' => $imagen
        ];

        jsonResponse([
            'ok' => true,
            'message' => 'Agregado a favoritos.',
            'esFavorito' => true,
            'total' => count($_SESSION['deseos'])
        ]);
    }

    case 'remove': {
        foreach ($_SESSION['deseos'] as $key => $fav) {
            if ((int)($fav['id_producto'] ?? 0) === $idProducto) {
                unset($_SESSION['deseos'][$key]);
            }
        }

        if (isset($_SESSION['deseos'][$idProducto])) {
            unset($_SESSION['deseos'][$idProducto]);
        }

        jsonResponse([
            'ok' => true,
            'message' => 'Eliminado de favoritos.',
            'esFavorito' => false,
            'total' => count($_SESSION['deseos'])
        ]);
    }

    case 'move_to_cart': {
        if (!isset($_SESSION['deseos'][$idProducto])) {
            foreach ($_SESSION['deseos'] as $fav) {
                if ((int)($fav['id_producto'] ?? 0) === $idProducto) {
                    $_SESSION['deseos'][$idProducto] = $fav;
                    break;
                }
            }
        }

        jsonResponse([
            'ok' => true,
            'message' => 'Selecciona la talla para agregar al carrito.',
            'redirect' => '/producto/' . $idProducto
        ]);
    }

    case 'check': {
        $esFavorito = false;
        foreach ($_SESSION['deseos'] as $fav) {
            if ((int)($fav['id_producto'] ?? 0) === $idProducto) {
                $esFavorito = true;
                break;
            }
        }

        if (isset($_SESSION['deseos'][$idProducto])) {
            $esFavorito = true;
        }

        jsonResponse([
            'ok' => true,
            'esFavorito' => $esFavorito,
            'total' => count($_SESSION['deseos'])
        ]);
    }

    default:
        jsonResponse([
            'ok' => false,
            'message' => 'Acción no reconocida.'
        ], 400);
}
