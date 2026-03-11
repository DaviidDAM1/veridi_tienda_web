<?php
require_once "../config/conexion.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['usuario_id']) || (($_SESSION['usuario_rol'] ?? 'cliente') !== 'admin')) {
    header("Location: ../index.php?auth_open=1&auth_tab=login&auth_error=requiere_login");
    exit;
}

try {
    $conexion->exec("ALTER TABLE productos ADD COLUMN oculto TINYINT(1) NOT NULL DEFAULT 0");
} catch (Exception $e) {
}

function redirectAdmin(string $msg): void
{
    header("Location: ../panel-admin.php?admin_msg=" . urlencode($msg));
    exit;
}

$action = $_POST['action'] ?? '';

function estiloValido(string $estilo): bool
{
    return in_array($estilo, ['casual', 'formal', 'deportivo'], true);
}

try {
    switch ($action) {
        case 'create_product':
            $nombre = trim($_POST['nombre'] ?? '');
            $descripcion = trim($_POST['descripcion'] ?? '');
            $precio = (float)($_POST['precio'] ?? 0);
            $color = trim($_POST['color'] ?? '');
            $estilo = trim($_POST['estilo'] ?? 'casual');
            $material = trim($_POST['material'] ?? '');
            $idCategoria = (int)($_POST['id_categoria'] ?? 0);
            $stockInicial = (int)($_POST['stock_inicial'] ?? 0);

            if ($nombre === '' || $precio <= 0 || $idCategoria <= 0 || !estiloValido($estilo)) {
                redirectAdmin('create_invalid');
            }

            if ($stockInicial < 0) {
                $stockInicial = 0;
            }

            $stmt = $conexion->prepare("INSERT INTO productos (nombre, descripcion, precio, color, estilo, material, id_categoria, oculto) VALUES (:nombre, :descripcion, :precio, :color, :estilo, :material, :id_categoria, 0)");
            $stmt->bindParam(':nombre', $nombre);
            $stmt->bindParam(':descripcion', $descripcion);
            $stmt->bindParam(':precio', $precio);
            $stmt->bindParam(':color', $color);
            $stmt->bindParam(':estilo', $estilo);
            $stmt->bindParam(':material', $material);
            $stmt->bindParam(':id_categoria', $idCategoria, PDO::PARAM_INT);
            $stmt->execute();

            $idProducto = (int)$conexion->lastInsertId();

            $stmtTallas = $conexion->query("SELECT id_talla FROM tallas");
            $tallas = $stmtTallas->fetchAll(PDO::FETCH_ASSOC);

            $stmtInsertTalla = $conexion->prepare("INSERT INTO producto_tallas (id_producto, id_talla, stock) VALUES (:id_producto, :id_talla, :stock)");
            foreach ($tallas as $talla) {
                $idTalla = (int)$talla['id_talla'];
                $stmtInsertTalla->bindParam(':id_producto', $idProducto, PDO::PARAM_INT);
                $stmtInsertTalla->bindParam(':id_talla', $idTalla, PDO::PARAM_INT);
                $stmtInsertTalla->bindParam(':stock', $stockInicial, PDO::PARAM_INT);
                $stmtInsertTalla->execute();
            }

            redirectAdmin('create_ok');
            break;

        case 'edit_product':
            $idProducto = (int)($_POST['id_producto'] ?? 0);
            $nombre = trim($_POST['nombre'] ?? '');
            $descripcion = trim($_POST['descripcion'] ?? '');
            $precio = (float)($_POST['precio'] ?? 0);
            $color = trim($_POST['color'] ?? '');
            $estilo = trim($_POST['estilo'] ?? 'casual');
            $material = trim($_POST['material'] ?? '');
            $idCategoria = (int)($_POST['id_categoria'] ?? 0);

            if ($idProducto <= 0 || $nombre === '' || $precio <= 0 || $idCategoria <= 0 || !estiloValido($estilo)) {
                redirectAdmin('edit_invalid');
            }

            $stmt = $conexion->prepare("UPDATE productos SET nombre = :nombre, descripcion = :descripcion, precio = :precio, color = :color, estilo = :estilo, material = :material, id_categoria = :id_categoria WHERE id_producto = :id_producto");
            $stmt->bindParam(':nombre', $nombre);
            $stmt->bindParam(':descripcion', $descripcion);
            $stmt->bindParam(':precio', $precio);
            $stmt->bindParam(':color', $color);
            $stmt->bindParam(':estilo', $estilo);
            $stmt->bindParam(':material', $material);
            $stmt->bindParam(':id_categoria', $idCategoria, PDO::PARAM_INT);
            $stmt->bindParam(':id_producto', $idProducto, PDO::PARAM_INT);
            $stmt->execute();

            redirectAdmin('edit_ok');
            break;

        case 'delete_product':
            $idProducto = (int)($_POST['id_producto'] ?? 0);
            if ($idProducto <= 0) {
                redirectAdmin('delete_invalid');
            }

            $stmt = $conexion->prepare("DELETE FROM productos WHERE id_producto = :id_producto");
            $stmt->bindParam(':id_producto', $idProducto, PDO::PARAM_INT);
            $stmt->execute();

            redirectAdmin('delete_ok');
            break;

        case 'toggle_hide':
            $idProducto = (int)($_POST['id_producto'] ?? 0);
            $oculto = (int)($_POST['oculto'] ?? 0) === 1 ? 1 : 0;

            if ($idProducto <= 0) {
                redirectAdmin('hide_invalid');
            }

            $stmt = $conexion->prepare("UPDATE productos SET oculto = :oculto WHERE id_producto = :id_producto");
            $stmt->bindParam(':oculto', $oculto, PDO::PARAM_INT);
            $stmt->bindParam(':id_producto', $idProducto, PDO::PARAM_INT);
            $stmt->execute();

            redirectAdmin('hide_ok');
            break;

        case 'adjust_stock':
            $idProducto = (int)($_POST['id_producto'] ?? 0);
            $idTalla = (int)($_POST['id_talla'] ?? 0);
            $delta = (int)($_POST['delta'] ?? 0);

            if ($idProducto <= 0 || $idTalla <= 0 || $delta === 0) {
                redirectAdmin('stock_invalid');
            }

            $stmtActual = $conexion->prepare("SELECT stock FROM producto_tallas WHERE id_producto = :id_producto AND id_talla = :id_talla LIMIT 1");
            $stmtActual->bindParam(':id_producto', $idProducto, PDO::PARAM_INT);
            $stmtActual->bindParam(':id_talla', $idTalla, PDO::PARAM_INT);
            $stmtActual->execute();
            $fila = $stmtActual->fetch(PDO::FETCH_ASSOC);

            if ($fila) {
                $stockNuevo = (int)$fila['stock'] + $delta;
                if ($stockNuevo < 0) {
                    $stockNuevo = 0;
                }

                $stmtUpdate = $conexion->prepare("UPDATE producto_tallas SET stock = :stock WHERE id_producto = :id_producto AND id_talla = :id_talla");
                $stmtUpdate->bindParam(':stock', $stockNuevo, PDO::PARAM_INT);
                $stmtUpdate->bindParam(':id_producto', $idProducto, PDO::PARAM_INT);
                $stmtUpdate->bindParam(':id_talla', $idTalla, PDO::PARAM_INT);
                $stmtUpdate->execute();
            } else {
                $stockNuevo = max(0, $delta);
                $stmtInsert = $conexion->prepare("INSERT INTO producto_tallas (id_producto, id_talla, stock) VALUES (:id_producto, :id_talla, :stock)");
                $stmtInsert->bindParam(':id_producto', $idProducto, PDO::PARAM_INT);
                $stmtInsert->bindParam(':id_talla', $idTalla, PDO::PARAM_INT);
                $stmtInsert->bindParam(':stock', $stockNuevo, PDO::PARAM_INT);
                $stmtInsert->execute();
            }

            redirectAdmin('stock_ok');
            break;

        default:
            redirectAdmin('action_invalid');
    }
} catch (Exception $e) {
    redirectAdmin('error');
}
