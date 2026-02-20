<?php
require_once "config/conexion.php";
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
    $email = trim($_POST["email"] ?? "");
    $tipo = $_POST["tipo"] ?? "";
    $mensaje = trim($_POST["mensaje"] ?? "");
    $contrasena = trim($_POST["contrasena"] ?? "");

    // VALIDACIÓN: Si está logueado, el email debe coincidir
    if (isset($_SESSION['usuario_id']) && $email !== $emailUsuario) {
        $error = "❌ El email ingresado no coincide con tu email de cuenta (" . htmlspecialchars($emailUsuario) . "). Por seguridad, debes usar el email asociado a tu cuenta.";
    } elseif (empty($email) || empty($tipo) || empty($mensaje) || empty($contrasena)) {
        $error = "❌ Todos los campos son obligatorios.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "❌ El email no es válido.";
    } elseif (strlen($contrasena) < 6) {
        $error = "❌ La contraseña debe tener al menos 6 caracteres.";
    } else {
        // VALIDACIÓN: Verificar que la contraseña sea correcta
        if (isset($_SESSION['usuario_id'])) {
            // Usuario logueado: verificar contraseña contra su hash
            if (!password_verify($contrasena, $passwordUsuario)) {
                $error = "❌ La contraseña ingresada es incorrecta.";
            }
        } else {
            // Usuario no logueado: obtener usuario del email y verificar contraseña
            $stmtCheckUser = $conexion->prepare("SELECT password FROM usuarios WHERE email = ?");
            $stmtCheckUser->execute([$email]);
            $usuarioCheck = $stmtCheckUser->fetch(PDO::FETCH_ASSOC);
            
            if (!$usuarioCheck || !password_verify($contrasena, $usuarioCheck['password'])) {
                $error = "❌ El email o la contraseña ingresada es incorrecta.";
            }
        }

        if (!$error) {
            $nombre = trim($_POST['nombre'] ?? '');
            
            if (empty($nombre)) {
                $error = "❌ El nombre es requerido.";
            } else {
                $id_usuario = isset($_SESSION['usuario_id']) ? $_SESSION['usuario_id'] : null;

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
                    <div class="info-message">
                        ℹ️ <strong>Nota:</strong> <a href="login.php">Inicia sesión</a> para enviar un mensaje con tu cuenta verificada.
                    </div>
                <?php endif; ?>

                <form method="POST" action="contacto.php" class="form-contacto">

                    <div class="form-group">
                        <label for="nombre">Nombre <span class="required">*</span></label>
                        <?php if (isset($_SESSION['usuario_id']) && $nombreUsuario): ?>
                            <input type="text" id="nombre" name="nombre" value="<?php echo htmlspecialchars($nombreUsuario); ?>" readonly class="email-readonly">
                            <small class="form-info">Tu nombre de cuenta</small>
                        <?php else: ?>
                            <input type="text" id="nombre" name="nombre" placeholder="Tu nombre y apellido" required>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label for="email">Email <span class="required">*</span></label>
                        <?php if (isset($_SESSION['usuario_id']) && $emailUsuario): ?>
                            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($emailUsuario); ?>" readonly class="email-readonly">
                            <small class="form-info">Este es tu email de cuenta verificado</small>
                        <?php else: ?>
                            <input type="email" id="email" name="email" placeholder="tu@email.com" required>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label for="contrasena">Contraseña de tu correo <span class="required">*</span></label>
                        <input type="password" id="contrasena" name="contrasena" placeholder="Ingresa tu contraseña" required>
                        <small class="form-info">Mínimo 6 caracteres</small>
                    </div>

                    <div class="form-group">
                        <label for="tipo">Tipo de asunto <span class="required">*</span></label>
                        <select id="tipo" name="tipo" required>
                            <option value="">-- Selecciona un asunto --</option>
                            <option value="consulta">Consulta</option>
                            <option value="queja">Queja</option>
                            <option value="reclamacion">Reclamación</option>
                            <option value="otro">Otro</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="mensaje">Mensaje <span class="required">*</span></label>
                        <textarea id="mensaje" name="mensaje" rows="6" placeholder="Escribe tu mensaje aquí..." required></textarea>
                    </div>

                    <button type="submit" class="btn-enviar">Enviar mensaje</button>
                </form>
            </div>
        </div>
    </section>
</main>

<?php require_once "includes/footer.php"; ?>

