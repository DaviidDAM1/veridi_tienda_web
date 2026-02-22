<?php
require_once "../config/conexion.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Validar que hay carrito
if (empty($_SESSION['carrito'])) {
    header("Location: ../tienda.php");
    exit;
}

// Obtener datos del formulario
$email = isset($_POST['email']) ? trim($_POST['email']) : '';
$password = isset($_POST['password']) ? $_POST['password'] : '';
$calle = isset($_POST['calle']) ? trim($_POST['calle']) : '';
$ciudad = isset($_POST['ciudad']) ? trim($_POST['ciudad']) : '';
$codigo_postal = isset($_POST['codigo_postal']) ? trim($_POST['codigo_postal']) : '';
$pais = isset($_POST['pais']) ? trim($_POST['pais']) : '';

// Armar dirección completa
$direccion = "$calle, $codigo_postal $ciudad, $pais";

// Validar campos requeridos
if (empty($email) || empty($password) || empty($calle) || empty($ciudad) || empty($codigo_postal) || empty($pais)) {
    $_SESSION['error_checkout'] = 'Todos los campos de dirección son requeridos';
    header("Location: ../checkout.php");
    exit;
}

// Validar email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['error_checkout'] = 'Email inválido';
    header("Location: ../checkout.php");
    exit;
}

// Buscar usuario por email
$stmt = $conexion->prepare("SELECT id_usuario, password FROM usuarios WHERE email = :email");
$stmt->bindParam(':email', $email);
$stmt->execute();
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$usuario || !password_verify($password, $usuario['password'])) {
    $_SESSION['error_checkout'] = 'Email o contraseña incorrectos';
    header("Location: ../checkout.php");
    exit;
}

$id_usuario = $usuario['id_usuario'];

// Calcular total
$total = 0;
foreach ($_SESSION['carrito'] as $item) {
    $total += $item['precio'] * $item['cantidad'];
}

try {
    // Iniciar transacción
    $conexion->beginTransaction();

    // Crear pedido
    $stmt = $conexion->prepare("
        INSERT INTO pedidos (id_usuario, direccion, total, estado)
        VALUES (:id_usuario, :direccion, :total, 'pagado')
    ");
    $stmt->bindParam(':id_usuario', $id_usuario, PDO::PARAM_INT);
    $stmt->bindParam(':direccion', $direccion);
    $stmt->bindParam(':total', $total);
    $stmt->execute();

    $id_pedido = $conexion->lastInsertId();

    // Guardar detalles del pedido
    foreach ($_SESSION['carrito'] as $key => $item) {
        $stmt = $conexion->prepare("
            INSERT INTO pedido_detalle (id_pedido, id_producto, id_talla, cantidad, precio_unitario)
            VALUES (:id_pedido, :id_producto, :id_talla, :cantidad, :precio_unitario)
        ");
        
        $stmt->bindParam(':id_pedido', $id_pedido, PDO::PARAM_INT);
        $stmt->bindParam(':id_producto', $item['id_producto'], PDO::PARAM_INT);
        $stmt->bindParam(':id_talla', $item['id_talla'], PDO::PARAM_INT);
        $stmt->bindParam(':cantidad', $item['cantidad'], PDO::PARAM_INT);
        $stmt->bindParam(':precio_unitario', $item['precio']);
        $stmt->execute();
    }

    // Confirmar transacción
    $conexion->commit();

    // Limpiar carrito
    unset($_SESSION['carrito']);

    // Redirigir a confirmación
    header("Location: ../confirmacion_pedido.php?id=$id_pedido");
    exit;

} catch (Exception $e) {
    // Deshacer cambios en caso de error
    $conexion->rollBack();
    $_SESSION['error_checkout'] = 'Error al procesar el pago: ' . $e->getMessage();
    header("Location: ../checkout.php");
    exit;
}
?>
