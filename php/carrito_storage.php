<?php
function carritoEnsureTable(PDO $conexion): void
{
    static $tablaCreada = false;

    if ($tablaCreada) {
        return;
    }

    $sql = "CREATE TABLE IF NOT EXISTS carritos_usuario (
        id_usuario INT NOT NULL PRIMARY KEY,
        carrito_json LONGTEXT NOT NULL,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        CONSTRAINT fk_carrito_usuario FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

    $conexion->exec($sql);
    $tablaCreada = true;
}

function carritoLoadByUser(PDO $conexion, int $idUsuario): array
{
    if ($idUsuario <= 0) {
        return [];
    }

    carritoEnsureTable($conexion);

    $stmt = $conexion->prepare("SELECT carrito_json FROM carritos_usuario WHERE id_usuario = :id_usuario LIMIT 1");
    $stmt->bindValue(':id_usuario', $idUsuario, PDO::PARAM_INT);
    $stmt->execute();

    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row || empty($row['carrito_json'])) {
        return [];
    }

    $carrito = json_decode($row['carrito_json'], true);
    return is_array($carrito) ? $carrito : [];
}

function carritoSaveByUser(PDO $conexion, int $idUsuario, array $carrito): void
{
    if ($idUsuario <= 0) {
        return;
    }

    carritoEnsureTable($conexion);

    $carritoJson = json_encode($carrito, JSON_UNESCAPED_UNICODE);
    if ($carritoJson === false) {
        $carritoJson = '[]';
    }

    $sql = "INSERT INTO carritos_usuario (id_usuario, carrito_json)
            VALUES (:id_usuario, :carrito_json)
            ON DUPLICATE KEY UPDATE carrito_json = VALUES(carrito_json), updated_at = CURRENT_TIMESTAMP";

    $stmt = $conexion->prepare($sql);
    $stmt->bindValue(':id_usuario', $idUsuario, PDO::PARAM_INT);
    $stmt->bindValue(':carrito_json', $carritoJson, PDO::PARAM_STR);
    $stmt->execute();
}

function carritoMerge(array $base, array $extra): array
{
    $resultado = $base;

    foreach ($extra as $idProducto => $item) {
        $id = (int)($item['id_producto'] ?? $idProducto);
        $cantidad = (int)($item['cantidad'] ?? 0);

        if ($id <= 0 || $cantidad <= 0) {
            continue;
        }

        if (isset($resultado[$id])) {
            $resultado[$id]['cantidad'] = (int)$resultado[$id]['cantidad'] + $cantidad;
            continue;
        }

        $resultado[$id] = [
            'id_producto' => $id,
            'nombre' => (string)($item['nombre'] ?? 'Producto'),
            'precio' => (float)($item['precio'] ?? 0),
            'cantidad' => $cantidad,
            'imagen' => (string)($item['imagen'] ?? ''),
        ];
    }

    return $resultado;
}
