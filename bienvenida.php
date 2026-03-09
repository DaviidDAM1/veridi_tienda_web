<?php
$page_title = "Bienvenida";
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bienvenida - Veridi</title>
    <?php $cssVersion = @filemtime(__DIR__ . '/css/styles.css') ?: time(); ?>
    <link rel="stylesheet" href="css/styles.css?v=<?php echo $cssVersion; ?>">

    <script>
        (function() {
            const savedTheme = localStorage.getItem('veridi-theme') || 'dark';
            document.documentElement.setAttribute('data-theme', savedTheme);
        })();
    </script>
</head>
<body>
<main class="welcome-main">
    <section class="welcome-card">
        <img src="img/Logo.png" alt="Veridi Logo" class="welcome-logo">
        <h1>Bienvenido a Veridi</h1>
        <p>Moda masculina exclusiva con estilo, calidad y personalidad.</p>
        <a href="index.php" class="btn-productos">Entrar</a>
    </section>
</main>
</body>
</html>
