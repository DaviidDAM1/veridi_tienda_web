<?php
require_once "config/conexion.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.php?auth_open=1&auth_tab=login&auth_error=requiere_login");
    exit;
}

if (($_SESSION['usuario_rol'] ?? 'cliente') !== 'admin') {
    header("Location: index.php");
    exit;
}

try {
    $conexion->exec("ALTER TABLE productos ADD COLUMN oculto TINYINT(1) NOT NULL DEFAULT 0");
} catch (Exception $e) {
}

$page_title = "Panel de Administrador";
require_once "includes/header.php";

$adminMsg = $_GET['admin_msg'] ?? '';
$msgTexto = '';
$msgClass = '';

$mapaMensajes = [
    'create_ok' => ['Producto creado correctamente.', 'success-message'],
    'edit_ok' => ['Producto editado correctamente.', 'success-message'],
    'delete_ok' => ['Producto eliminado correctamente.', 'success-message'],
    'hide_ok' => ['Estado de visibilidad actualizado.', 'success-message'],
    'stock_ok' => ['Stock actualizado correctamente.', 'success-message'],
    'create_invalid' => ['Datos inválidos al crear producto.', 'error-message'],
    'edit_invalid' => ['Datos inválidos al editar producto.', 'error-message'],
    'delete_invalid' => ['ID de producto inválido para eliminar.', 'error-message'],
    'hide_invalid' => ['No se pudo cambiar la visibilidad.', 'error-message'],
    'stock_invalid' => ['Datos inválidos al actualizar stock.', 'error-message'],
    'action_invalid' => ['Acción de administrador no válida.', 'error-message'],
    'error' => ['Ocurrió un error al procesar la acción.', 'error-message']
];

if (isset($mapaMensajes[$adminMsg])) {
    $msgTexto = $mapaMensajes[$adminMsg][0];
    $msgClass = $mapaMensajes[$adminMsg][1];
}

$stmtCategorias = $conexion->query("SELECT id_categoria, nombre FROM categorias ORDER BY nombre ASC");
$categorias = $stmtCategorias->fetchAll(PDO::FETCH_ASSOC);

$stmtTallas = $conexion->query("SELECT id_talla, nombre FROM tallas ORDER BY id_talla ASC");
$tallas = $stmtTallas->fetchAll(PDO::FETCH_ASSOC);

