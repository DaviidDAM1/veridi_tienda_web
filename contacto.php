<?php
require_once "config/conexion.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$page_title = "Contacto";
require_once "includes/header.php";

// Correo de la web (puedes cambiar esto)
$emailWeb = "info@veridi.com";

$mensajeExito = "";
$error = "";
$emailUsuario = "";
$nombreUsuario = "";
$passwordUsuario = "";

// Obtener email, nombre y contraseña del usuario logueado
if (isset($_SESSION['usuario_id'])) {
    $stmt = $conexion->prepare("SELECT email, nombre, password FROM usuarios WHERE id_usuario = ?");
    $stmt->execute([$_SESSION['usuario_id']]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
    $emailUsuario = $usuario ? $usuario['email'] : "";
    $nombreUsuario = $usuario ? $usuario['nombre'] : "";
    $passwordUsuario = $usuario ? $usuario['password'] : "";
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // ========== VERIFICACIÓN: Usuario debe estar loqueado para enviar mensajes ==========
    if (!isset($_SESSION['usuario_id'])) {
        $error = "❌ Debes iniciar sesión para enviar un mensaje. <a href='login.php' style='color: var(--veridi-gold);'>Inicia sesión aquí</a>";
    } else {
        $email = trim($_POST["email"] ?? "");
        $tipo = $_POST["tipo"] ?? "";
        $mensaje = trim($_POST["mensaje"] ?? "");
        $contrasena = trim($_POST["contrasena"] ?? "");

        // VALIDACIÓN: El email debe coincidir con el del usuario logueado
        if ($email !== $emailUsuario) {
            $error = "❌ El email ingresado no coincide con tu email de cuenta (" . htmlspecialchars($emailUsuario) . "). Por seguridad, debes usar el email asociado a tu cuenta.";
        } elseif (empty($email) || empty($tipo) || empty($mensaje) || empty($contrasena)) {
            $error = "❌ Todos los campos son obligatorios.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "❌ El email no es válido.";
        } elseif (strlen($contrasena) < 6) {
            $error = "❌ La contraseña debe tener al menos 6 caracteres.";
        } elseif (!password_verify($contrasena, $passwordUsuario)) {
            // Verificar contraseña contra su hash
            $error = "❌ La contraseña ingresada es incorrecta.";
        } else {
            // Todo OK, insertar en la BD
            $nombre = trim($_POST['nombre'] ?? '');
            
            if (empty($nombre)) {
                $error = "❌ El nombre es requerido.";
            } else {
                $stmt = $conexion->prepare("INSERT INTO contacto (nombre, email, asunto, mensaje, contrasena) VALUES (?, ?, ?, ?, ?)");
                
                if ($stmt->execute([$nombre, $email, $tipo, $mensaje, $contrasena])) {
                    $mensajeExito = "✓ Mensaje enviado correctamente. Te responderemos pronto a " . htmlspecialchars($email);
                } else {
                    $error = "❌ Hubo un error al enviar el mensaje. Intenta de nuevo.";
                }
            }
        }
    }
}
?>

<main>
    <section class="contacto-page">
        <!-- Texto introductorio -->
        <div class="contacto-intro">
            <h1>Contacto</h1>
            <p class="intro-text">
                Bienvenido a Veridi. Nos encantaría saber de ti. Si tienes alguna pregunta, sugerencia o necesitas ayuda, 
                no dudes en ponerte en contacto con nosotros. Nuestro equipo está aquí para ayudarte.
            </p>
            <p class="email-info">
                <strong>📧 Email de contacto:</strong> <a href="mailto:<?php echo $emailWeb; ?>" class="email-link"><?php echo $emailWeb; ?></a>
            </p>
        </div>

        <!-- Mensajes de éxito/error -->
        <?php if ($mensajeExito): ?>
            <div class="success-message"><?php echo htmlspecialchars($mensajeExito); ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <!-- Formulario de contacto -->
        <div class="contacto-container">
            <div class="form-wrapper">
                <h2>Enviar un mensaje</h2>
                
                <?php if (!isset($_SESSION['usuario_id'])): ?>
                    <div class="info-message" style="background: rgba(212, 175, 55, 0.1); border: 2px solid var(--veridi-gold); padding: 15px; border-radius: 8px; margin-bottom: 20px; text-align: center;">
                        🔒 <strong>Debes iniciar sesión para enviar mensajes.</strong><br>
                        <a href="login.php" style="color: var(--veridi-gold); text-decoration: underline; font-weight: 600;">Inicia sesión aquí</a> o 
                        <a href="registro.php" style="color: var(--veridi-gold); text-decoration: underline; font-weight: 600;">Regístrate</a>
                    </div>
                <?php endif; ?>

                <form method="POST" action="contacto.php" class="form-contacto">

                    <div class="form-group">
                        <label for="nombre">Nombre <span class="required">*</span></label>
                        <?php if (isset($_SESSION['usuario_id']) && $nombreUsuario): ?>
                            <input type="text" id="nombre" name="nombre" value="<?php echo htmlspecialchars($nombreUsuario); ?>" readonly class="email-readonly">
                            <small class="form-info">Tu nombre de cuenta</small>
                        <?php else: ?>
                            <input type="text" id="nombre" name="nombre" placeholder="Debes iniciar sesión" disabled style="opacity: 0.5; cursor: not-allowed;">
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label for="email">Email <span class="required">*</span></label>
                        <?php if (isset($_SESSION['usuario_id']) && $emailUsuario): ?>
                            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($emailUsuario); ?>" readonly class="email-readonly">
                            <small class="form-info">Este es tu email de cuenta verificado</small>
                        <?php else: ?>
                            <input type="email" id="email" name="email" placeholder="Debes iniciar sesión" disabled style="opacity: 0.5; cursor: not-allowed;">
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label for="contrasena">Contraseña de tu correo <span class="required">*</span></label>
                        <input type="password" id="contrasena" name="contrasena" placeholder="<?php echo isset($_SESSION['usuario_id']) ? 'Ingresa tu contraseña' : 'Debes iniciar sesión'; ?>" <?php echo !isset($_SESSION['usuario_id']) ? 'disabled style="opacity: 0.5; cursor: not-allowed;"' : 'required'; ?>>
                        <?php if (isset($_SESSION['usuario_id'])): ?>
                            <small class="form-info">Mínimo 6 caracteres</small>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label for="tipo">Tipo de asunto <span class="required">*</span></label>
                        <select id="tipo" name="tipo" <?php echo !isset($_SESSION['usuario_id']) ? 'disabled style="opacity: 0.5; cursor: not-allowed;"' : 'required'; ?>>
                            <option value="">-- <?php echo isset($_SESSION['usuario_id']) ? 'Selecciona un asunto' : 'Debes iniciar sesión'; ?> --</option>
                            <?php if (isset($_SESSION['usuario_id'])): ?>
                                <option value="consulta">Consulta</option>
                                <option value="queja">Queja</option>
                                <option value="reclamacion">Reclamación</option>
                                <option value="otro">Otro</option>
                            <?php endif; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="mensaje">Mensaje <span class="required">*</span></label>
                        <textarea id="mensaje" name="mensaje" rows="6" placeholder="<?php echo isset($_SESSION['usuario_id']) ? 'Escribe tu mensaje aquí...' : 'Debes iniciar sesión para enviar mensajes'; ?>" <?php echo !isset($_SESSION['usuario_id']) ? 'disabled style="opacity: 0.5; cursor: not-allowed;"' : 'required'; ?>></textarea>
                    </div>

                    <button type="submit" class="btn-enviar" <?php echo !isset($_SESSION['usuario_id']) ? 'disabled style="opacity: 0.5; cursor: not-allowed; background: #666;"' : ''; ?>>
                        <?php echo isset($_SESSION['usuario_id']) ? 'Enviar mensaje' : '🔒 Inicia sesión para enviar'; ?>
                    </button>
                </form>
            </div>
        </div>
    </section>
</main>

<?php require_once "includes/footer.php"; ?>

