<?php
require_once "config/conexion.php";
$page_title = "Tienda";
require_once "includes/header.php";

// ---------------- PAGINACION ----------------
$productosPorPagina = 16;
$paginaActual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$inicio = ($paginaActual - 1) * $productosPorPagina;

// ---------------- SQL BASE ----------------
$sqlBase = "FROM productos p LEFT JOIN categorias c ON p.id_categoria = c.id_categoria WHERE 1";

// Filtro búsqueda
if (!empty($_GET['buscar'])) {
    $buscar = $conexion->quote('%' . $_GET['buscar'] . '%');
    $sqlBase .= " AND p.nombre LIKE $buscar";
}

// Filtro categoría
if (!empty($_GET['categoria'])) {
    $categoria = (int)$_GET['categoria'];
    $sqlBase .= " AND p.id_categoria = $categoria";
}

// Filtro tallas
$stmtTallas = $conexion->query("SELECT DISTINCT talla FROM productos");
$tallas_disponibles = $stmtTallas->fetchAll(PDO::FETCH_ASSOC);

if (!empty($_GET['talla'])) {
    $tallas_seleccionadas = array_map([$conexion, 'quote'], $_GET['talla']);
    $sqlBase .= " AND p.talla IN (" . implode(",", $tallas_seleccionadas) . ")";
}

// Filtro precio
if (!empty($_GET['precio_min'])) {
    $precio_min = (float)$_GET['precio_min'];
    $sqlBase .= " AND p.precio >= $precio_min";
}
if (!empty($_GET['precio_max'])) {
    $precio_max = (float)$_GET['precio_max'];
    $sqlBase .= " AND p.precio <= $precio_max";
}

// ---------------- TOTAL PRODUCTOS ----------------
$stmtTotal = $conexion->query("SELECT COUNT(*) as total " . $sqlBase);
$totalProductos = $stmtTotal->fetch(PDO::FETCH_ASSOC)['total'];
$totalPaginas = ceil($totalProductos / $productosPorPagina);

// ---------------- CONSULTA PRINCIPAL ----------------
$sql = "SELECT p.*, c.nombre AS categoria " . $sqlBase . " ORDER BY p.id_producto ASC LIMIT $inicio, $productosPorPagina";
$stmt = $conexion->query($sql);
$productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

$cantidadCarrito = 0;
if (!empty($_SESSION['carrito']) && is_array($_SESSION['carrito'])) {
    foreach ($_SESSION['carrito'] as $itemCarrito) {
        $cantidadCarrito += (int)($itemCarrito['cantidad'] ?? 0);
    }
}

$redirectTienda = '../tienda.php';
if (!empty($_GET)) {
    $redirectTienda .= '?' . http_build_query($_GET);
}
?>

<div class="search-section">
    <h3>Busca el producto que estás buscando</h3>
    <div class="barra-busqueda">
        <form method="GET" action="tienda.php" id="form-busqueda">
            <input type="text" name="buscar" placeholder="Buscar producto..." value="<?php echo htmlspecialchars($_GET['buscar'] ?? ''); ?>">

            <select name="categoria">
                <option value="">Todas las categorías</option>
                <?php
                $stmtCat = $conexion->query("SELECT * FROM categorias ORDER BY nombre ASC");
                $categorias = $stmtCat->fetchAll(PDO::FETCH_ASSOC);
                foreach ($categorias as $cat):
                    $selected = (isset($_GET['categoria']) && $_GET['categoria'] == $cat['id_categoria']) ? 'selected' : '';
                ?>
                    <option value="<?php echo $cat['id_categoria']; ?>" <?php echo $selected; ?>><?php echo $cat['nombre']; ?></option>
                <?php endforeach; ?>
            </select>

            <select id="select-filtro">
                <option value="">Filtrar</option>
                <option value="precio">Precio</option>
                <option value="talla">Talla</option>
            </select>

            <button type="submit">🔍</button>
        </form>

        <a href="carrito.php" class="btn-carrito">🛒 Carrito (<?php echo $cantidadCarrito; ?>)</a>
    </div>
</div>

<!-- MODAL FILTRO -->
<div class="modal-overlay" id="overlay"></div>
<div class="modal-filtro" id="modal-filtro"></div>

