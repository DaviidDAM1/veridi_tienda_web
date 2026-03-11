<?php
require_once "config/conexion.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ========== VERIFICACIÓN: Usuario debe estar loqueado para ver confirmación ==========
if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.php?auth_open=1&auth_tab=login&auth_error=requiere_login");
    exit;
}

$page_title = "Confirmación de Pedido";
require_once "includes/header.php";

// Obtener ID del pedido
$id_pedido = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$valoracionMsg = $_GET['valoracion_msg'] ?? '';

if ($id_pedido <= 0) {
    header("Location: tienda.php");
    exit;
}

// Obtener datos del pedido
$stmt = $conexion->prepare("
    SELECT p.id_pedido, p.total, p.fecha, p.estado, p.direccion, u.nombre, u.email
    FROM pedidos p
    LEFT JOIN usuarios u ON p.id_usuario = u.id_usuario
    WHERE p.id_pedido = :id AND p.id_usuario = :id_usuario
");
$stmt->bindParam(':id', $id_pedido, PDO::PARAM_INT);
$stmt->bindParam(':id_usuario', $_SESSION['usuario_id'], PDO::PARAM_INT);
$stmt->execute();
$pedido = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$pedido) {
    header("Location: tienda.php");
    exit;
}

// Obtener detalles del pedido
$stmt = $conexion->prepare("
    SELECT pd.id_detalle, pd.cantidad, pd.precio_unitario, 
           pr.nombre AS producto_nombre, pr.id_producto,
           t.nombre AS talla_nombre
    FROM pedido_detalle pd
    JOIN productos pr ON pd.id_producto = pr.id_producto
    JOIN tallas t ON pd.id_talla = t.id_talla
    WHERE pd.id_pedido = :id
");
$stmt->bindParam(':id', $id_pedido, PDO::PARAM_INT);
$stmt->execute();
$detalles = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener imagen mapa
$imagenesProducto = [
    1 => 'img/camisetaNegraVeridi.png',
    2 => 'img/pantalonVeridiNegro.png',
    3 => 'img/abrigoVeridiBlanco.png',
];

$textoValoracion = '';
$tipoValoracion = '';
if ($valoracionMsg === 'saved') {
    $textoValoracion = '¡Gracias! Tu valoración se guardó correctamente.';
    $tipoValoracion = 'success-message';
} elseif ($valoracionMsg === 'invalid') {
    $textoValoracion = 'Selecciona entre 1 y 5 estrellas para enviar tu valoración.';
    $tipoValoracion = 'error-message';
} elseif ($valoracionMsg === 'error') {
    $textoValoracion = 'No se pudo guardar la valoración. Inténtalo de nuevo.';
    $tipoValoracion = 'error-message';
}
?>

<main>
    <div class="producto-detalle-container">
        <!-- TICKET DE COMPRA -->
        <div style="background: linear-gradient(135deg, var(--veridi-dark) 0%, var(--veridi-surface) 100%); 
                    border: 2px solid var(--veridi-gold); border-radius: 12px; padding: 50px; 
                    max-width: 700px; margin: 40px auto; box-shadow: 0 8px 30px rgba(212, 175, 55, 0.2);">
            
            <!-- HEADER DEL TICKET -->
            <div style="text-align: center; border-bottom: 2px solid var(--veridi-gold); padding-bottom: 30px; margin-bottom: 30px;">
                <h1 style="color: var(--veridi-gold); margin: 0 0 10px 0; font-size: 36px; font-family: Georgia, serif;">
                    ✓ GRACIAS POR TU COMPRA
                </h1>
                <p style="color: var(--veridi-text-secondary); margin: 0; font-size: 16px;">
                    Tu pedido ha sido confirmado y procesado exitosamente
                </p>
            </div>

            <!-- INFO DEL PEDIDO -->
            <div style="background: rgba(212, 175, 55, 0.05); padding: 20px; border-radius: 8px; margin-bottom: 30px; border: 1px solid rgba(212, 175, 55, 0.15);">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div>
                        <p style="color: var(--veridi-text-muted); margin: 0 0 5px 0; font-size: 12px; text-transform: uppercase; letter-spacing: 1px;">Número de Pedido</p>
                        <p style="color: var(--veridi-gold); font-weight: 700; margin: 0; font-size: 18px;">#<?php echo str_pad($pedido['id_pedido'], 6, '0', STR_PAD_LEFT); ?></p>
                    </div>
                    <div>
                        <p style="color: var(--veridi-text-muted); margin: 0 0 5px 0; font-size: 12px; text-transform: uppercase; letter-spacing: 1px;">Fecha</p>
                        <p style="color: var(--veridi-gold); font-weight: 700; margin: 0; font-size: 18px;">
                            <?php echo date('d/m/Y H:i', strtotime($pedido['fecha'])); ?>
                        </p>
                    </div>
                </div>
            </div>

            <!-- DATOS DE ENVÍO -->
            <div style="margin-bottom: 30px;">
                <h3 style="color: var(--veridi-gold); font-size: 14px; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 12px;">Dirección de Envío</h3>
                <p style="color: var(--veridi-text); background: rgba(212, 175, 55, 0.08); padding: 15px; border-radius: 6px; margin: 0; line-height: 1.6;">
                    <?php echo htmlspecialchars($pedido['direccion']); ?>
                </p>
            </div>

            <!-- CLIENTE -->
            <div style="margin-bottom: 30px; padding-bottom: 20px; border-bottom: 1px solid rgba(212, 175, 55, 0.2);">
                <h3 style="color: var(--veridi-gold); font-size: 14px; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 12px;">Cliente</h3>
                <p style="color: var(--veridi-text); margin: 0; font-weight: 600;"><?php echo htmlspecialchars($pedido['nombre'] ?? 'Cliente'); ?></p>
                <p style="color: var(--veridi-text-secondary); margin: 0;"><?php echo htmlspecialchars($pedido['email'] ?? ''); ?></p>
            </div>

            <!-- DETALLES DE PRODUCTOS -->
            <div style="margin-bottom: 30px;">
                <h3 style="color: var(--veridi-gold); font-size: 14px; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 15px;">Productos Pedidos</h3>
                
                <?php foreach ($detalles as $detalle): ?>
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; padding: 15px 0; border-bottom: 1px solid rgba(212, 175, 55, 0.15);">
                        <div style="flex: 1;">
                            <p style="color: var(--veridi-text); margin: 0 0 5px 0; font-weight: 600;">
                                <?php echo htmlspecialchars($detalle['producto_nombre']); ?>
                            </p>
                            <p style="color: var(--veridi-text-muted); margin: 0; font-size: 13px;">
                                Talla: <strong><?php echo htmlspecialchars($detalle['talla_nombre']); ?></strong> | 
                                Cantidad: <strong><?php echo $detalle['cantidad']; ?></strong>
                            </p>
                        </div>
                        <div style="text-align: right;">
                            <p style="color: var(--veridi-gold-light); font-weight: 700; margin: 0;">
                                €<?php echo number_format($detalle['precio_unitario'], 2, ',', '.'); ?>
                            </p>
                            <p style="color: var(--veridi-text-muted); font-size: 12px; margin: 0;">
                                (x<?php echo $detalle['cantidad']; ?> = €<?php echo number_format($detalle['precio_unitario'] * $detalle['cantidad'], 2, ',', '.'); ?>)
                            </p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- TOTAL -->
            <div style="background: var(--veridi-gold); color: var(--veridi-black); padding: 25px; border-radius: 8px; text-align: center; margin-bottom: 30px;">
                <p style="margin: 0 0 8px 0; font-size: 14px; text-transform: uppercase; letter-spacing: 1px;">Monto Total</p>
                <p style="margin: 0; font-size: 42px; font-weight: 700;">
                    €<?php echo number_format($pedido['total'], 2, ',', '.'); ?>
                </p>
            </div>

            <!-- INFO ADICIONAL -->
            <div style="background: rgba(212, 175, 55, 0.03); padding: 15px; border-radius: 6px; border-left: 4px solid var(--veridi-gold); margin-bottom: 30px;">
                <p style="color: var(--veridi-text); margin: 0; font-size: 14px; line-height: 1.6;">
                    📧 Recibirás actualizaciones de tu pedido en tu email.<br>
                    🚚 Tu pedido se procesará y enviará en breve.<br>
                    ✓ Número de seguimiento: <strong>#<?php echo str_pad($pedido['id_pedido'], 6, '0', STR_PAD_LEFT); ?></strong>
                </p>
            </div>

            <!-- FIRMA DEL TICKET -->
            <div style="text-align: center; padding-top: 20px; border-top: 1px solid rgba(212, 175, 55, 0.2);">
                <p style="color: var(--veridi-text-muted); margin: 0; font-size: 12px; font-style: italic;">
                    Veridi - Luxury Fashion Store<br>
                    Gracias por tu confianza
                </p>
            </div>

            <div style="margin-top: 24px; text-align: center; border-top: 1px dashed rgba(212, 175, 55, 0.25); padding-top: 24px;">
                <p style="color: var(--veridi-gold); font-weight: 700; margin: 0 0 12px 0;">¿Qué te pareció tu experiencia?</p>
                <p style="color: var(--veridi-text-secondary); margin: 0 0 18px 0;">¡Valóranos y ayuda a otros clientes!</p>
                <button type="button" id="abrir-modal-valoracion" style="background: linear-gradient(135deg, var(--veridi-gold-dark) 0%, var(--veridi-gold) 100%); color: var(--veridi-black); padding: 12px 28px; border: 0; border-radius: 8px; font-weight: 700; cursor: pointer;">
                    ⭐ Valóranos
                </button>
            </div>

            <?php if ($textoValoracion !== ''): ?>
                <div class="<?php echo $tipoValoracion; ?>" style="margin-top: 18px;">
                    <?php echo htmlspecialchars($textoValoracion); ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- BOTÓN VOLVER A TIENDA -->
        <div style="text-align: center; margin-top: 40px;">
            <a href="tienda.php" style="background: linear-gradient(135deg, var(--veridi-gold-dark) 0%, var(--veridi-gold) 100%); 
                    color: var(--veridi-black); padding: 14px 40px; border-radius: 6px; font-weight: 700; 
                    font-size: 16px; text-decoration: none; display: inline-block; text-transform: uppercase; 
                    letter-spacing: 1px; transition: all 0.3s ease; box-shadow: 0 6px 20px rgba(212, 175, 55, 0.3);">
                ← Volver a la Tienda
            </a>
        </div>
    </div>
</main>

<div id="valoracion-overlay" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.72); z-index: 9998;"></div>
<div id="valoracion-modal" style="display: none; position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); width: min(92vw, 560px); background: var(--veridi-surface); border: 2px solid var(--veridi-gold); border-radius: 12px; padding: 24px; z-index: 9999; box-shadow: 0 12px 40px rgba(0,0,0,0.5);">
    <button type="button" id="cerrar-modal-valoracion" style="position: absolute; top: 12px; right: 14px; background: transparent; border: none; color: var(--veridi-text); font-size: 24px; cursor: pointer;">&times;</button>
    <h2 style="margin: 0 0 8px 0; color: var(--veridi-gold);">Valora tu compra</h2>
    <p style="margin: 0 0 18px 0; color: var(--veridi-text-secondary);">Selecciona de 1 a 5 estrellas y añade un comentario opcional.</p>

    <form method="POST" action="php/valoraciones.php" id="form-valoracion" style="display: flex; flex-direction: column; gap: 16px;">
        <input type="hidden" name="id_pedido" value="<?php echo (int)$id_pedido; ?>">
        <input type="hidden" name="redirect" value="confirmacion_pedido.php?id=<?php echo (int)$id_pedido; ?>">

        <div>
            <label style="display:block; margin-bottom: 8px; color: var(--veridi-text); font-weight: 600;">Estrellas *</label>
            <div id="estrellas-grupo" style="display: flex; gap: 8px; font-size: 30px;">
                <?php for ($star = 1; $star <= 5; $star++): ?>
                    <button type="button" class="star-btn" data-star="<?php echo $star; ?>" style="background: transparent; border: 0; cursor: pointer; color: #6b6b6b; line-height: 1;">★</button>
                <?php endfor; ?>
            </div>
            <input type="hidden" name="estrellas" id="input-estrellas" value="">
            <small id="error-estrellas" style="display:none; color:#ff6b6b;">Debes seleccionar al menos una estrella.</small>
        </div>

        <div>
            <label for="comentario" style="display:block; margin-bottom: 8px; color: var(--veridi-text); font-weight: 600;">Mensaje (opcional)</label>
            <textarea id="comentario" name="comentario" rows="4" maxlength="500" placeholder="Cuéntanos cómo fue tu experiencia" style="width: 100%; padding: 12px; border: 2px solid var(--veridi-gold); border-radius: 8px; background: var(--veridi-dark); color: var(--veridi-text);"></textarea>
        </div>

        <button type="submit" style="background: linear-gradient(135deg, var(--veridi-gold-dark) 0%, var(--veridi-gold) 100%); color: var(--veridi-black); padding: 12px 22px; border: 0; border-radius: 8px; font-weight: 700; cursor: pointer;">
            Enviar valoración
        </button>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const openBtn = document.getElementById('abrir-modal-valoracion');
    const closeBtn = document.getElementById('cerrar-modal-valoracion');
    const overlay = document.getElementById('valoracion-overlay');
    const modal = document.getElementById('valoracion-modal');
    const form = document.getElementById('form-valoracion');
    const inputEstrellas = document.getElementById('input-estrellas');
    const errorEstrellas = document.getElementById('error-estrellas');
    const starButtons = document.querySelectorAll('.star-btn');

    if (!openBtn || !closeBtn || !overlay || !modal || !form || !inputEstrellas) {
        return;
    }

    function setStars(value) {
        inputEstrellas.value = value;
        starButtons.forEach(function (button) {
            const starValue = Number(button.getAttribute('data-star'));
            button.style.color = starValue <= value ? 'var(--veridi-gold)' : '#6b6b6b';
        });
        errorEstrellas.style.display = 'none';
    }

    function openModal() {
        overlay.style.display = 'block';
        modal.style.display = 'block';
    }

    function closeModal() {
        overlay.style.display = 'none';
        modal.style.display = 'none';
    }

    openBtn.addEventListener('click', openModal);
    closeBtn.addEventListener('click', closeModal);
    overlay.addEventListener('click', closeModal);

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            closeModal();
        }
    });

    starButtons.forEach(function (button) {
        button.addEventListener('click', function () {
            const starValue = Number(button.getAttribute('data-star'));
            setStars(starValue);
        });
    });

    form.addEventListener('submit', function (event) {
        if (!inputEstrellas.value || Number(inputEstrellas.value) < 1 || Number(inputEstrellas.value) > 5) {
            event.preventDefault();
            errorEstrellas.style.display = 'block';
        }
    });
});
</script>

<?php require_once "includes/footer.php"; ?>
