<?php session_start(); ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro - Veridi</title>
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
        <h2>Registro de Usuario</h2>

        <?php if (!empty($_GET['error'])): ?>
            <div class="error-message">
                <?php
                if ($_GET['error'] === 'email_existente') {
                    echo 'Este email ya está registrado.';
                } elseif ($_GET['error'] === 'password_corta') {
                    echo 'La contraseña debe tener al menos 6 caracteres.';
                } elseif ($_GET['error'] === 'password_no_coincide') {
                    echo 'Las contraseñas no coinciden.';
                } elseif ($_GET['error'] === 'faltan_campos') {
                    echo 'Completa todos los campos.';
                } else {
                    echo 'Ha ocurrido un error al registrarte.';
                }
                ?>
            </div>
        <?php endif; ?>
        
        <form action="php/auth.php" method="POST">
            <label for="nombre">Nombre:</label>
            <input type="text" id="nombre" name="nombre" placeholder="Tu nombre completo" required>
            
            <label for="email">Email:</label>
            <input type="email" id="email" name="email" placeholder="tu@email.com" required>
            
            <label for="password">Contraseña:</label>
            <input type="password" id="password" name="password" placeholder="Mínimo 6 caracteres" required>

            <label for="password_confirm">Confirmar contraseña:</label>
            <input type="password" id="password_confirm" name="password_confirm" placeholder="Repite tu contraseña" required>

            <p id="password-help" class="input-help">La contraseña debe tener al menos 6 caracteres.</p>
            <p id="password-match" class="input-help">Las contraseñas deben coincidir.</p>
            
            <button type="submit" name="registro" id="btn-registro">Registrarse</button>
        </form>
        
        <div class="link">
            ¿Ya tienes cuenta? <a href="login.php">Inicia sesión aquí</a>
        </div>
    </div>
</div>

<script>
const passwordInput = document.getElementById('password');
const passwordConfirmInput = document.getElementById('password_confirm');
const passwordHelp = document.getElementById('password-help');
const passwordMatch = document.getElementById('password-match');
const btnRegistro = document.getElementById('btn-registro');

function validarRegistro() {
    const password = passwordInput.value;
    const confirmPassword = passwordConfirmInput.value;

    const longitudOk = password.length >= 6;
    const coincide = password !== '' && password === confirmPassword;

    passwordHelp.classList.toggle('ok', longitudOk);
    passwordHelp.classList.toggle('error', !longitudOk && password.length > 0);

    passwordMatch.classList.toggle('ok', coincide);
    passwordMatch.classList.toggle('error', confirmPassword.length > 0 && !coincide);

    btnRegistro.disabled = !(longitudOk && coincide);
}

passwordInput.addEventListener('input', validarRegistro);
passwordConfirmInput.addEventListener('input', validarRegistro);
validarRegistro();
</script>

</body>
</html>
