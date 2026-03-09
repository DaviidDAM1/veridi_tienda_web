<?php
require_once "config/conexion.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ========== VERIFICACIÓN: Usuario debe estar loqueado ==========
if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.php?auth_open=1&auth_tab=login&auth_error=requiere_login");
    exit;
}

$page_title = "Checkout";
require_once "includes/header.php";

// Verificar si hay carrito
if (empty($_SESSION['carrito'])) {
    header("Location: tienda.php");
    exit;
}

// Calcular total del carrito y obtener nombres de tallas
$total = 0;
$cartItems = [];
$tallasNombres = [];

// Primero, obtener todos los nombres de tallas
if (!empty($_SESSION['carrito'])) {
    $idsTallas = [];
    foreach ($_SESSION['carrito'] as $item) {
        if (!empty($item['id_talla'])) {
            $idsTallas[] = (int)$item['id_talla'];
        }
    }
    
    if (!empty($idsTallas)) {
        $placeholders = implode(',', array_fill(0, count($idsTallas), '?'));
        $stmt = $conexion->prepare("SELECT id_talla, nombre FROM tallas WHERE id_talla IN ($placeholders)");
        $stmt->execute($idsTallas);
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $tallasNombres[$row['id_talla']] = $row['nombre'];
        }
    }
}

// Armar items del carrito con nombres de tallas
foreach ($_SESSION['carrito'] as $key => $item) {
    $item['talla'] = $tallasNombres[$item['id_talla']] ?? 'N/A';
    $total += $item['precio'] * $item['cantidad'];
    $cartItems[] = $item;
}

$error = '';
$email = '';
$nombre = '';

// Si está logueado, llenar datos del usuario
if (isset($_SESSION['usuario_id'])) {
    $stmt = $conexion->prepare("SELECT nombre, email FROM usuarios WHERE id_usuario = :id");
    $stmt->bindParam(':id', $_SESSION['usuario_id'], PDO::PARAM_INT);
    $stmt->execute();
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($usuario) {
        $email = $usuario['email'];
        $nombre = $usuario['nombre'];
    }
}
?>

