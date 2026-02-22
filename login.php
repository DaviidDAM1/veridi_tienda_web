<?php session_start(); ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - Veridi</title>
    <?php $cssVersion = @filemtime(__DIR__ . '/css/styles.css') ?: time(); ?>
    <link rel="stylesheet" href="css/styles.css?v=<?php echo $cssVersion; ?>">
</head>
<body>

<div class="auth-page">
    <div class="auth-header">
        <a href="index.php">
            <img src="img/Logo.png" alt="Veridi Logo" class="auth-logo">
        </a>
    </div>

    <div class="form-container">
        <h2>Iniciar Sesión</h2>

        <?php if (!empty($_GET['success']) && $_GET['success'] === 'registro'): ?>
            <div class="success-message">Registro completado. Ahora puedes iniciar sesión.</div>
        <?php endif; ?>

        <?php if (!empty($_GET['error'])): ?>
            <div class="error-message">
                <?php
                if ($_GET['error'] === 'credenciales') {
                    echo 'Email o contraseña incorrectos.';
                } elseif ($_GET['error'] === 'faltan_campos') {
                    echo 'Completa todos los campos.';
                } else {
                    echo 'Ha ocurrido un error al iniciar sesión.';
                }
                ?>
            </div>
        <?php endif; ?>
        
        <form action="php/auth.php" method="POST">
            <label for="email">Email:</label>
            <input type="email" id="email" name="email" placeholder="tu@email.com" required>
            
            <label for="password">Contraseña:</label>
            <input type="password" id="password" name="password" placeholder="Contraseña" required>
            
            <button type="submit" name="login">Iniciar Sesión</button>
        </form>
        
        <div class="link">
            ¿No tienes cuenta? <a href="registro.php">Regístrate aquí</a>
        </div>
        
        <div class="link" style="margin-top: 15px; padding-top: 15px; border-top: 1px solid rgba(212, 175, 55, 0.3);">
            <a href="index.php" style="color: var(--veridi-text-muted);">Continuar sin iniciar sesión</a>
        </div>
    </div>
</div>

</body>
</html>
