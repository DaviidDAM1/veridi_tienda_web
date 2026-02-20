<?php
require_once "config/conexion.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$page_title = "Detalle del Producto";

// Mostrar mensajes de carrito
$msgCarrito = isset($_GET['carrito_msg']) ? $_GET['carrito_msg'] : '';

require_once "includes/header.php";

// Obtener ID del producto desde GET
$idProducto = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($idProducto <= 0) {
    header("Location: tienda.php");
    exit;
}

// Obtener datos del producto
$stmt = $conexion->prepare("
    SELECT p.*, c.nombre AS categoria 
    FROM productos p 
    LEFT JOIN categorias c ON p.id_categoria = c.id_categoria 
    WHERE p.id_producto = :id
");
$stmt->bindParam(':id', $idProducto, PDO::PARAM_INT);
$stmt->execute();
$producto = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$producto) {
    header("Location: tienda.php");
    exit;
}

// Determinar qué tallas mostrar según la categoría del producto
$categoriasRopa = ['Camisetas', 'Chaquetas', 'Abrigos', 'Sudaderas', 'Pantalones', 'Vaqueros'];
$categoriasCalzado = ['Calzado'];
$categoriasAccesorios = ['Gorras', 'Calcetines', 'Accesorios'];

$tallasPermitidas = [];
if (in_array($producto['categoria'], $categoriasRopa)) {
    $tallasPermitidas = ['S', 'M', 'L', 'XL'];
} elseif (in_array($producto['categoria'], $categoriasCalzado)) {
    $tallasPermitidas = ['40', '41', '42', '43', '44', '45'];
} elseif (in_array($producto['categoria'], $categoriasAccesorios)) {
    $tallasPermitidas = ['Única'];
}

