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

if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['ok' => false, 'requiresLogin' => true, 'message' => 'Debes iniciar sesión.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$idUsuario = (int)$_SESSION['usuario_id'];
$action = trim((string)($_POST['action'] ?? ''));

try {
    $conexion->exec("ALTER TABLE usuarios ADD COLUMN foto_perfil VARCHAR(255) NULL AFTER password");
} catch (Exception $e) {
}

$stmtActual = $conexion->prepare("SELECT nombre, foto_perfil FROM usuarios WHERE id_usuario = :id_usuario LIMIT 1");
$stmtActual->bindParam(':id_usuario', $idUsuario, PDO::PARAM_INT);
$stmtActual->execute();
$usuarioActual = $stmtActual->fetch(PDO::FETCH_ASSOC) ?: ['nombre' => '', 'foto_perfil' => ''];

if ($action === 'name') {
    $nombre = trim((string)($_POST['nombre'] ?? ''));

    if ($nombre === '' || mb_strlen($nombre) > 100) {
        echo json_encode(['ok' => false, 'message' => 'El nombre no es válido (1-100 caracteres).'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $stmtUpdate = $conexion->prepare("UPDATE usuarios SET nombre = :nombre WHERE id_usuario = :id_usuario");
    $stmtUpdate->bindParam(':nombre', $nombre);
    $stmtUpdate->bindParam(':id_usuario', $idUsuario, PDO::PARAM_INT);
    $stmtUpdate->execute();

    $_SESSION['usuario_nombre'] = $nombre;

    echo json_encode(['ok' => true, 'message' => 'Perfil actualizado correctamente.'], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($action === 'photo') {
    if (!isset($_FILES['foto_perfil']) || !is_array($_FILES['foto_perfil'])) {
        echo json_encode(['ok' => false, 'message' => 'Selecciona una imagen válida.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $archivo = $_FILES['foto_perfil'];
    if (($archivo['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        echo json_encode(['ok' => false, 'message' => 'No se pudo procesar la imagen.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $tamanoMaximo = 5 * 1024 * 1024;
    if (($archivo['size'] ?? 0) > $tamanoMaximo) {
        echo json_encode(['ok' => false, 'message' => 'La imagen supera el tamaño máximo (5MB).'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $tmp = $archivo['tmp_name'] ?? '';
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $tmp !== '' ? (string)$finfo->file($tmp) : '';

    $permitidos = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp'
    ];

    if (!isset($permitidos[$mime])) {
        echo json_encode(['ok' => false, 'message' => 'Formato de imagen no permitido (solo JPG, PNG o WEBP).'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $extension = $permitidos[$mime];
    $nombreArchivo = 'usuario_' . $idUsuario . '_' . time() . '.' . $extension;
    $directorioPerfiles = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'img' . DIRECTORY_SEPARATOR . 'perfiles';
    if (!is_dir($directorioPerfiles)) {
        @mkdir($directorioPerfiles, 0777, true);
    }
    $rutaDestinoAbs = $directorioPerfiles . DIRECTORY_SEPARATOR . $nombreArchivo;
    $rutaDestinoRel = 'img/perfiles/' . $nombreArchivo;

    if (!move_uploaded_file($tmp, $rutaDestinoAbs)) {
        echo json_encode(['ok' => false, 'message' => 'No se pudo guardar la imagen.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $fotoActual = trim((string)($usuarioActual['foto_perfil'] ?? ''));
    if ($fotoActual !== '' && str_starts_with($fotoActual, 'img/perfiles/')) {
        $rutaAnteriorAbs = dirname(__DIR__) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $fotoActual);
        if (is_file($rutaAnteriorAbs)) {
            @unlink($rutaAnteriorAbs);
        }
    }

    $stmtUpdate = $conexion->prepare("UPDATE usuarios SET foto_perfil = :foto_perfil WHERE id_usuario = :id_usuario");
    $stmtUpdate->bindParam(':foto_perfil', $rutaDestinoRel);
    $stmtUpdate->bindParam(':id_usuario', $idUsuario, PDO::PARAM_INT);
    $stmtUpdate->execute();

    echo json_encode(['ok' => true, 'message' => 'Perfil actualizado correctamente.'], JSON_UNESCAPED_UNICODE);
    exit;
}

echo json_encode(['ok' => false, 'message' => 'Acción no reconocida.'], JSON_UNESCAPED_UNICODE);
