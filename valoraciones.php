<?php
require_once "config/conexion.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$page_title = "Valoraciones";
require_once "includes/header.php";

$filtroEstrellas = isset($_GET['estrellas']) ? (int)$_GET['estrellas'] : 0;
if ($filtroEstrellas < 1 || $filtroEstrellas > 5) {
    $filtroEstrellas = 0;
}

$sqlResumen = "SELECT COUNT(*) AS total, ROUND(AVG(estrellas), 2) AS promedio FROM valoraciones";
$stmtResumen = $conexion->query($sqlResumen);
$resumen = $stmtResumen->fetch(PDO::FETCH_ASSOC) ?: ['total' => 0, 'promedio' => 0];

$sqlValoraciones = "
    SELECT v.id_valoracion, v.estrellas, v.comentario, v.fecha, u.nombre
    FROM valoraciones v
    INNER JOIN usuarios u ON v.id_usuario = u.id_usuario
";

$params = [];
if ($filtroEstrellas > 0) {
    $sqlValoraciones .= " WHERE v.estrellas = :estrellas ";
    $params[':estrellas'] = $filtroEstrellas;
}

$sqlValoraciones .= " ORDER BY v.fecha DESC";

$stmtValoraciones = $conexion->prepare($sqlValoraciones);
foreach ($params as $param => $value) {
    $stmtValoraciones->bindValue($param, $value, PDO::PARAM_INT);
}
$stmtValoraciones->execute();
$valoraciones = $stmtValoraciones->fetchAll(PDO::FETCH_ASSOC);

function pintarEstrellas(int $cantidad): string
{
    $cantidad = max(1, min(5, $cantidad));
    return str_repeat('★', $cantidad) . str_repeat('☆', 5 - $cantidad);
}
?>

<main>
    <section class="producto-detalle-container" style="max-width: 980px; margin-top: 32px;">
        <h1 style="color: var(--veridi-gold); margin-bottom: 8px;">Valoraciones de clientes</h1>
        <p style="color: var(--veridi-text-secondary); margin-bottom: 24px;">Consulta la experiencia de compra y filtra por estrellas.</p>

        <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px; margin-bottom: 24px;">
            <div style="background: var(--veridi-surface); border: 1px solid rgba(212, 175, 55, 0.3); border-radius: 10px; padding: 16px;">
                <p style="margin:0; color: var(--veridi-text-muted);">Total valoraciones</p>
                <p style="margin:6px 0 0 0; color: var(--veridi-gold); font-size: 28px; font-weight: 700;"><?php echo (int)$resumen['total']; ?></p>
            </div>
            <div style="background: var(--veridi-surface); border: 1px solid rgba(212, 175, 55, 0.3); border-radius: 10px; padding: 16px;">
                <p style="margin:0; color: var(--veridi-text-muted);">Promedio</p>
                <p style="margin:6px 0 0 0; color: var(--veridi-gold); font-size: 28px; font-weight: 700;"><?php echo number_format((float)($resumen['promedio'] ?? 0), 2, ',', '.'); ?>/5</p>
            </div>
        </div>

        <form method="GET" action="valoraciones.php" style="display:flex; align-items:center; gap:12px; flex-wrap:wrap; margin-bottom: 26px;">
            <label for="filtro-estrellas" style="color: var(--veridi-text); font-weight:600;">Filtrar por estrellas:</label>
            <select id="filtro-estrellas" name="estrellas" style="padding:10px 12px; border:2px solid var(--veridi-gold); border-radius:8px; background: var(--veridi-dark); color: var(--veridi-text);">
                <option value="0" <?php echo $filtroEstrellas === 0 ? 'selected' : ''; ?>>Todas</option>
                <?php for ($i = 5; $i >= 1; $i--): ?>
                    <option value="<?php echo $i; ?>" <?php echo $filtroEstrellas === $i ? 'selected' : ''; ?>><?php echo $i; ?> estrellas</option>
                <?php endfor; ?>
            </select>
            <button type="submit" style="background: var(--veridi-gold); color: var(--veridi-black); border:0; border-radius:8px; padding:10px 16px; font-weight:700; cursor:pointer;">Aplicar</button>
            <a href="valoraciones.php" style="color: var(--veridi-gold); text-decoration:none; font-weight:600;">Limpiar</a>
        </form>

        <?php if (empty($valoraciones)): ?>
            <div style="padding: 20px; border: 1px dashed rgba(212, 175, 55, 0.4); border-radius: 10px; color: var(--veridi-text-secondary);">
                No hay valoraciones para el filtro seleccionado.
            </div>
        <?php else: ?>
            <div style="display:flex; flex-direction:column; gap:14px;">
                <?php foreach ($valoraciones as $valoracion): ?>
                    <article style="background: var(--veridi-surface); border: 1px solid rgba(212, 175, 55, 0.25); border-radius: 10px; padding: 16px;">
                        <div style="display:flex; justify-content:space-between; gap:10px; flex-wrap:wrap; margin-bottom:8px;">
                            <strong style="color: var(--veridi-text);"><?php echo htmlspecialchars($valoracion['nombre']); ?></strong>
                            <span style="color: var(--veridi-text-muted); font-size: 13px;"><?php echo date('d/m/Y H:i', strtotime($valoracion['fecha'])); ?></span>
                        </div>
                        <div style="color: var(--veridi-gold); font-size: 22px; letter-spacing: 1px; margin-bottom: 8px;">
                            <?php echo pintarEstrellas((int)$valoracion['estrellas']); ?>
                        </div>
                        <p style="margin:0; color: var(--veridi-text-secondary); line-height:1.5;">
                            <?php
                                $comentario = trim((string)($valoracion['comentario'] ?? ''));
                                echo $comentario !== ''
                                    ? htmlspecialchars($comentario)
                                    : 'Sin comentario adicional.';
                            ?>
                        </p>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</main>

<?php require_once "includes/footer.php"; ?>
