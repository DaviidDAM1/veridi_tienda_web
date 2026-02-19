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
        // Producto más vendido
        $stmt = $conexion->query("SELECT * FROM productos WHERE mas_vendido = TRUE LIMIT 1");
        $masVendido = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($masVendido):
        ?>
            <div class="card">
                <img src="img/camisetaNegraVeridi.png" alt="Más vendido" class="producto-img">
                <h3>🏆 Más vendido</h3>
                <p><?php echo $masVendido['nombre']; ?></p>
                <p><?php echo $masVendido['descripcion']; ?></p>
                <p><?php echo $masVendido['precio']; ?> €</p>
            </div>
        <?php endif; ?>

        <?php
        // Producto en oferta
        $stmt = $conexion->query("SELECT * FROM productos WHERE en_oferta = TRUE LIMIT 1");
        $oferta = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($oferta):
        ?>
            <div class="card">
                <img src="img/pantalonVeridiNegro.png" alt="Oferta" class="producto-img">
                <h3>🔥 Oferta</h3>
                <p><?php echo $oferta['nombre']; ?></p>
                <p><?php echo $oferta['descripcion']; ?></p>
                <p><?php echo $oferta['precio']; ?> €</p>
            </div>
        <?php endif; ?>

        <?php
        // Producto nuevo
        $stmt = $conexion->query("SELECT * FROM productos WHERE es_nuevo = TRUE LIMIT 1");
        $nuevo = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($nuevo):
        ?>
            <div class="card">
                <img src="img/abrigoVeridiBlanco.png" alt="Nuevo" class="producto-img">
                <h3>🆕 Nuevo</h3>
                <p><?php echo $nuevo['nombre']; ?></p>
                <p><?php echo $nuevo['descripcion']; ?></p>
                <p><?php echo $nuevo['precio']; ?> €</p>
            </div>
        <?php endif; ?>

    </div>
</main>

<?php require_once "includes/footer.php"; ?>
