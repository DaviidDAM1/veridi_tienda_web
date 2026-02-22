<?php
require_once "config/conexion.php";
require_once "config/imagenes.php";
$page_title = "Inicio";
require_once "includes/header.php";
?>

<main>
    <!-- Selector de Tema -->
    <div class="theme-selector">
        <span class="theme-label">Personaliza tu experiencia:</span>
        <div class="theme-buttons">
            <button class="theme-btn" id="theme-light" title="Modo Claro" aria-label="Cambiar a tema claro">☀️</button>
            <button class="theme-btn" id="theme-dark" title="Modo Oscuro" aria-label="Cambiar a tema oscuro">🌙</button>
        </div>
    </div>

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
                $imagenMasVendido = obtenerImagenProducto($masVendido['id_producto']);
                $imagenMasVendido .= '?v=' . time();
            ?>
            <div class="card">
                <img src="<?php echo htmlspecialchars($imagenMasVendido); ?>" alt="Más vendido" class="producto-img">
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
                $imagenOferta = obtenerImagenProducto($oferta['id_producto']);
                $imagenOferta .= '?v=' . time();
            ?>
            <div class="card">
                <img src="<?php echo htmlspecialchars($imagenOferta); ?>" alt="Oferta" class="producto-img">
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
                $imagenNuevo = obtenerImagenProducto($nuevo['id_producto']);
                $imagenNuevo .= '?v=' . time();
            ?>
            <div class="card">
                <img src="<?php echo htmlspecialchars($imagenNuevo); ?>" alt="Nuevo" class="producto-img">
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

<script>
// Sistema de Temas (Light/Dark)
document.addEventListener('DOMContentLoaded', function() {
    const themeLight = document.getElementById('theme-light');
    const themeDark = document.getElementById('theme-dark');
    
    // Obtener tema guardado o usar 'dark' por defecto
    const savedTheme = localStorage.getItem('veridi-theme') || 'dark';
    
    // Aplicar tema guardado
    document.documentElement.setAttribute('data-theme', savedTheme);
    updateThemeButtons(savedTheme);
    
    // Event listeners para cambiar tema
    themeLight.addEventListener('click', function() {
        setTheme('light');
    });
    
    themeDark.addEventListener('click', function() {
        setTheme('dark');
    });
    
    function setTheme(theme) {
        document.documentElement.setAttribute('data-theme', theme);
        localStorage.setItem('veridi-theme', theme);
        updateThemeButtons(theme);
    }
    
    function updateThemeButtons(theme) {
        themeLight.classList.remove('active');
        themeDark.classList.remove('active');
        
        if (theme === 'light') {
            themeLight.classList.add('active');
        } else {
            themeDark.classList.add('active');
        }
    }
});
</script>

<?php require_once "includes/footer.php"; ?>
