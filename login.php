<?php
$page_title = "Iniciar Sesión";
require_once "includes/header.php";
?>

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

        <label class="checkbox-row" for="remember">
            <input type="checkbox" id="remember" name="remember" value="1">
            Recordarme en este dispositivo
        </label>
        
        <button type="submit" name="login">Iniciar Sesión</button>
    </form>
    
    <div class="link">
        ¿No tienes cuenta? <a href="registro.php">Regístrate aquí</a>
    </div>
</div>

<?php require_once "includes/footer.php"; ?>
