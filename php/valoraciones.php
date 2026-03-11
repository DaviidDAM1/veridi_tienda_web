<?php
require_once "../config/conexion.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../index.php?auth_open=1&auth_tab=login&auth_error=requiere_login");
    exit;
}

function redirectWithMessage(int $idPedido, string $message): void
{
    header("Location: ../confirmacion_pedido.php?id=" . $idPedido . "&valoracion_msg=" . $message);
    exit;
}

$idUsuario = (int)$_SESSION['usuario_id'];
$idPedido = isset($_POST['id_pedido']) ? (int)$_POST['id_pedido'] : 0;
$estrellas = isset($_POST['estrellas']) ? (int)$_POST['estrellas'] : 0;
$comentario = trim($_POST['comentario'] ?? '');

if ($idPedido <= 0 || $estrellas < 1 || $estrellas > 5) {
    redirectWithMessage($idPedido > 0 ? $idPedido : 0, 'invalid');
}

if (mb_strlen($comentario) > 500) {
    $comentario = mb_substr($comentario, 0, 500);
}

try {
    $conexion->exec("CREATE TABLE IF NOT EXISTS valoraciones (
        id_valoracion INT AUTO_INCREMENT PRIMARY KEY,
        id_usuario INT NOT NULL,
        id_pedido INT NOT NULL,
        estrellas TINYINT NOT NULL,
        comentario TEXT NULL,
        fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uk_valoracion_usuario_pedido (id_usuario, id_pedido),
        FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario) ON DELETE CASCADE,
        FOREIGN KEY (id_pedido) REFERENCES pedidos(id_pedido) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    $stmtPedido = $conexion->prepare(
        "SELECT id_pedido FROM pedidos WHERE id_pedido = :id_pedido AND id_usuario = :id_usuario LIMIT 1"
    );
    $stmtPedido->bindParam(':id_pedido', $idPedido, PDO::PARAM_INT);
    $stmtPedido->bindParam(':id_usuario', $idUsuario, PDO::PARAM_INT);
    $stmtPedido->execute();

    $pedidoValido = $stmtPedido->fetch(PDO::FETCH_ASSOC);

    if (!$pedidoValido) {
        redirectWithMessage($idPedido, 'error');
    }

    $stmtExiste = $conexion->prepare(
        "SELECT id_valoracion FROM valoraciones WHERE id_usuario = :id_usuario AND id_pedido = :id_pedido LIMIT 1"
    );
    $stmtExiste->bindParam(':id_usuario', $idUsuario, PDO::PARAM_INT);
    $stmtExiste->bindParam(':id_pedido', $idPedido, PDO::PARAM_INT);
    $stmtExiste->execute();

    $valoracionExistente = $stmtExiste->fetch(PDO::FETCH_ASSOC);

    if ($valoracionExistente) {
        $stmtUpdate = $conexion->prepare(
            "UPDATE valoraciones SET estrellas = :estrellas, comentario = :comentario, fecha = CURRENT_TIMESTAMP
             WHERE id_valoracion = :id_valoracion"
        );
        $stmtUpdate->bindParam(':estrellas', $estrellas, PDO::PARAM_INT);
        $stmtUpdate->bindParam(':comentario', $comentario);
        $stmtUpdate->bindParam(':id_valoracion', $valoracionExistente['id_valoracion'], PDO::PARAM_INT);
        $stmtUpdate->execute();
    } else {
        $stmtInsert = $conexion->prepare(
            "INSERT INTO valoraciones (id_usuario, id_pedido, estrellas, comentario)
             VALUES (:id_usuario, :id_pedido, :estrellas, :comentario)"
        );
        $stmtInsert->bindParam(':id_usuario', $idUsuario, PDO::PARAM_INT);
        $stmtInsert->bindParam(':id_pedido', $idPedido, PDO::PARAM_INT);
        $stmtInsert->bindParam(':estrellas', $estrellas, PDO::PARAM_INT);
        $stmtInsert->bindParam(':comentario', $comentario);
        $stmtInsert->execute();
    }

    redirectWithMessage($idPedido, 'saved');
} catch (Exception $e) {
    redirectWithMessage($idPedido, 'error');
}