<main>
    <div class="producto-detalle-container">
        <h1 style="color: var(--veridi-gold); margin-bottom: 30px;">Finalizar Compra</h1>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 50px; margin-bottom: 50px;">
            <!-- FORMULARIO CHECKOUT -->
            <div>
                <h2 style="color: var(--veridi-gold); margin-bottom: 20px; font-size: 24px;">Tus Datos</h2>

                <?php if (!empty($error)): ?>
                    <div style="background: rgba(211, 47, 47, 0.1); border: 1px solid #d32f2f; color: #d32f2f; padding: 15px; border-radius: 6px; margin-bottom: 20px;">
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="php/procesar_pago.php" style="display: flex; flex-direction: column; gap: 20px;">
                    <!-- Email -->
                    <div>
                        <label style="display: block; color: var(--veridi-gold); margin-bottom: 8px; font-weight: 600;">Email:</label>
                        <input type="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required
                            style="width: 100%; padding: 12px; border: 2px solid var(--veridi-gold); border-radius: 6px; background: var(--veridi-dark); color: var(--veridi-text); font-size: 14px;">
                    </div>

                    <!-- Contraseña -->
                    <div>
                        <label style="display: block; color: var(--veridi-gold); margin-bottom: 8px; font-weight: 600;">Contraseña:</label>
                        <input type="password" name="password" required
                            style="width: 100%; padding: 12px; border: 2px solid var(--veridi-gold); border-radius: 6px; background: var(--veridi-dark); color: var(--veridi-text); font-size: 14px;">
                        <small style="color: var(--veridi-text-muted); display: block; margin-top: 5px;">Se requiere tu contraseña de cuenta para confirmar la compra</small>
                    </div>

                    <!-- Dirección: Calle -->
                    <div>
                        <label style="display: block; color: var(--veridi-gold); margin-bottom: 8px; font-weight: 600;">Calle y Número:</label>
                        <input type="text" name="calle" placeholder="Ej: Calle Principal 123" required
                            style="width: 100%; padding: 12px; border: 2px solid var(--veridi-gold); border-radius: 6px; background: var(--veridi-dark); color: var(--veridi-text); font-size: 14px;">
                    </div>

                    <!-- Dirección: Ciudad -->
                    <div>
                        <label style="display: block; color: var(--veridi-gold); margin-bottom: 8px; font-weight: 600;">Ciudad:</label>
                        <input type="text" name="ciudad" placeholder="Ej: Madrid" required
                            style="width: 100%; padding: 12px; border: 2px solid var(--veridi-gold); border-radius: 6px; background: var(--veridi-dark); color: var(--veridi-text); font-size: 14px;">
                    </div>

                    <!-- Dirección: Código Postal -->
                    <div>
                        <label style="display: block; color: var(--veridi-gold); margin-bottom: 8px; font-weight: 600;">Código Postal:</label>
                        <input type="text" name="codigo_postal" placeholder="Ej: 28001" required
                            style="width: 100%; padding: 12px; border: 2px solid var(--veridi-gold); border-radius: 6px; background: var(--veridi-dark); color: var(--veridi-text); font-size: 14px;">
                    </div>

                    <!-- Dirección: País -->
                    <div>
                        <label style="display: block; color: var(--veridi-gold); margin-bottom: 8px; font-weight: 600;">País:</label>
                        <input type="text" name="pais" placeholder="Ej: España" required
                            style="width: 100%; padding: 12px; border: 2px solid var(--veridi-gold); border-radius: 6px; background: var(--veridi-dark); color: var(--veridi-text); font-size: 14px;">
                    </div>

                    <!-- Botón Pagar -->
                    <button type="submit" style="background: linear-gradient(135deg, var(--veridi-gold-dark) 0%, var(--veridi-gold) 100%); color: var(--veridi-black); padding: 16px 30px; border: none; border-radius: 6px; font-weight: 700; font-size: 16px; cursor: pointer; text-transform: uppercase; letter-spacing: 1px; transition: all 0.3s ease; margin-top: 10px;">
                        💳 Procesar Pago
                    </button>

                    <a href="carrito.php" style="text-align: center; color: var(--veridi-gold); text-decoration: none; padding: 10px; border: 2px solid var(--veridi-gold); border-radius: 6px; transition: all 0.3s ease;">
                        Volver al Carrito
                    </a>
                </form>
            </div>

            <!-- RESUMEN DEL CARRITO -->
            <div>
                <h2 style="color: var(--veridi-gold); margin-bottom: 20px; font-size: 24px;">Resumen de Compra</h2>
                
                <div style="background: var(--veridi-dark); border: 2px solid var(--veridi-gold); border-radius: 8px; padding: 20px;">
                    <?php foreach ($cartItems as $item): ?>
                        <div style="display: flex; justify-content: space-between; align-items: center; padding: 12px 0; border-bottom: 1px solid rgba(212, 175, 55, 0.2);">
                            <div>
                                <p style="color: var(--veridi-text); margin: 0 0 5px 0; font-weight: 600;">
                                    <?php echo htmlspecialchars($item['nombre']); ?>
                                </p>
                                <p style="color: var(--veridi-text-muted); margin: 0; font-size: 14px;">
                                    Talla: <?php echo htmlspecialchars($item['talla']); ?> | Cantidad: <?php echo $item['cantidad']; ?>
                                </p>
                            </div>
                            <p style="color: var(--veridi-gold-light); font-weight: 700;">
                                €<?php echo number_format($item['precio'] * $item['cantidad'], 2, ',', '.'); ?>
                            </p>
                        </div>
                    <?php endforeach; ?>

                    <div style="padding: 20px 0; border-top: 2px solid var(--veridi-gold); margin-top: 10px; display: flex; justify-content: space-between; align-items: center;">
                        <p style="color: var(--veridi-gold); font-weight: 700; font-size: 18px; margin: 0;">Total:</p>
                        <p style="color: var(--veridi-gold); font-weight: 700; font-size: 24px; margin: 0;">
                            €<?php echo number_format($total, 2, ',', '.'); ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<?php require_once "includes/footer.php"; ?>
