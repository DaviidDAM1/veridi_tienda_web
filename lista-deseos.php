<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ========== VERIFICACIÓN: Usuario debe estar loqueado para ver favoritos ==========
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}

$page_title = "Lista de deseos";
require_once "includes/header.php";

$deseos = $_SESSION['deseos'] ?? [];
?>

<main>
    <section class="carrito-section">
        <h2>Tus productos favoritos</h2>

        <?php if (isset($_GET['deseos_msg']) && $_GET['deseos_msg'] === 'added'): ?>
            <div class="success-message">Producto añadido a tus favoritos.</div>
        <?php elseif (isset($_GET['deseos_msg']) && $_GET['deseos_msg'] === 'moved'): ?>
            <div class="success-message">Producto movido al carrito.</div>
        <?php endif; ?>

        <?php if (empty($deseos)): ?>
            <p class="carrito-vacio">No tienes productos guardados en tus favoritos.</p>
            <a href="tienda.php" class="btn-ver">Explorar tienda</a>
        <?php else: ?>
            <div class="carrito-lista">
                <?php foreach ($deseos as $item): ?>
                    <article class="carrito-item">
                        <div class="carrito-info">
                            <?php if (!empty($item['imagen'])): ?>
                                <img src="<?php echo htmlspecialchars($item['imagen']); ?>" alt="<?php echo htmlspecialchars($item['nombre']); ?>" class="wishlist-img">
                            <?php endif; ?>
                            <h3><?php echo htmlspecialchars($item['nombre']); ?></h3>
                            <p><?php echo number_format((float)$item['precio'], 2, ',', '.'); ?> €</p>
                        </div>

                        <div class="carrito-acciones">
                            <form method="POST" action="php/deseos.php" class="eliminar-form">
                                <input type="hidden" name="action" value="move_to_cart">
                                <input type="hidden" name="id_producto" value="<?php echo (int)$item['id_producto']; ?>">
                                <input type="hidden" name="redirect" value="lista-deseos.php">
                                <button type="submit" class="btn-pagar">Mover al carrito</button>
                            </form>

                            <form method="POST" action="php/deseos.php" class="eliminar-form">
                                <input type="hidden" name="action" value="remove">
                                <input type="hidden" name="id_producto" value="<?php echo (int)$item['id_producto']; ?>">
                                <input type="hidden" name="redirect" value="lista-deseos.php">
                                <button type="submit" class="btn-eliminar">Eliminar</button>
                            </form>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</main>

<?php require_once "includes/footer.php"; ?>