// Obtener tallas disponibles para este producto filtradas por categoría
if (!empty($tallasPermitidas)) {
    $placeholders = implode(',', array_fill(0, count($tallasPermitidas), '?'));
    $stmtTallas = $conexion->prepare("
        SELECT t.id_talla, t.nombre, pt.stock
        FROM tallas t
        INNER JOIN producto_tallas pt ON t.id_talla = pt.id_talla
        WHERE pt.id_producto = ? AND pt.stock > 0 AND t.nombre IN ($placeholders)
        ORDER BY 
            CASE 
                WHEN t.nombre = 'Única' THEN 0
                WHEN t.nombre IN ('S', 'M', 'L', 'XL') THEN 1
                ELSE 2
            END,
            FIELD(t.nombre, 'S', 'M', 'L', 'XL', '40', '41', '42', '43', '44', '45', 'Única')
    ");
    $params = array_merge([$idProducto], $tallasPermitidas);
    $stmtTallas->execute($params);
    $tallas = $stmtTallas->fetchAll(PDO::FETCH_ASSOC);
} else {
    // Si no hay categoría definida, mostrar todas
    $stmtTallas = $conexion->prepare("
        SELECT t.id_talla, t.nombre, pt.stock
        FROM tallas t
        INNER JOIN producto_tallas pt ON t.id_talla = pt.id_talla
        WHERE pt.id_producto = :id AND pt.stock > 0
        ORDER BY 
            CASE 
                WHEN t.nombre = 'Única' THEN 0
                WHEN t.nombre IN ('S', 'M', 'L', 'XL') THEN 1
                ELSE 2
            END,
            FIELD(t.nombre, 'S', 'M', 'L', 'XL', '40', '41', '42', 'Única')
    ");
    $stmtTallas->bindParam(':id', $idProducto, PDO::PARAM_INT);
    $stmtTallas->execute();
    $tallas = $stmtTallas->fetchAll(PDO::FETCH_ASSOC);
}

// Obtener productos relacionados (misma categoría)
$stmtRelacionados = $conexion->prepare("
    SELECT p.id_producto, p.nombre, p.precio, p.descripcion
    FROM productos p
    WHERE p.id_categoria = :id_categoria 
    AND p.id_producto != :id_producto
    LIMIT 4
");
$stmtRelacionados->bindParam(':id_categoria', $producto['id_categoria'], PDO::PARAM_INT);
$stmtRelacionados->bindParam(':id_producto', $idProducto, PDO::PARAM_INT);
$stmtRelacionados->execute();
$productosRelacionados = $stmtRelacionados->fetchAll(PDO::FETCH_ASSOC);

// Mapa de imágenes (tú las completarás)
$imagenesProducto = [
    1 => 'img/camisetaNegraVeridi.png',
    2 => 'img/pantalonVeridiNegro.png',
    3 => 'img/abrigoVeridiBlanco.png',
];

$imagenProducto = $imagenesProducto[$idProducto] ?? ('img/producto-' . $idProducto . '.png');

// Verificar si está en favoritos
$esFavorito = false;
if (isset($_SESSION['deseos']) && is_array($_SESSION['deseos'])) {
    foreach ($_SESSION['deseos'] as $fav) {
        if ($fav['id_producto'] == $idProducto) {
            $esFavorito = true;
            break;
        }
    }
}
?>

<?php if ($msgCarrito === 'added'): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const msg = document.createElement('div');
    msg.textContent = '✓ Producto añadido al carrito';
    msg.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 15px 24px;
        background: var(--veridi-gold);
        color: black;
        border-radius: 4px;
        font-weight: 600;
        z-index: 10000;
        animation: slideIn 0.3s ease;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
    `;
    document.body.appendChild(msg);
    setTimeout(() => {
        msg.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => msg.remove(), 300);
    }, 2500);
});
</script>
<?php endif; ?>

<main class="producto-detalle-container">
    <!-- BREADCRUMB -->
    <div class="breadcrumb">
        <a href="index.php">Inicio</a> > 
        <a href="tienda.php">Tienda</a> > 
        <a href="tienda.php?categoria=<?php echo $producto['id_categoria']; ?>"><?php echo htmlspecialchars($producto['categoria']); ?></a> > 
        <span><?php echo htmlspecialchars($producto['nombre']); ?></span>
    </div>

    <div class="detalle-grid">
        <!-- Columna izquierda: Imagen -->
        <div class="detalle-imagen">
            <img src="<?php echo htmlspecialchars($imagenProducto); ?>" alt="<?php echo htmlspecialchars($producto['nombre']); ?>" class="imagen-grande">
        </div>

        <!-- Columna derecha: Información -->
        <div class="detalle-info">
            <h1><?php echo htmlspecialchars($producto['nombre']); ?></h1>
            
            <!-- Precio -->
            <div class="detalle-precio">
                <span class="precio-grande"><?php echo number_format($producto['precio'], 2, ',', '.'); ?> €</span>
            </div>

            <!-- Descripción -->
            <div class="detalle-descripcion">
                <h3>Descripción</h3>
                <p><?php echo htmlspecialchars($producto['descripcion'] ?? 'Sin descripción'); ?></p>
            </div>

            <!-- Características -->
            <div class="detalle-caracteristicas">
                <h3>Características</h3>
                <ul>
                    <li><strong>Categoría:</strong> <?php echo htmlspecialchars($producto['categoria']); ?></li>
                    <?php if (!empty($producto['color'])): ?>
                        <li><strong>Color:</strong> <?php echo htmlspecialchars($producto['color']); ?></li>
                    <?php endif; ?>
                    <?php if (!empty($producto['material'])): ?>
                        <li><strong>Material:</strong> <?php echo htmlspecialchars($producto['material']); ?></li>
                    <?php endif; ?>
                    <?php if (!empty($producto['estilo'])): ?>
                        <li><strong>Estilo:</strong> <?php echo ucfirst(htmlspecialchars($producto['estilo'])); ?></li>
                    <?php endif; ?>
                </ul>
            </div>

            <!-- Selector de Talla -->
            <div class="detalle-tallas">
                <h3>Tallas disponibles</h3>
                <?php if (empty($tallas)): ?>
                    <p class="sin-stock">Producto agotado</p>
                <?php else: ?>
                    <div class="selector-tallas">
                        <?php foreach ($tallas as $talla): ?>
                            <label class="talla-option">
                                <input type="radio" name="id_talla" value="<?php echo $talla['id_talla']; ?>" 
                                    data-talla="<?php echo htmlspecialchars($talla['nombre']); ?>"
                                    data-stock="<?php echo $talla['stock']; ?>">
                                <span class="talla-label"><?php echo htmlspecialchars($talla['nombre']); ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                    <p class="stock-info" id="stock-info"></p>
                <?php endif; ?>
            </div>

            <!-- Botones de acción -->
            <div class="detalle-acciones">
                <?php if (!empty($tallas)): ?>
                    <form id="form-carrito" method="POST" action="php/agregar_carrito.php" style="margin-bottom: 10px;">
                        <input type="hidden" name="id_producto" value="<?php echo $idProducto; ?>">
                        <input type="hidden" name="nombre" value="<?php echo htmlspecialchars($producto['nombre']); ?>">
                        <input type="hidden" name="precio" value="<?php echo $producto['precio']; ?>">
                        <input type="hidden" name="imagen" value="<?php echo htmlspecialchars($imagenProducto); ?>">
                        <input type="hidden" name="id_talla" id="input-talla" value="">
                        <input type="hidden" name="redirect" value="producto-detalle.php?id=<?php echo $idProducto; ?>">
                        <button type="submit" class="btn-agregar-carrito">Agregar al carrito</button>
                    </form>
                <?php endif; ?>

                <button type="button" class="btn-favorito <?php echo $esFavorito ? 'es-favorito' : ''; ?>" onclick="agregarAFavoritosDetalle(<?php echo $idProducto; ?>, '<?php echo htmlspecialchars($producto['nombre']); ?>', <?php echo $producto['precio']; ?>, '<?php echo htmlspecialchars($imagenProducto); ?>', <?php echo $esFavorito ? 'true' : 'false'; ?>)">
                    <?php echo $esFavorito ? '❤️ Eliminar de favoritos' : '🤍 Añadir a favoritos'; ?>
                </button>
            </div>

            <!-- Ir a tienda -->
            <div class="detalle-volver">
                <a href="tienda.php" class="btn-volver">← Volver a la tienda</a>
            </div>
        </div>
    </div>

    <!-- PRODUCTOS RELACIONADOS -->
    <?php if (!empty($productosRelacionados)): ?>
    <section class="productos-relacionados">
        <h2>Productos relacionados de <?php echo htmlspecialchars($producto['categoria']); ?></h2>
        <div class="cards-relacionados">
            <?php foreach ($productosRelacionados as $prod): ?>
                <?php
                $imagenRel = $imagenesProducto[$prod['id_producto']] ?? ('img/producto-' . $prod['id_producto'] . '.png');
                ?>
                <div class="card-relacionado">
                    <img src="<?php echo htmlspecialchars($imagenRel); ?>" alt="<?php echo htmlspecialchars($prod['nombre']); ?>" class="producto-img-rel">
                    <h4><?php echo htmlspecialchars($prod['nombre']); ?></h4>
                    <p class="precio-rel"><?php echo number_format($prod['precio'], 2, ',', '.'); ?> €</p>
                    <a href="producto-detalle.php?id=<?php echo $prod['id_producto']; ?>" class="btn-ver-relacionado">Ver producto</a>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>
</main>

<style>
/* Estilos para la página de detalle */
.producto-detalle-container {
    max-width: 1200px;
    margin: 40px auto;
    padding: 0 20px;
}

.breadcrumb {
    font-size: 14px;
    color: var(--veridi-text-muted);
    margin-bottom: 30px;
    display: flex;
    gap: 5px;
    flex-wrap: wrap;
}

.breadcrumb a {
    color: var(--veridi-gold);
    text-decoration: none;
    transition: color 0.3s;
}

.breadcrumb a:hover {
    color: var(--veridi-gold-light);
    text-decoration: underline;
}

.breadcrumb span {
    color: var(--veridi-text);
}

.detalle-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 50px;
    margin-bottom: 80px;
}

.detalle-imagen {
    display: flex;
    justify-content: center;
    align-items: center;
    background: linear-gradient(135deg, #1a1a1a 0%, #2a2a2a 100%);
    border: 2px solid var(--veridi-gold);
    border-radius: 8px;
    padding: 20px;
    min-height: 500px;
}

.imagen-grande {
    max-width: 100%;
    max-height: 100%;
    object-fit: contain;
}

.detalle-info h1 {
    font-size: 32px;
    font-family: Georgia, serif;
    color: var(--veridi-gold);
    margin: 0 0 20px 0;
    letter-spacing: 1px;
}

.detalle-precio {
    margin-bottom: 30px;
    padding-bottom: 30px;
    border-bottom: 2px solid var(--veridi-gold);
}

.precio-grande {
    font-size: 28px;
    font-weight: 600;
    color: var(--veridi-gold);
}

.detalle-descripcion,
.detalle-caracteristicas {
    margin-bottom: 30px;
}

.detalle-descripcion h3,
.detalle-caracteristicas h3,
.detalle-tallas h3 {
    font-family: Georgia, serif;
    color: var(--veridi-gold);
    font-size: 18px;
    margin-bottom: 12px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.detalle-caracteristicas ul {
    list-style: none;
    padding: 0;
}

.detalle-caracteristicas li {
    padding: 8px 0;
    border-bottom: 1px solid rgba(212, 175, 55, 0.2);
    color: var(--veridi-text-muted);
}

.detalle-caracteristicas li:last-child {
    border-bottom: none;
}

.detalle-caracteristicas strong {
    color: var(--veridi-gold);
    margin-right: 10px;
}

.detalle-tallas {
    margin-bottom: 30px;
}

.selector-tallas {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    margin-bottom: 15px;
}

.talla-option {
    position: relative;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
}

.talla-option input[type="radio"] {
    appearance: none;
    -webkit-appearance: none;
    width: 50px;
    height: 50px;
    border: 2px solid var(--veridi-gold);
    border-radius: 4px;
    background: transparent;
    cursor: pointer;
    transition: all 0.3s;
    display: flex;
    align-items: center;
    justify-content: center;
}

.talla-option input[type="radio"]:hover {
    background: rgba(212, 175, 55, 0.1);
}

.talla-option input[type="radio"]:checked {
    background: var(--veridi-gold);
    border-color: var(--veridi-gold-light);
}

.talla-label {
    position: absolute;
    font-size: 14px;
    font-weight: 600;
    color: var(--veridi-gold);
    pointer-events: none;
}

.talla-option input[type="radio"]:checked ~ .talla-label {
    color: white;
}

.stock-info {
    font-size: 13px;
    color: var(--veridi-text-muted);
    margin-top: 8px;
}

.sin-stock {
    color: var(--veridi-danger);
    font-weight: 600;
}

.detalle-acciones {
    display: flex;
    gap: 15px;
    margin-bottom: 30px;
    flex-wrap: wrap;
}

.form-agregar-carrito,
.form-favorito {
    flex: 1;
    min-width: 200px;
}

.btn-agregar-carrito,
.btn-favorito {
    width: 100%;
    padding: 14px 24px;
    font-size: 15px;
    font-weight: 600;
    border: 2px solid var(--veridi-gold);
    border-radius: 4px;
    background: linear-gradient(135deg, var(--veridi-gold), rgb(201, 160, 39));
    color: black;
    cursor: pointer;
    transition: all 0.3s;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.btn-agregar-carrito:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(212, 175, 55, 0.3);
}

.btn-favorito {
    background: transparent;
    color: var(--veridi-gold);
}

.btn-favorito:hover {
    background: rgba(212, 175, 55, 0.1);
}

.btn-favorito.es-favorito {
    color: #ff6b6b;
    border-color: #ff6b6b;
    background: rgba(255, 107, 107, 0.1);
}

.btn-volver {
    display: inline-block;
    color: var(--veridi-gold);
    text-decoration: none;
    font-weight: 600;
    transition: color 0.3s;
}

.btn-volver:hover {
    color: var(--veridi-gold-light);
    text-decoration: underline;
}

.productos-relacionados {
    margin-top: 80px;
    padding-top: 40px;
    border-top: 2px solid var(--veridi-gold);
}

.productos-relacionados h2 {
    font-family: Georgia, serif;
    color: var(--veridi-gold);
    font-size: 24px;
    margin-bottom: 30px;
    text-align: center;
    letter-spacing: 1px;
}

.cards-relacionados {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
}

.card-relacionado {
    background: rgba(255, 255, 255, 0.02);
    border: 1px solid rgba(212, 175, 55, 0.3);
    border-radius: 6px;
    padding: 15px;
    text-align: center;
    transition: all 0.3s;
}

.card-relacionado:hover {
    border-color: var(--veridi-gold);
    background: rgba(212, 175, 55, 0.05);
    transform: translateY(-3px);
}

.producto-img-rel {
    width: 100%;
    height: 180px;
    object-fit: cover;
    border-radius: 4px;
    margin-bottom: 12px;
}

.card-relacionado h4 {
    font-size: 14px;
    color: var(--veridi-text);
    margin: 10px 0 8px 0;
    min-height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.precio-rel {
    color: var(--veridi-gold);
    font-weight: 600;
    font-size: 14px;
    margin-bottom: 12px;
}

.btn-ver-relacionado {
    display: inline-block;
    padding: 8px 16px;
    background: transparent;
    border: 1px solid var(--veridi-gold);
    color: var(--veridi-gold);
    text-decoration: none;
    border-radius: 4px;
    font-size: 12px;
    font-weight: 600;
    transition: all 0.3s;
}

.btn-ver-relacionado:hover {
    background: var(--veridi-gold);
    color: black;
}

/* Responsive */
@media (max-width: 768px) {
    .detalle-grid {
        grid-template-columns: 1fr;
        gap: 30px;
    }

    .detalle-info h1 {
        font-size: 24px;
    }

    .precio-grande {
        font-size: 22px;
    }

    .detalle-imagen {
        min-height: 350px;
    }

    .selector-tallas {
        gap: 8px;
    }

    .talla-option input[type="radio"] {
        width: 40px;
        height: 40px;
        font-size: 12px;
    }

    .detalle-acciones {
        flex-direction: column;
    }

    .form-agregar-carrito,
    .form-favorito {
        min-width: auto;
    }

    .cards-relacionados {
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    }
}
</style>

<script>
// Sincronizar tallas con validación
const tallasRadios = document.querySelectorAll('input[name="id_talla"]');
const formCarrito = document.getElementById('form-carrito');
const inputTalla = document.getElementById('input-talla');
const stockInfo = document.getElementById('stock-info');

// Validar formulario antes de enviar
if (formCarrito) {
    formCarrito.addEventListener('submit', function(e) {
        if (!inputTalla || !inputTalla.value) {
            e.preventDefault();
            mostrarToast('❌ Por favor, selecciona una talla', 'error');
            return false;
        }
    });
}

// Sincronizar tallas con el input hidden cuando se selecciona una
tallasRadios.forEach(radio => {
    radio.addEventListener('change', function() {
        if (inputTalla) {
            inputTalla.value = this.value;
        }
        const talla = this.getAttribute('data-talla');
        const stock = this.getAttribute('data-stock');
        
        if (stockInfo) {
            stockInfo.textContent = `Stock disponible: ${stock} unidades`;
        }
    });
});

// Sistema de notificaciones Toast
function mostrarToast(mensaje, tipo = 'success') {
    const toast = document.createElement('div');
    toast.className = `toast toast-${tipo}`;
    toast.textContent = mensaje;
    toast.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 15px 24px;
        background: ${tipo === 'success' ? 'var(--veridi-gold)' : 'var(--veridi-danger)'};
        color: ${tipo === 'success' ? 'black' : 'white'};
        border-radius: 4px;
        font-weight: 600;
        z-index: 10000;
        animation: slideIn 0.3s ease;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
    `;
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => toast.remove(), 300);
    }, 2500);
}

// Agregar a favoritos desde detalle
function agregarAFavoritosDetalle(idProducto, nombre, precio, imagen, esFavorito) {
    const action = esFavorito ? 'remove' : 'add';
    
    const formData = new FormData();
    formData.append('action', action);
    formData.append('id_producto', idProducto);
    formData.append('nombre', nombre);
    formData.append('precio', precio);
    formData.append('imagen', imagen);
    
    fetch('php/favoritos_ajax.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if(data.success) {
            const btn = document.querySelector('.btn-favorito');
            if(data.esFavorito) {
                btn.classList.add('es-favorito');
                btn.textContent = '❤️ Eliminar de favoritos';
                mostrarToast('❤️ Agregado a favoritos');
            } else {
                btn.classList.remove('es-favorito');
                btn.textContent = '🤍 Añadir a favoritos';
                mostrarToast('Eliminado de favoritos');
            }
        }
    })
    .catch(error => console.error('Error:', error));
}

// CSS para animaciones
const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from {
            transform: translateX(400px);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    
    @keyframes slideOut {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(400px);
            opacity: 0;
        }
    }
`;
document.head.appendChild(style);
</script>

<?php require_once "includes/footer.php"; ?>
