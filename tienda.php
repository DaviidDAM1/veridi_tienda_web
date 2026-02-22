<?php
require_once "config/conexion.php";
require_once "config/imagenes.php";
$page_title = "Tienda";
require_once "includes/header.php";

// Mostrar mensajes de carrito/deseos
$msgCarrito = isset($_GET['carrito_msg']) ? $_GET['carrito_msg'] : '';
$msgDeseos = isset($_GET['deseos_msg']) ? $_GET['deseos_msg'] : '';

// Limpiar los parámetros de mensaje de la URL para no mostrar en los links
$quesry_clean = $_GET;
unset($quesry_clean['carrito_msg']);
unset($quesry_clean['deseos_msg']);

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
$stmtTallas = $conexion->query("SELECT DISTINCT t.nombre FROM tallas t INNER JOIN producto_tallas pt ON t.id_talla = pt.id_talla");
$tallas_disponibles = [];
if($stmtTallas) {
    $tallas_disponibles = $stmtTallas->fetchAll(PDO::FETCH_ASSOC);
}

if (!empty($_GET['talla'])) {
    $tallas_seleccionadas = array_map([$conexion, 'quote'], $_GET['talla']);
    $sqlBase .= " AND p.id_producto IN (SELECT pt.id_producto FROM producto_tallas pt INNER JOIN tallas t ON pt.id_talla = t.id_talla WHERE t.nombre IN (" . implode(",", $tallas_seleccionadas) . "))";
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

// Filtro color
$stmtColores = $conexion->query("SELECT DISTINCT color FROM productos WHERE color IS NOT NULL AND color != ''");
$colores_disponibles = $stmtColores->fetchAll(PDO::FETCH_ASSOC);

if (!empty($_GET['color'])) {
    $colores_seleccionados = array_map([$conexion, 'quote'], $_GET['color']);
    $sqlBase .= " AND p.color IN (" . implode(",", $colores_seleccionados) . ")";
}

// Filtro estilo
$stmtEstilos = $conexion->query("SELECT DISTINCT estilo FROM productos WHERE estilo IS NOT NULL AND estilo != ''");
$estilos_disponibles = $stmtEstilos->fetchAll(PDO::FETCH_ASSOC);

if (!empty($_GET['estilo'])) {
    $estilos_seleccionados = array_map([$conexion, 'quote'], $_GET['estilo']);
    $sqlBase .= " AND p.estilo IN (" . implode(",", $estilos_seleccionados) . ")";
}

// Filtro ordenamiento
$ordenamiento = "ORDER BY p.id_producto ASC";
if (!empty($_GET['ordenar'])) {
    switch ($_GET['ordenar']) {
        case 'nombre_asc':
            $ordenamiento = "ORDER BY p.nombre ASC";
            break;
        case 'nombre_desc':
            $ordenamiento = "ORDER BY p.nombre DESC";
            break;
        case 'precio_asc':
            $ordenamiento = "ORDER BY p.precio ASC";
            break;
        case 'precio_desc':
            $ordenamiento = "ORDER BY p.precio DESC";
            break;
        default:
            $ordenamiento = "ORDER BY p.id_producto ASC";
    }
}

// ---------------- TOTAL PRODUCTOS ----------------
$stmtTotal = $conexion->query("SELECT COUNT(*) as total " . $sqlBase);
$totalProductos = $stmtTotal->fetch(PDO::FETCH_ASSOC)['total'];
$totalPaginas = ceil($totalProductos / $productosPorPagina);

// ---------------- CONSULTA PRINCIPAL ----------------
$sql = "SELECT p.*, c.nombre AS categoria " . $sqlBase . " $ordenamiento LIMIT $inicio, $productosPorPagina";
$stmt = $conexion->query($sql);
$productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener tallas disponibles para cada producto
$productosConTallas = [];
foreach ($productos as $producto) {
    // Ya no necesitamos obtener id_talla_default porque siempre irán a ver producto
    $productosConTallas[] = $producto;
}
$productos = $productosConTallas;

$cantidadCarrito = 0;
if (!empty($_SESSION['carrito']) && is_array($_SESSION['carrito'])) {
    foreach ($_SESSION['carrito'] as $itemCarrito) {
        $cantidadCarrito += (int)($itemCarrito['cantidad'] ?? 0);
    }
}

$cantidadDeseos = 0;
if (!empty($_SESSION['deseos']) && is_array($_SESSION['deseos'])) {
    $cantidadDeseos = count($_SESSION['deseos']);
}

$imagenesProducto = [
    1 => 'img/camisetaNegraVeridi.png',
    2 => 'img/pantalonVeridiNegro.png',
    3 => 'img/abrigoVeridiBlanco.png',
];

$redirectTienda = 'tienda.php';
if (!empty($_GET)) {
    $redirectTienda .= '?' . http_build_query($_GET);
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

<?php if ($msgDeseos === 'added'): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const msg = document.createElement('div');
    msg.textContent = '❤️ Producto añadido a favoritos';
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

<div class="search-section">
    <h3>Encuentra el producto que estás buscando</h3>
    <div class="barra-busqueda">
        <form method="GET" action="tienda.php" id="form-busqueda">
            <input type="text" name="buscar" placeholder="Buscar producto..." value="<?php echo htmlspecialchars($_GET['buscar'] ?? ''); ?>">

            <select name="categoria" id="select-categoria">
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

            <select name="ordenar" id="select-ordenar">
                <option value="">Ordenar por</option>
                <option value="nombre_asc" <?php echo (isset($_GET['ordenar']) && $_GET['ordenar'] == 'nombre_asc') ? 'selected' : ''; ?>>📝 Nombre: A - Z</option>
                <option value="nombre_desc" <?php echo (isset($_GET['ordenar']) && $_GET['ordenar'] == 'nombre_desc') ? 'selected' : ''; ?>>📝 Nombre: Z - A</option>
                <option value="precio_asc" <?php echo (isset($_GET['ordenar']) && $_GET['ordenar'] == 'precio_asc') ? 'selected' : ''; ?>>💰 Precio: Menor a Mayor</option>
                <option value="precio_desc" <?php echo (isset($_GET['ordenar']) && $_GET['ordenar'] == 'precio_desc') ? 'selected' : ''; ?>>💰 Precio: Mayor a Menor</option>
            </select>

            <select id="select-filtro">
                <option value="">+ Añadir Filtro</option>
                <option value="precio">Precio</option>
                <option value="talla">Talla</option>
                <option value="color">Color</option>
                <option value="estilo">Estilo</option>
            </select>

            <!-- Inputs ocultos para los filtros -->
            <div id="filtros-ocultos">
                <?php if (!empty($_GET['ordenar'])): ?>
                    <input type="hidden" name="ordenar" value="<?php echo htmlspecialchars($_GET['ordenar']); ?>">
                <?php endif; ?>
                <?php if (!empty($_GET['precio_min'])): ?>
                    <input type="hidden" name="precio_min" value="<?php echo htmlspecialchars($_GET['precio_min']); ?>">
                <?php endif; ?>
                <?php if (!empty($_GET['precio_max'])): ?>
                    <input type="hidden" name="precio_max" value="<?php echo htmlspecialchars($_GET['precio_max']); ?>">
                <?php endif; ?>
                <?php if (!empty($_GET['talla'])): ?>
                    <?php foreach ($_GET['talla'] as $t): ?>
                        <input type="hidden" name="talla[]" value="<?php echo htmlspecialchars($t); ?>">
                    <?php endforeach; ?>
                <?php endif; ?>
                <?php if (!empty($_GET['color'])): ?>
                    <?php foreach ($_GET['color'] as $c): ?>
                        <input type="hidden" name="color[]" value="<?php echo htmlspecialchars($c); ?>">
                    <?php endforeach; ?>
                <?php endif; ?>
                <?php if (!empty($_GET['estilo'])): ?>
                    <?php foreach ($_GET['estilo'] as $e): ?>
                        <input type="hidden" name="estilo[]" value="<?php echo htmlspecialchars($e); ?>">
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <button type="submit" title="Buscar">🔍 Buscar</button>
        </form>

        <a href="carrito.php" class="btn-carrito">🛒 Carrito (<?php echo $cantidadCarrito; ?>)</a>
        <a href="lista-deseos.php" class="btn-deseos">💙 Productos Favoritos (<?php echo $cantidadDeseos; ?>)</a>
    </div>
    
    <!-- Mostrar filtros activos -->
    <div id="filtros-activos" class="filtros-activos">
        <?php
        $filtrosActivos = [];
        if (!empty($_GET['precio_min']) || !empty($_GET['precio_max'])) {
            $min = $_GET['precio_min'] ?? '0';
            $max = $_GET['precio_max'] ?? '∞';
            $filtrosActivos[] = "Precio: {$min}€ - {$max}€";
        }
        if (!empty($_GET['talla'])) {
            $filtrosActivos[] = "Talla: " . implode(", ", $_GET['talla']);
        }
        if (!empty($_GET['color'])) {
            $filtrosActivos[] = "Color: " . implode(", ", $_GET['color']);
        }
        if (!empty($_GET['estilo'])) {
            $filtrosActivos[] = "Estilo: " . implode(", ", $_GET['estilo']);
        }
        
        if (!empty($filtrosActivos)):
        ?>
            <div class="filtros-tags">
                <strong>Filtros aplicados:</strong>
                <?php foreach ($filtrosActivos as $filtro): ?>
                    <span class="filtro-tag"><?php echo htmlspecialchars($filtro); ?></span>
                <?php endforeach; ?>
                <a href="tienda.php" class="btn-limpiar-filtros">✖ Limpiar filtros</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- MODAL FILTRO -->
<div class="modal-overlay" id="overlay"></div>
<div class="modal-filtro" id="modal-filtro"></div>

<main>
    <div class="cards">
        <?php if($productos): ?>
            <?php foreach($productos as $producto): ?>
                <?php
                $idProductoActual = (int)$producto['id_producto'];
                $imagenProducto = obtenerImagenProducto($idProductoActual);
                // Agregar timestamp para forzar recarga de imágenes (evitar caché)
                $imagenProducto .= '?v=' . time();
                ?>
                <div class="card">
                    <img src="<?php echo htmlspecialchars($imagenProducto); ?>" alt="<?php echo htmlspecialchars($producto['nombre']); ?>" class="producto-img">
                    <h3><?php echo $producto['nombre']; ?></h3>
                    <p><?php echo $producto['descripcion']; ?></p>
                    <p>Categoria: <?php echo $producto['categoria']; ?></p>
                    <p class="precio"><?php echo $producto['precio']; ?> €</p>
                    <div class="botones-card">
                        <a class="btn-anadir" href="producto-detalle.php?id=<?php echo $producto['id_producto']; ?>">Agregar al carrito</a>

                        <?php if (isset($_SESSION['usuario_id'])): ?>
                            <!-- Usuario loqueado: mostrar botón de favoritos -->
                            <form method="POST" action="php/deseos.php" class="form-add-carrito" style="display:inline;">
                                <input type="hidden" name="action" value="add">
                                <input type="hidden" name="id_producto" value="<?php echo (int)$producto['id_producto']; ?>">
                                <input type="hidden" name="nombre" value="<?php echo htmlspecialchars($producto['nombre']); ?>">
                                <input type="hidden" name="precio" value="<?php echo (float)$producto['precio']; ?>">
                                <input type="hidden" name="imagen" value="<?php echo htmlspecialchars($imagenProducto); ?>">
                                <input type="hidden" name="redirect" value="<?php echo htmlspecialchars($redirectTienda); ?>">
                                <button type="submit" class="btn-deseo-card">Añadir a favoritos</button>
                            </form>
                        <?php else: ?>
                            <!-- Usuario no loqueado: botón deshabilitado -->
                            <button type="button" class="btn-deseo-card" style="cursor: not-allowed; opacity: 0.6;" disabled>🔒 Favoritos</button>
                        <?php endif; ?>

                        <!-- <a class="btn-ver" href="producto-detalle.php?id=<?php echo $producto['id_producto']; ?>">Ver producto</a> -->
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

<!-- SCRIPT DE FILTROS MODAL -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const filtrosOcultos = document.getElementById('filtros-ocultos');
    const overlay = document.getElementById('overlay');
    const formBusqueda = document.getElementById('form-busqueda');
    const selectFiltro = document.getElementById('select-filtro');
    const selectCategoria = document.getElementById('select-categoria');
    const selectOrdenar = document.getElementById('select-ordenar');
    const modalFiltro = document.getElementById('modal-filtro');
    
    // Datos disponibles (desde PHP)
    const tallasCF = <?php echo json_encode(array_column($tallas_disponibles, 'nombre')); ?>;
    const coloresCF = <?php echo json_encode(array_column($colores_disponibles, 'color')); ?>;
    const estilosCF = <?php echo json_encode(array_column($estilos_disponibles, 'estilo')); ?>;
    
    // Estado de filtros
    let filtrosActuales = {
        precio: { min: null, max: null },
        talla: [],
        color: [],
        estilo: []
    };
    
    // Cargar filtros desde URL si existen
    function cargarFiltrosDesdeURL() {
        const params = new URLSearchParams(window.location.search);
        
        if (params.has('precio_min') || params.has('precio_max')) {
            filtrosActuales.precio.min = parseFloat(params.get('precio_min')) || null;
            filtrosActuales.precio.max = parseFloat(params.get('precio_max')) || null;
        }
        
        if (params.has('talla')) {
            const tallas = params.getAll('talla');
            filtrosActuales.talla = tallas;
        }
        
        if (params.has('color')) {
            const colores = params.getAll('color');
            filtrosActuales.color = colores;
        }
        
        if (params.has('estilo')) {
            const estilos = params.getAll('estilo');
            filtrosActuales.estilo = estilos;
        }
    }
    
    cargarFiltrosDesdeURL();
    
    // Función para cerrar modal
    function cerrarModal() {
        overlay.style.display = 'none';
        modalFiltro.style.display = 'none';
        selectFiltro.value = '';
    }
    
    // Función para crear modal según tipo de filtro
    function abrirModalFiltro(tipoFiltro) {
        overlay.style.display = 'block';
        let contenidoModal = '';
        
        switch(tipoFiltro) {
            case 'precio':
                contenidoModal = `
                    <div class="modal-header">
                        <h3>Filtrar por Precio</h3>
                        <button class="modal-close" onclick="document.getElementById('overlay').click()">✕</button>
                    </div>
                    <div class="modal-body">
                        <div class="precio-inputs">
                            <div class="input-group">
                                <label for="precio-min">Precio mínimo (€)</label>
                                <input type="number" id="precio-min" placeholder="0" value="${filtrosActuales.precio.min || ''}" min="0" step="0.01">
                            </div>
                            <div class="input-group">
                                <label for="precio-max">Precio máximo (€)</label>
                                <input type="number" id="precio-max" placeholder="9999" value="${filtrosActuales.precio.max || ''}" min="0" step="0.01">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn-cancelar" onclick="document.getElementById('overlay').click()">Cancelar</button>
                        <button class="btn-aceptar" data-filtro="precio">Aceptar</button>
                    </div>
                `;
                break;
                
            case 'talla':
                contenidoModal = `
                    <div class="modal-header">
                        <h3>Filtrar por Talla</h3>
                        <button class="modal-close" onclick="document.getElementById('overlay').click()">✕</button>
                    </div>
                    <div class="modal-body">
                        <div class="talla-options">
                            ${tallasCF.map(talla => `
                                <label class="checkbox-container">
                                    <input type="checkbox" value="${talla}" ${filtrosActuales.talla.includes(talla) ? 'checked' : ''}>
                                    <span class="talla-label">${talla}</span>
                                </label>
                            `).join('')}
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn-cancelar" onclick="document.getElementById('overlay').click()">Cancelar</button>
                        <button class="btn-aceptar" data-filtro="talla">Aceptar</button>
                    </div>
                `;
                break;
                
            case 'color':
                contenidoModal = `
                    <div class="modal-header">
                        <h3>Filtrar por Color</h3>
                        <button class="modal-close" onclick="document.getElementById('overlay').click()">✕</button>
                    </div>
                    <div class="modal-body">
                        <div class="color-options">
                            ${coloresCF.map(color => `
                                <label class="checkbox-container">
                                    <input type="checkbox" value="${color}" ${filtrosActuales.color.includes(color) ? 'checked' : ''}>
                                    <span class="color-label">${color}</span>
                                </label>
                            `).join('')}
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn-cancelar" onclick="document.getElementById('overlay').click()">Cancelar</button>
                        <button class="btn-aceptar" data-filtro="color">Aceptar</button>
                    </div>
                `;
                break;
                
            case 'estilo':
                contenidoModal = `
                    <div class="modal-header">
                        <h3>Filtrar por Estilo</h3>
                        <button class="modal-close" onclick="document.getElementById('overlay').click()">✕</button>
                    </div>
                    <div class="modal-body">
                        <div class="estilo-options">
                            ${estilosCF.map(estilo => `
                                <label class="checkbox-container">
                                    <input type="checkbox" value="${estilo}" ${filtrosActuales.estilo.includes(estilo) ? 'checked' : ''}>
                                    <span class="estilo-label">${estilo}</span>
                                </label>
                            `).join('')}
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn-cancelar" onclick="document.getElementById('overlay').click()">Cancelar</button>
                        <button class="btn-aceptar" data-filtro="estilo">Aceptar</button>
                    </div>
                `;
                break;
        }
        
        modalFiltro.innerHTML = contenidoModal;
        modalFiltro.style.display = 'block';
        
        // Evento para el botón aceptar
        const btnAceptar = modalFiltro.querySelector('.btn-aceptar');
        btnAceptar.addEventListener('click', function() {
            const filtroTipo = this.getAttribute('data-filtro');
            
            if (filtroTipo === 'precio') {
                const min = document.getElementById('precio-min').value;
                const max = document.getElementById('precio-max').value;
                filtrosActuales.precio.min = min ? parseFloat(min) : null;
                filtrosActuales.precio.max = max ? parseFloat(max) : null;
            } else {
                const checkboxes = modalFiltro.querySelectorAll('input[type="checkbox"]');
                const seleccionados = [];
                checkboxes.forEach(cb => {
                    if (cb.checked) {
                        seleccionados.push(cb.value);
                    }
                });
                filtrosActuales[filtroTipo] = seleccionados;
            }
            
            cerrarModal();
        });
    }
    
    // Evento para abrir modal al seleccionar filtro
    if(selectFiltro) {
        selectFiltro.addEventListener('change', function() {
            if (this.value) {
                abrirModalFiltro(this.value);
            }
        });
    }
    
    // Cerrar modal al hacer click en overlay
    if(overlay) {
        overlay.addEventListener('click', cerrarModal);
    }
    
    // Evento para button de buscar - cargar filtros en inputs ocultos
    if(formBusqueda) {
        formBusqueda.addEventListener('submit', function(e) {
            // Limpiar inputs ocultos de filtros anteriores
            const inputsFiltros = filtrosOcultos.querySelectorAll('input[name="precio_min"], input[name="precio_max"], input[name="talla[]"], input[name="color[]"], input[name="estilo[]"]');
            inputsFiltros.forEach(input => input.remove());
            
            // Agregar filtro de precio
            if (filtrosActuales.precio.min !== null) {
                const inputMin = document.createElement('input');
                inputMin.type = 'hidden';
                inputMin.name = 'precio_min';
                inputMin.value = filtrosActuales.precio.min;
                filtrosOcultos.appendChild(inputMin);
            }
            if (filtrosActuales.precio.max !== null) {
                const inputMax = document.createElement('input');
                inputMax.type = 'hidden';
                inputMax.name = 'precio_max';
                inputMax.value = filtrosActuales.precio.max;
                filtrosOcultos.appendChild(inputMax);
            }
            
            // Agregar filtro de talla
            filtrosActuales.talla.forEach(t => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'talla[]';
                input.value = t;
                filtrosOcultos.appendChild(input);
            });
            
            // Agregar filtro de color
            filtrosActuales.color.forEach(c => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'color[]';
                input.value = c;
                filtrosOcultos.appendChild(input);
            });
            
            // Agregar filtro de estilo
            filtrosActuales.estilo.forEach(e => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'estilo[]';
                input.value = e;
                filtrosOcultos.appendChild(input);
            });
        });
    }
    
    // Sincronizar ordenamiento
    if(selectOrdenar) {
        selectOrdenar.addEventListener('change', function() {
            const inputOrdenarAnterior = document.querySelector('input[name="ordenar"]');
            if(inputOrdenarAnterior && inputOrdenarAnterior.type === 'hidden') {
                inputOrdenarAnterior.remove();
            }
            
            if(this.value) {
                const inputOrdenar = document.createElement('input');
                inputOrdenar.type = 'hidden';
                inputOrdenar.name = 'ordenar';
                inputOrdenar.value = this.value;
                filtrosOcultos.appendChild(inputOrdenar);
            }
        });
    }
});
</script>

