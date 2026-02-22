<?php
require_once "config/conexion.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ========== VERIFICACIÓN: Usuario debe estar loqueado para ver confirmación ==========
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}

$page_title = "Confirmación de Pedido";
require_once "includes/header.php";

// Obtener ID del pedido
$id_pedido = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id_pedido <= 0) {
    header("Location: tienda.php");
    exit;
}

// Obtener datos del pedido
$stmt = $conexion->prepare("
    SELECT p.id_pedido, p.total, p.fecha, p.estado, p.direccion, u.nombre, u.email
    FROM pedidos p
    LEFT JOIN usuarios u ON p.id_usuario = u.id_usuario
    WHERE p.id_pedido = :id
");
$stmt->bindParam(':id', $id_pedido, PDO::PARAM_INT);
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

<?php require_once "includes/footer.php"; ?>
