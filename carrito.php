<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ========== VERIFICACIÓN: Usuario debe estar loqueado para ver el carrito ==========
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}

$page_title = "Carrito";
require_once "config/conexion.php";
require_once "includes/header.php";

$carrito = $_SESSION['carrito'] ?? [];
$total = 0;

// Obtener nombres de tallas para mostrar en el carrito
$tallasNombres = [];
if (!empty($carrito)) {
    $idsTallas = [];
    foreach ($carrito as $item) {
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
?>

<main>
    <section class="carrito-section">
        <h2>Tu carrito</h2>

        <?php if (isset($_GET['carrito_msg']) && $_GET['carrito_msg'] === 'paid'): ?>
            <div class="success-message">Pago realizado correctamente.</div>
        <?php elseif (isset($_GET['carrito_msg']) && $_GET['carrito_msg'] === 'added'): ?>
            <div class="success-message">Producto añadido al carrito.</div>
        <?php endif; ?>

        <?php if (empty($carrito)): ?>
            <p class="carrito-vacio">Tu carrito está vacío.</p>
            <a href="tienda.php" class="btn-ver">Volver a la tienda</a>
        <?php else: ?>
            <div class="carrito-lista">
                <?php foreach ($carrito as $item): ?>
                    <?php $subtotal = $item['precio'] * $item['cantidad']; ?>
                    <?php $total += $subtotal; ?>
                    <article class="carrito-item">
                        <div class="carrito-info">
                            <h3><?php echo htmlspecialchars($item['nombre']); ?></h3>
                            <?php if (!empty($item['imagen'])): ?>
                                <img src="<?php echo htmlspecialchars($item['imagen']); ?>" alt="<?php echo htmlspecialchars($item['nombre']); ?>" class="carrito-img" style="width: 80px; height: 80px; object-fit: cover; border-radius: 4px; margin-bottom: 8px;">
                            <?php endif; ?>
                            <p><?php echo number_format((float)$item['precio'], 2, ',', '.'); ?> € unidad</p>
                            <?php if (!empty($item['id_talla'])): ?>
                                <?php 
                                $nombreTalla = $tallasNombres[$item['id_talla']] ?? 'ID: ' . $item['id_talla'];
                                ?>
                                <p><strong>Talla:</strong> <?php echo htmlspecialchars($nombreTalla); ?></p>
                            <?php endif; ?>
                            <p><strong>Subtotal:</strong> <?php echo number_format((float)$subtotal, 2, ',', '.'); ?> €</p>
                        </div>

                        <div class="carrito-acciones">
                            <form method="POST" action="php/actualizar_cantidad.php" class="cantidad-form" style="display:inline;">
                                <input type="hidden" name="id_producto" value="<?php echo (int)$item['id_producto']; ?>">
                                <input type="hidden" name="id_talla" value="<?php echo !empty($item['id_talla']) ? (int)$item['id_talla'] : 0; ?>">
                                <input type="hidden" name="delta" value="-1">
                                <input type="hidden" name="redirect" value="../carrito.php">
                                <button type="submit" class="cantidad-btn">-</button>
                            </form>

                            <span class="cantidad-numero"><?php echo (int)$item['cantidad']; ?></span>

                            <form method="POST" action="php/actualizar_cantidad.php" class="cantidad-form" style="display:inline;">
                                <input type="hidden" name="id_producto" value="<?php echo (int)$item['id_producto']; ?>">
                                <input type="hidden" name="id_talla" value="<?php echo !empty($item['id_talla']) ? (int)$item['id_talla'] : 0; ?>">
                                <input type="hidden" name="delta" value="1">
                                <input type="hidden" name="redirect" value="../carrito.php">
                                <button type="submit" class="cantidad-btn">+</button>
                            </form>

                            <form method="POST" action="php/eliminar_producto.php" class="eliminar-form" style="display:inline;">
                                <input type="hidden" name="id_producto" value="<?php echo (int)$item['id_producto']; ?>">
                                <input type="hidden" name="id_talla" value="<?php echo !empty($item['id_talla']) ? (int)$item['id_talla'] : 0; ?>">
                                <input type="hidden" name="redirect" value="../carrito.php">
                                <button type="submit" class="btn-eliminar">Eliminar</button>
                            </form>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>

            <div class="carrito-resumen">
                <p><strong>Total:</strong> <?php echo number_format((float)$total, 2, ',', '.'); ?> €</p>
                <div style="display: flex; gap: 10px; margin-top: 20px; flex-direction: column;">
                    <a href="checkout.php" style="background: linear-gradient(135deg, var(--veridi-gold-dark) 0%, var(--veridi-gold) 100%); 
                        color: var(--veridi-black); padding: 14px 24px; border-radius: 6px; font-weight: 700; 
                        font-size: 15px; text-decoration: none; text-transform: uppercase; letter-spacing: 1px; 
                        display: flex; align-items: center; justify-content: center; gap: 8px; 
                        transition: all 0.3s ease; box-shadow: 0 6px 20px rgba(212, 175, 55, 0.3);">
                        💳 Ir a Pagar
                    </a>
                    <form method="POST" action="php/vaciar_carrito.php">
                        <input type="hidden" name="redirect" value="../carrito.php">
                        <button type="submit" class="btn-eliminar" style="width: 100%; padding: 12px 24px; border: 2px solid var(--veridi-gold); background: transparent; color: var(--veridi-gold); font-weight: 700; border-radius: 6px; cursor: pointer; transition: all 0.3s ease;">Vaciar carrito</button>
                    </form>
                </div>
            </div>
        <?php endif; ?>
    </section>
</main>

<?php require_once "includes/footer.php"; ?>