<main>
    <div class="cards">
        <?php if($productos): ?>
            <?php foreach($productos as $producto): ?>
                <div class="card">
                    <img src="img/producto<?php echo $producto['id_producto']; ?>.jpg" alt="<?php echo $producto['nombre']; ?>" class="producto-img">
                    <h3><?php echo $producto['nombre']; ?></h3>
                    <p><?php echo $producto['descripcion']; ?></p>
                    <p>Categoria: <?php echo $producto['categoria']; ?></p>
                    <p class="precio"><?php echo $producto['precio']; ?> €</p>
                    <div class="botones-card">
                        <form method="POST" action="php/carrito.php" class="form-add-carrito">
                            <input type="hidden" name="action" value="add">
                            <input type="hidden" name="id_producto" value="<?php echo (int)$producto['id_producto']; ?>">
                            <input type="hidden" name="nombre" value="<?php echo htmlspecialchars($producto['nombre']); ?>">
                            <input type="hidden" name="precio" value="<?php echo (float)$producto['precio']; ?>">
                            <input type="hidden" name="imagen" value="img/producto<?php echo (int)$producto['id_producto']; ?>.jpg">
                            <input type="hidden" name="redirect" value="<?php echo htmlspecialchars($redirectTienda); ?>">
                            <button type="submit" class="btn-anadir">Agregar al carrito</button>
                        </form>
                        <a class="btn-ver" href="producto.php?id=<?php echo $producto['id_producto']; ?>">Ver</a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>No hay productos disponibles.</p>
        <?php endif; ?>
    </div>

    <!-- PAGINACION -->
    <?php if($totalPaginas > 1): ?>
    <div class="paginacion">
        <?php if($paginaActual > 1): ?>
            <a href="?<?php echo http_build_query(array_merge($_GET,['pagina'=>$paginaActual-1])); ?>">&laquo; Anterior</a>
        <?php endif; ?>

        <?php for($i=1; $i <= $totalPaginas; $i++): ?>
            <a href="?<?php echo http_build_query(array_merge($_GET,['pagina'=>$i])); ?>" <?php echo ($i==$paginaActual)?'style="font-weight:bold;"':''; ?>><?php echo $i; ?></a>
        <?php endfor; ?>

        <?php if($paginaActual < $totalPaginas): ?>
            <a href="?<?php echo http_build_query(array_merge($_GET,['pagina'=>$paginaActual+1])); ?>">Siguiente &raquo;</a>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</main>

<?php require_once "includes/footer.php"; ?>

<script>
const selectFiltro = document.getElementById('select-filtro');
const modal = document.getElementById('modal-filtro');
const overlay = document.getElementById('overlay');
const formBusqueda = document.getElementById('form-busqueda');

selectFiltro.addEventListener('change', function() {
    modal.innerHTML = '';
    if(this.value === 'precio') {
        modal.innerHTML = `
            <label>Min: <input type="number" name="precio_min" placeholder="0"></label>
            <label>Max: <input type="number" name="precio_max" placeholder="1000"></label>
            <button type="button" id="aceptar-filtro">Aceptar</button>
        `;
        modal.style.display = 'block';
        overlay.style.display = 'block';
    } else if(this.value === 'talla') {
        <?php
        $htmlTallas = '';
        foreach($tallas_disponibles as $t) {
            $htmlTallas .= '<label><input type="checkbox" name="talla[]" value="'.$t['talla'].'"> '.$t['talla'].'</label>';
        }
        ?>
        modal.innerHTML = `<?php echo $htmlTallas; ?><button type="button" id="aceptar-filtro">Aceptar</button>`;
        modal.style.display = 'block';
        overlay.style.display = 'block';
    } else {
        modal.style.display = 'none';
        overlay.style.display = 'none';
    }

    const btnAceptar = document.getElementById('aceptar-filtro');
    if(btnAceptar){
        btnAceptar.addEventListener('click', function(){
            formBusqueda.submit();
        });
    }
});

overlay.addEventListener('click', function() {
    modal.style.display = 'none';
    overlay.style.display = 'none';
    selectFiltro.value = '';
});
</script>
