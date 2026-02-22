<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - Veridi' : 'Veridi - Tienda de ropa'; ?></title>
    <?php $cssVersion = @filemtime(__DIR__ . '/../css/styles.css') ?: time(); ?>
    <link rel="stylesheet" href="css/styles.css?v=<?php echo $cssVersion; ?>">
    
    <!-- Script para cargar tema guardado inmediatamente -->
    <script>
        (function() {
            const savedTheme = localStorage.getItem('veridi-theme') || 'dark';
            document.documentElement.setAttribute('data-theme', savedTheme);
        })();
    </script>
</head>
<body>

<header>
    <div class="header-container">
        <!-- IZQUIERDA: LOGO -->
        <div class="header-left">
            <div class="logo">
                <a href="index.php" title="Volver a inicio">
                    <img src="img/Logo.png" alt="Veridi Logo" class="logo-img">
                </a>
            </div>
        </div>
        
        <!-- CENTRO: NAVEGACIÓN -->
        <div class="header-center">
            <nav class="nav-principal">
                <a href="index.php" class="nav-link nav-main">Inicio</a>
                <a href="tienda.php" class="nav-link nav-main">Catálogo</a>
                <a href="contacto.php" class="nav-link nav-main">Contacto</a>
                <a href="sobre-nosotros.php" class="nav-link nav-main">Sobre nosotros</a>
            </nav>
        </div>
        
        <!-- DERECHA: USUARIO Y ACCIONES -->
        <div class="header-right">
            <div class="user-section">
                <?php if (isset($_SESSION['usuario_id'])): ?>
                    <span class="user-greeting">Bienvenido, <span class="user-name-value"><?php echo htmlspecialchars(strlen($_SESSION['usuario_nombre']) > 15 ? substr($_SESSION['usuario_nombre'], 0, 15) . '...' : $_SESSION['usuario_nombre']); ?></span></span>
                    
                    <a href="carrito.php" class="icon-button carrito-btn" title="Ver carrito" aria-label="Ir al carrito">
                        <span class="icon">🛒</span>
                    </a>
                    
                    <a href="logout.php" class="nav-link logout-btn" title="Cerrar sesión">Cerrar sesión</a>
                <?php else: ?>
                    <span class="user-greeting">Bienvenido, <span class="user-name-value">usuario</span></span>
                    
                    <a href="login.php" class="nav-link auth-btn" title="Iniciar sesión">Iniciar sesión</a>
                    <a href="registro.php" class="nav-link auth-btn" title="Registrarse">Registrarse</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</header>
