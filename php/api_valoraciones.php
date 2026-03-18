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
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode([
        'ok' => false,
        'message' => 'Método no permitido.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$filtroEstrellas = isset($_GET['estrellas']) ? (int)$_GET['estrellas'] : 0;
if ($filtroEstrellas < 1 || $filtroEstrellas > 5) {
    $filtroEstrellas = 0;
}

$sqlResumen = "SELECT COUNT(*) AS total, ROUND(AVG(estrellas), 2) AS promedio FROM valoraciones";
$stmtResumen = $conexion->query($sqlResumen);
$resumen = $stmtResumen->fetch(PDO::FETCH_ASSOC) ?: ['total' => 0, 'promedio' => 0];

$sqlValoraciones = "
    SELECT v.id_valoracion, v.estrellas, v.comentario, v.fecha, u.nombre
    FROM valoraciones v
    INNER JOIN usuarios u ON v.id_usuario = u.id_usuario
";

$params = [];
if ($filtroEstrellas > 0) {
    $sqlValoraciones .= " WHERE v.estrellas = :estrellas ";
    $params[':estrellas'] = $filtroEstrellas;
}

$sqlValoraciones .= " ORDER BY v.fecha DESC";

$stmtValoraciones = $conexion->prepare($sqlValoraciones);
foreach ($params as $param => $value) {
    $stmtValoraciones->bindValue($param, $value, PDO::PARAM_INT);
}
$stmtValoraciones->execute();
$valoraciones = $stmtValoraciones->fetchAll(PDO::FETCH_ASSOC);

$lista = array_map(function ($valoracion) {
    $comentario = trim((string)($valoracion['comentario'] ?? ''));

    return [
        'id_valoracion' => (int)$valoracion['id_valoracion'],
        'nombre' => (string)$valoracion['nombre'],
        'estrellas' => (int)$valoracion['estrellas'],
        'comentario' => $comentario,
        'fecha' => (string)$valoracion['fecha']
    ];
}, $valoraciones);

echo json_encode([
    'ok' => true,
    'filtro_estrellas' => $filtroEstrellas,
    'resumen' => [
        'total' => (int)($resumen['total'] ?? 0),
        'promedio' => (float)($resumen['promedio'] ?? 0)
    ],
    'valoraciones' => $lista
], JSON_UNESCAPED_UNICODE);
