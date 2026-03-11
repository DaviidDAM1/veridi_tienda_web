<?php
require_once "../config/conexion.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../index.php?auth_open=1&auth_tab=login&auth_error=requiere_login");
    exit;
}

$idUsuario = (int)$_SESSION['usuario_id'];
$nombre = trim($_POST['nombre'] ?? '');
$redirect = trim($_POST['redirect'] ?? 'index.php');

if (!preg_match('/^[a-zA-Z0-9._-]+\.php$/', $redirect)) {
    $redirect = 'index.php';
}

function redirectWithStatus(string $redirect, string $status): void {
    $sep = str_contains($redirect, '?') ? '&' : '?';
    header('Location: ../' . $redirect . $sep . 'profile_msg=' . urlencode($status) . '&profile_open=1');
    exit;
}

if ($nombre === '' || mb_strlen($nombre) > 100) {
    redirectWithStatus($redirect, 'nombre_invalido');
}

try {
    $conexion->exec("ALTER TABLE usuarios ADD COLUMN foto_perfil VARCHAR(255) NULL AFTER password");
} catch (Exception $e) {
}

$stmtActual = $conexion->prepare("SELECT foto_perfil FROM usuarios WHERE id_usuario = :id_usuario LIMIT 1");
$stmtActual->bindParam(':id_usuario', $idUsuario, PDO::PARAM_INT);
$stmtActual->execute();
$usuarioActual = $stmtActual->fetch(PDO::FETCH_ASSOC) ?: [];
$fotoActual = trim((string)($usuarioActual['foto_perfil'] ?? ''));
$nuevaRutaFoto = $fotoActual;

if (isset($_FILES['foto_perfil']) && is_array($_FILES['foto_perfil']) && ($_FILES['foto_perfil']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
    $archivo = $_FILES['foto_perfil'];

    if (($archivo['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        redirectWithStatus($redirect, 'foto_error');
    }

    $tamanoMaximo = 5 * 1024 * 1024;
    if (($archivo['size'] ?? 0) > $tamanoMaximo) {
        redirectWithStatus($redirect, 'foto_peso');
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
        redirectWithStatus($redirect, 'foto_tipo');
    }

    $extension = $permitidos[$mime];
    $nombreArchivo = 'usuario_' . $idUsuario . '_' . time() . '.' . $extension;
    $rutaDestinoAbs = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'img' . DIRECTORY_SEPARATOR . 'perfiles' . DIRECTORY_SEPARATOR . $nombreArchivo;
    $rutaDestinoRel = 'img/perfiles/' . $nombreArchivo;

    if (!move_uploaded_file($tmp, $rutaDestinoAbs)) {
        redirectWithStatus($redirect, 'foto_error');
    }

    $nuevaRutaFoto = $rutaDestinoRel;

    if ($fotoActual !== '' && str_starts_with($fotoActual, 'img/perfiles/')) {
        $rutaAnteriorAbs = dirname(__DIR__) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $fotoActual);
        if (is_file($rutaAnteriorAbs)) {
            @unlink($rutaAnteriorAbs);
        }
    }
}

$stmtUpdate = $conexion->prepare("UPDATE usuarios SET nombre = :nombre, foto_perfil = :foto_perfil WHERE id_usuario = :id_usuario");
$stmtUpdate->bindParam(':nombre', $nombre);
$stmtUpdate->bindParam(':foto_perfil', $nuevaRutaFoto);
$stmtUpdate->bindParam(':id_usuario', $idUsuario, PDO::PARAM_INT);
$stmtUpdate->execute();

$_SESSION['usuario_nombre'] = $nombre;

redirectWithStatus($redirect, 'ok');
