<?php
require_once "config/conexion.php";
$page_title = "Inicio";
require_once "includes/header.php";
?>

<main>
    <div class="hero-section">
        <h2>Bienvenido a Veridi 👕</h2>
        <p>Descubre nuestra colección exclusiva de ropa de calidad</p>
        <a href="tienda.php" class="btn-productos">Ver Tienda</a>
    </div>

    <div class="cards">

        <?php
        // Obtener 3 productos para mostrar
        $stmt = $conexion->query("SELECT * FROM productos LIMIT 3");
        $productosDestacados = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (!empty($productosDestacados)):
            $masVendido = $productosDestacados[0] ?? null;
            $oferta = $productosDestacados[1] ?? null;
            $nuevo = $productosDestacados[2] ?? null;

            // Producto 1: Bestseller
            if ($masVendido):
            ?>
            <div class="card">
                <img src="img/camisetaNegraVeridi.png" alt="Más vendido" class="producto-img">
                <h3>🏆 Más vendido</h3>
                <p><?php echo htmlspecialchars($masVendido['nombre']); ?></p>
                <p><?php echo htmlspecialchars($masVendido['descripcion'] ?? ''); ?></p>
                <p><?php echo number_format($masVendido['precio'], 2, ',', '.'); ?> €</p>
                <a href="producto-detalle.php?id=<?php echo $masVendido['id_producto']; ?>" class="btn-ver">Ver producto</a>
            </div>
            <?php endif; ?>

            <?php
            // Producto 2: Oferta
            if ($oferta):
            ?>
            <div class="card">
                <img src="img/pantalonVeridiNegro.png" alt="Oferta" class="producto-img">
                <h3>🔥 Oferta</h3>
                <p><?php echo htmlspecialchars($oferta['nombre']); ?></p>
                <p><?php echo htmlspecialchars($oferta['descripcion'] ?? ''); ?></p>
                <p><?php echo number_format($oferta['precio'], 2, ',', '.'); ?> €</p>
                <a href="producto-detalle.php?id=<?php echo $oferta['id_producto']; ?>" class="btn-ver">Ver producto</a>
            </div>
            <?php endif; ?>

            <?php
            // Producto 3: Nuevo
            if ($nuevo):
            ?>
            <div class="card">
                <img src="img/abrigoVeridiBlanco.png" alt="Nuevo" class="producto-img">
                <h3>🆕 Nuevo</h3>
                <p><?php echo htmlspecialchars($nuevo['nombre']); ?></p>
                <p><?php echo htmlspecialchars($nuevo['descripcion'] ?? ''); ?></p>
                <p><?php echo number_format($nuevo['precio'], 2, ',', '.'); ?> €</p>
                <a href="producto-detalle.php?id=<?php echo $nuevo['id_producto']; ?>" class="btn-ver">Ver producto</a>
            </div>
            <?php endif; ?>
        <?php endif; ?>

    </div>
</main>

<?php require_once "includes/footer.php"; ?><?php require_once "includes/footer.php"; ?>
