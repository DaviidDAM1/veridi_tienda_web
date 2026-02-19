<?php
$page_title = "Carrito";
require_once "includes/header.php";

$carrito = $_SESSION['carrito'] ?? [];
$total = 0;
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
                            <p><?php echo number_format((float)$item['precio'], 2, ',', '.'); ?> € unidad</p>
                            <p><strong>Subtotal:</strong> <?php echo number_format((float)$subtotal, 2, ',', '.'); ?> €</p>
                        </div>

                        <div class="carrito-acciones">
                            <form method="POST" action="php/carrito.php" class="cantidad-form">
                                <input type="hidden" name="action" value="delta">
                                <input type="hidden" name="id_producto" value="<?php echo (int)$item['id_producto']; ?>">
                                <input type="hidden" name="delta" value="-1">
                                <input type="hidden" name="redirect" value="../carrito.php">
                                <button type="submit" class="cantidad-btn">-</button>
                            </form>

                            <span class="cantidad-numero"><?php echo (int)$item['cantidad']; ?></span>

                            <form method="POST" action="php/carrito.php" class="cantidad-form">
                                <input type="hidden" name="action" value="delta">
                                <input type="hidden" name="id_producto" value="<?php echo (int)$item['id_producto']; ?>">
                                <input type="hidden" name="delta" value="1">
                                <input type="hidden" name="redirect" value="../carrito.php">
                                <button type="submit" class="cantidad-btn">+</button>
                            </form>

                            <form method="POST" action="php/carrito.php" class="eliminar-form">
                                <input type="hidden" name="action" value="remove">
                                <input type="hidden" name="id_producto" value="<?php echo (int)$item['id_producto']; ?>">
                                <input type="hidden" name="redirect" value="../carrito.php">
                                <button type="submit" class="btn-eliminar">Eliminar</button>
                            </form>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>

            <div class="carrito-resumen">
                <p><strong>Total:</strong> <?php echo number_format((float)$total, 2, ',', '.'); ?> €</p>
                <form method="POST" action="php/carrito.php">
                    <input type="hidden" name="action" value="pay">
                    <input type="hidden" name="redirect" value="../carrito.php">
                    <button type="submit" class="btn-pagar">Pagar</button>
                </form>
            </div>
        <?php endif; ?>
    </section>
</main>

<?php require_once "includes/footer.php"; ?>