$stmtProductos = $conexion->query("
    SELECT p.id_producto, p.nombre, p.precio, p.estilo, p.oculto, c.nombre AS categoria, COALESCE(SUM(pt.stock), 0) AS stock_total
    FROM productos p
    LEFT JOIN categorias c ON c.id_categoria = p.id_categoria
    LEFT JOIN producto_tallas pt ON pt.id_producto = p.id_producto
    GROUP BY p.id_producto
    ORDER BY p.id_producto DESC
");
$productos = $stmtProductos->fetchAll(PDO::FETCH_ASSOC);

$stmtUsuarios = $conexion->query("SELECT id_usuario, nombre, email, password, rol FROM usuarios ORDER BY id_usuario DESC");
$usuarios = $stmtUsuarios->fetchAll(PDO::FETCH_ASSOC);
?>

<main>
    <section class="producto-detalle-container" style="max-width: 1300px;">
        <h1 style="margin-bottom: 8px;">Panel de Administrador</h1>
        <p style="color: var(--veridi-text-secondary); margin-bottom: 20px;">Gestiona productos, stock, visibilidad y usuarios registrados.</p>

        <?php if ($msgTexto !== ''): ?>
            <div class="<?php echo $msgClass; ?>" style="margin-bottom: 20px;"><?php echo htmlspecialchars($msgTexto); ?></div>
        <?php endif; ?>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 16px; margin-bottom: 24px;">
            <div style="background: var(--veridi-surface); border: 1px solid var(--veridi-border); border-radius: 10px; padding: 16px;">
                <h3 style="font-size: 18px;">Crear producto</h3>
                <form method="POST" action="php/admin_productos.php" style="display:flex; flex-direction:column; gap:10px;">
                    <input type="hidden" name="action" value="create_product">
                    <input type="text" name="nombre" placeholder="Nombre" required>
                    <textarea name="descripcion" rows="3" placeholder="Descripción"></textarea>
                    <input type="number" step="0.01" min="0.01" name="precio" placeholder="Precio" required>
                    <input type="text" name="color" placeholder="Color">
                    <select name="estilo" required>
                        <option value="casual">Casual</option>
                        <option value="formal">Formal</option>
                        <option value="deportivo">Deportivo</option>
                    </select>
                    <input type="text" name="material" placeholder="Material">
                    <select name="id_categoria" required>
                        <option value="">Selecciona categoría</option>
                        <?php foreach ($categorias as $categoria): ?>
                            <option value="<?php echo (int)$categoria['id_categoria']; ?>"><?php echo htmlspecialchars($categoria['nombre']); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <input type="number" min="0" name="stock_inicial" placeholder="Stock inicial por talla (ej: 20)" value="0">
                    <button type="submit" class="profile-save-btn">Crear producto</button>
                </form>
            </div>

            <div style="background: var(--veridi-surface); border: 1px solid var(--veridi-border); border-radius: 10px; padding: 16px;">
                <h3 style="font-size: 18px;">Editar producto</h3>
                <form method="POST" action="php/admin_productos.php" style="display:flex; flex-direction:column; gap:10px;">
                    <input type="hidden" name="action" value="edit_product">
                    <input type="number" min="1" name="id_producto" placeholder="ID producto" required>
                    <input type="text" name="nombre" placeholder="Nuevo nombre" required>
                    <textarea name="descripcion" rows="3" placeholder="Nueva descripción"></textarea>
                    <input type="number" step="0.01" min="0.01" name="precio" placeholder="Nuevo precio" required>
                    <input type="text" name="color" placeholder="Color">
                    <select name="estilo" required>
                        <option value="casual">Casual</option>
                        <option value="formal">Formal</option>
                        <option value="deportivo">Deportivo</option>
                    </select>
                    <input type="text" name="material" placeholder="Material">
                    <select name="id_categoria" required>
                        <option value="">Selecciona categoría</option>
                        <?php foreach ($categorias as $categoria): ?>
                            <option value="<?php echo (int)$categoria['id_categoria']; ?>"><?php echo htmlspecialchars($categoria['nombre']); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="profile-save-btn">Guardar edición</button>
                </form>
            </div>

            <div style="background: var(--veridi-surface); border: 1px solid var(--veridi-border); border-radius: 10px; padding: 16px;">
                <h3 style="font-size: 18px;">Eliminar producto</h3>
                <form method="POST" action="php/admin_productos.php" style="display:flex; flex-direction:column; gap:10px;">
                    <input type="hidden" name="action" value="delete_product">
                    <input type="number" min="1" name="id_producto" placeholder="ID producto" required>
                    <button type="submit" class="profile-save-btn" style="background: linear-gradient(135deg, #8f2323 0%, #d32f2f 100%); color: #fff;">Eliminar producto</button>
                </form>
            </div>
        </div>

        <h2 style="font-size: 22px; margin-top: 20px;">Productos y stock</h2>
        <div style="overflow-x:auto; margin-bottom: 24px; border:1px solid var(--veridi-border); border-radius:10px;">
            <table style="width:100%; border-collapse: collapse; min-width: 1020px;">
                <thead>
                    <tr style="background: rgba(212,175,55,0.15);">
                        <th style="padding:10px; text-align:left;">ID</th>
                        <th style="padding:10px; text-align:left;">Nombre</th>
                        <th style="padding:10px; text-align:left;">Categoría</th>
                        <th style="padding:10px; text-align:left;">Precio</th>
                        <th style="padding:10px; text-align:left;">Estilo</th>
                        <th style="padding:10px; text-align:left;">Stock total</th>
                        <th style="padding:10px; text-align:left;">Estado</th>
                        <th style="padding:10px; text-align:left;">Ocultar / mostrar</th>
                        <th style="padding:10px; text-align:left;">Ajustar stock</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($productos as $producto): ?>
                        <tr style="border-top: 1px solid var(--veridi-border);">
                            <td style="padding:10px;"><?php echo (int)$producto['id_producto']; ?></td>
                            <td style="padding:10px;"><?php echo htmlspecialchars($producto['nombre']); ?></td>
                            <td style="padding:10px;"><?php echo htmlspecialchars($producto['categoria'] ?? 'Sin categoría'); ?></td>
                            <td style="padding:10px;">€<?php echo number_format((float)$producto['precio'], 2, ',', '.'); ?></td>
                            <td style="padding:10px;"><?php echo htmlspecialchars(ucfirst((string)$producto['estilo'])); ?></td>
                            <td style="padding:10px;"><?php echo (int)$producto['stock_total']; ?></td>
                            <td style="padding:10px;"><?php echo ((int)$producto['oculto'] === 1) ? 'Oculto' : 'Visible'; ?></td>
                            <td style="padding:10px;">
                                <form method="POST" action="php/admin_productos.php" style="display:inline-flex; gap:8px; align-items:center;">
                                    <input type="hidden" name="action" value="toggle_hide">
                                    <input type="hidden" name="id_producto" value="<?php echo (int)$producto['id_producto']; ?>">
                                    <input type="hidden" name="oculto" value="<?php echo ((int)$producto['oculto'] === 1) ? 0 : 1; ?>">
                                    <button type="submit" style="padding:6px 10px; border-radius:6px; border:1px solid var(--veridi-gold); background:transparent; color:var(--veridi-gold); cursor:pointer;">
                                        <?php echo ((int)$producto['oculto'] === 1) ? 'Mostrar' : 'Ocultar'; ?>
                                    </button>
                                </form>
                            </td>
                            <td style="padding:10px;">
                                <form method="POST" action="php/admin_productos.php" style="display:flex; gap:6px; align-items:center;">
                                    <input type="hidden" name="action" value="adjust_stock">
                                    <input type="hidden" name="id_producto" value="<?php echo (int)$producto['id_producto']; ?>">
                                    <select name="id_talla" required style="padding:5px;">
                                        <?php foreach ($tallas as $talla): ?>
                                            <option value="<?php echo (int)$talla['id_talla']; ?>"><?php echo htmlspecialchars($talla['nombre']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <input type="number" name="delta" required placeholder="+/-" style="width:72px; padding:5px;" title="Usa positivo para sumar y negativo para restar">
                                    <button type="submit" style="padding:6px 10px; border-radius:6px; border:1px solid var(--veridi-gold); background:var(--veridi-gold); color:var(--veridi-black); cursor:pointer;">Aplicar</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <h2 style="font-size: 22px;">Usuarios registrados</h2>
        <div style="overflow-x:auto; border:1px solid var(--veridi-border); border-radius:10px;">
            <table style="width:100%; border-collapse: collapse; min-width: 900px;">
                <thead>
                    <tr style="background: rgba(212,175,55,0.15);">
                        <th style="padding:10px; text-align:left;">ID</th>
                        <th style="padding:10px; text-align:left;">Nombre</th>
                        <th style="padding:10px; text-align:left;">Email</th>
                        <th style="padding:10px; text-align:left;">Rol</th>
                        <th style="padding:10px; text-align:left;">Password (hash)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($usuarios as $usuario): ?>
                        <tr style="border-top: 1px solid var(--veridi-border);">
                            <td style="padding:10px;"><?php echo (int)$usuario['id_usuario']; ?></td>
                            <td style="padding:10px;"><?php echo htmlspecialchars($usuario['nombre']); ?></td>
                            <td style="padding:10px;"><?php echo htmlspecialchars($usuario['email']); ?></td>
                            <td style="padding:10px;"><?php echo htmlspecialchars($usuario['rol']); ?></td>
                            <td style="padding:10px; font-family: Consolas, monospace; font-size: 12px; word-break: break-all;"><?php echo htmlspecialchars($usuario['password']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
</main>

<?php require_once "includes/footer.php"; ?>
