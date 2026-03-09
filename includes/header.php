<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$authOpen = isset($_GET['auth_open']) && $_GET['auth_open'] === '1';
$authTab = (isset($_GET['auth_tab']) && $_GET['auth_tab'] === 'register') ? 'register' : 'login';
$authError = $_GET['auth_error'] ?? '';
$authSuccess = $_GET['auth_success'] ?? '';

$authMessage = '';
$authMessageType = '';

if ($authError !== '') {
    $authMessageType = 'error';
    if ($authError === 'credenciales') {
        $authMessage = 'Email o contraseña incorrectos.';
    } elseif ($authError === 'faltan_campos') {
        $authMessage = 'Completa todos los campos.';
    } elseif ($authError === 'password_corta') {
        $authMessage = 'La contraseña debe tener al menos 6 caracteres.';
    } elseif ($authError === 'password_no_coincide') {
        $authMessage = 'Las contraseñas no coinciden.';
    } elseif ($authError === 'email_existente') {
        $authMessage = 'Este email ya está registrado.';
    } elseif ($authError === 'requiere_login') {
        $authMessage = 'Debes iniciar sesión para acceder a esta sección.';
    } else {
        $authMessage = 'Ha ocurrido un error. Inténtalo de nuevo.';
    }
}

if ($authSuccess !== '') {
    $authMessageType = 'success';
    if ($authSuccess === 'registro') {
        $authMessage = 'Registro completado. Ahora puedes iniciar sesión.';
    }
}

$currentPage = basename(parse_url($_SERVER['REQUEST_URI'] ?? 'index.php', PHP_URL_PATH));
if ($currentPage === '') {
    $currentPage = 'index.php';
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
                    <div class="auth-inline-wrapper" data-auth-default-tab="<?php echo htmlspecialchars($authTab); ?>" data-auth-auto-open="<?php echo $authOpen ? '1' : '0'; ?>">
                        <div class="auth-actions">
                            <button type="button" class="nav-link auth-btn auth-open-btn" data-auth-open-tab="login" title="Iniciar sesión">Iniciar sesión</button>
                            <button type="button" class="nav-link auth-btn auth-open-btn" data-auth-open-tab="register" title="Registrarse">Registrarse</button>
                        </div>

                        <div class="auth-panel<?php echo $authOpen ? ' open' : ''; ?>" aria-hidden="<?php echo $authOpen ? 'false' : 'true'; ?>">
                            <div class="auth-tabs" role="tablist" aria-label="Autenticación">
                                <button type="button" class="auth-tab-btn<?php echo $authTab === 'login' ? ' active' : ''; ?>" data-auth-tab="login">Entrar</button>
                                <button type="button" class="auth-tab-btn<?php echo $authTab === 'register' ? ' active' : ''; ?>" data-auth-tab="register">Registrarse</button>
                            </div>

                            <?php if ($authMessage !== ''): ?>
                                <div class="auth-inline-message <?php echo $authMessageType === 'error' ? 'error-message' : 'success-message'; ?>"><?php echo htmlspecialchars($authMessage); ?></div>
                            <?php endif; ?>

                            <form action="php/auth.php" method="POST" class="auth-inline-form auth-form-login<?php echo $authTab === 'login' ? ' active' : ''; ?>" data-auth-form="login">
                                <input type="hidden" name="redirect" value="<?php echo htmlspecialchars($currentPage); ?>">
                                <label for="header-login-email">Email</label>
                                <input type="email" id="header-login-email" name="email" placeholder="tu@email.com" required>

                                <label for="header-login-password">Contraseña</label>
                                <input type="password" id="header-login-password" name="password" placeholder="Contraseña" required>

                                <button type="submit" name="login">Iniciar sesión</button>
                            </form>

                            <form action="php/auth.php" method="POST" class="auth-inline-form auth-form-register<?php echo $authTab === 'register' ? ' active' : ''; ?>" data-auth-form="register">
                                <input type="hidden" name="redirect" value="<?php echo htmlspecialchars($currentPage); ?>">
                                <label for="header-register-nombre">Nombre</label>
                                <input type="text" id="header-register-nombre" name="nombre" placeholder="Tu nombre" required>

                                <label for="header-register-email">Email</label>
                                <input type="email" id="header-register-email" name="email" placeholder="tu@email.com" required>

                                <label for="header-register-password">Contraseña</label>
                                <input type="password" id="header-register-password" name="password" placeholder="Mínimo 6 caracteres" required>

                                <label for="header-register-password-confirm">Confirmar contraseña</label>
                                <input type="password" id="header-register-password-confirm" name="password_confirm" placeholder="Repite tu contraseña" required>

                                <button type="submit" name="registro">Crear cuenta</button>
                            </form>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</header>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const wrapper = document.querySelector('.auth-inline-wrapper');
    if (!wrapper) {
        return;
    }

    const openButtons = wrapper.querySelectorAll('.auth-open-btn');
    const panel = wrapper.querySelector('.auth-panel');
    const tabButtons = wrapper.querySelectorAll('.auth-tab-btn');
    const forms = wrapper.querySelectorAll('.auth-inline-form');
    const defaultTab = wrapper.getAttribute('data-auth-default-tab') === 'register' ? 'register' : 'login';
    const autoOpen = wrapper.getAttribute('data-auth-auto-open') === '1';

    function setTab(tab) {
        tabButtons.forEach(function(button) {
            button.classList.toggle('active', button.getAttribute('data-auth-tab') === tab);
        });

        forms.forEach(function(form) {
            form.classList.toggle('active', form.getAttribute('data-auth-form') === tab);
        });
    }

    function openPanel() {
        panel.classList.add('open');
        panel.setAttribute('aria-hidden', 'false');
    }

    function closePanel() {
        panel.classList.remove('open');
        panel.setAttribute('aria-hidden', 'true');
    }

    setTab(defaultTab);

    if (autoOpen) {
        openButtons.forEach(function(button) {
            const matchesTab = button.getAttribute('data-auth-open-tab') === defaultTab;
            button.classList.toggle('active', matchesTab);
        });
        openPanel();
    }

    openButtons.forEach(function(button) {
        button.addEventListener('click', function() {
            const tabToOpen = button.getAttribute('data-auth-open-tab') === 'register' ? 'register' : 'login';

            if (panel.classList.contains('open') && button.classList.contains('active')) {
                closePanel();
                button.classList.remove('active');
                return;
            }

            openButtons.forEach(function(btn) {
                btn.classList.remove('active');
            });

            button.classList.add('active');
            setTab(tabToOpen);
            openPanel();
        });
    });

    tabButtons.forEach(function(button) {
        button.addEventListener('click', function() {
            setTab(button.getAttribute('data-auth-tab'));
            openPanel();
        });
    });

    document.addEventListener('click', function(event) {
        if (!wrapper.contains(event.target)) {
            closePanel();
            openButtons.forEach(function(button) {
                button.classList.remove('active');
            });
        }
    });

    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            closePanel();
            openButtons.forEach(function(button) {
                button.classList.remove('active');
            });
        }
    });
});
</script>
