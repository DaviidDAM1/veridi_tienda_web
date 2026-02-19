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
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>

<header>
    <div class="header-container">
        <div class="logo">
            <a href="index.php"><h1>👕 VERIDI</h1></a>
        </div>
        
        <div class="nav-center">
            <a href="tienda.php" class="btn-productos">Productos</a>
            <a href="contacto.php" class="btn-productos">Contacto</a>
            <a href="sobre-nosotros.php" class="btn-productos">Sobre nosotros</a>
        </div>
        
        <div class="user-area">
            <?php if (isset($_SESSION['usuario_id'])): ?>
                <span class="user-name">Bienvenido, <?php echo htmlspecialchars($_SESSION['usuario_nombre']); ?></span>
                <a href="logout.php" class="nav-link">Cerrar sesión</a>
            <?php else: ?>
                <span class="user-name">Bienvenido, usuario</span>
                <a href="login.php" class="nav-link">Iniciar sesión</a>
            <?php endif; ?>
        </div>
    </div>
</header>
