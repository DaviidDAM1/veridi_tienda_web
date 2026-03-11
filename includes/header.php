<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$authOpen = isset($_GET['auth_open']) && $_GET['auth_open'] === '1';
$authTab = (isset($_GET['auth_tab']) && $_GET['auth_tab'] === 'register') ? 'register' : 'login';
$authError = $_GET['auth_error'] ?? '';
$authSuccess = $_GET['auth_success'] ?? '';
$profileMsg = $_GET['profile_msg'] ?? '';
$profileOpen = isset($_GET['profile_open']) && $_GET['profile_open'] === '1';

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

$profileMessageText = '';
$profileMessageType = '';

if ($profileMsg !== '') {
    if ($profileMsg === 'ok') {
        $profileMessageText = 'Perfil actualizado correctamente.';
        $profileMessageType = 'success';
    } elseif ($profileMsg === 'nombre_invalido') {
        $profileMessageText = 'El nombre no es válido (1-100 caracteres).';
        $profileMessageType = 'error';
    } elseif ($profileMsg === 'foto_peso') {
        $profileMessageText = 'La imagen supera el tamaño máximo (5MB).';
        $profileMessageType = 'error';
    } elseif ($profileMsg === 'foto_tipo') {
        $profileMessageText = 'Formato de imagen no permitido (solo JPG, PNG o WEBP).';
        $profileMessageType = 'error';
    } else {
        $profileMessageText = 'No se pudo actualizar el perfil.';
        $profileMessageType = 'error';
    }
}

$perfilUsuario = null;
$historialPedidos = [];
$valoracionesUsuario = [];

if (isset($_SESSION['usuario_id'])) {
    $idUsuarioSesion = (int)$_SESSION['usuario_id'];

    if (isset($conexion) && $conexion instanceof PDO) {
        try {
            $conexion->exec("ALTER TABLE usuarios ADD COLUMN foto_perfil VARCHAR(255) NULL AFTER password");
        } catch (Exception $e) {
        }

        try {
            $stmtPerfil = $conexion->prepare("SELECT nombre, email, password, foto_perfil FROM usuarios WHERE id_usuario = :id_usuario LIMIT 1");
            $stmtPerfil->bindParam(':id_usuario', $idUsuarioSesion, PDO::PARAM_INT);
            $stmtPerfil->execute();
            $perfilUsuario = $stmtPerfil->fetch(PDO::FETCH_ASSOC) ?: null;

            $stmtPedidos = $conexion->prepare("SELECT id_pedido, total, estado, fecha FROM pedidos WHERE id_usuario = :id_usuario ORDER BY fecha DESC LIMIT 10");
            $stmtPedidos->bindParam(':id_usuario', $idUsuarioSesion, PDO::PARAM_INT);
            $stmtPedidos->execute();
            $historialPedidos = $stmtPedidos->fetchAll(PDO::FETCH_ASSOC);

            $stmtValoraciones = $conexion->prepare("SELECT v.estrellas, v.comentario, v.fecha, v.id_pedido FROM valoraciones v WHERE v.id_usuario = :id_usuario ORDER BY v.fecha DESC LIMIT 10");
            $stmtValoraciones->bindParam(':id_usuario', $idUsuarioSesion, PDO::PARAM_INT);
            $stmtValoraciones->execute();
            $valoracionesUsuario = $stmtValoraciones->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $perfilUsuario = $perfilUsuario ?? [
                'nombre' => $_SESSION['usuario_nombre'] ?? 'Usuario',
                'email' => '',
                'password' => ''
            ];
            $historialPedidos = [];
            $valoracionesUsuario = [];
        }
    }

    if (!$perfilUsuario) {
        $perfilUsuario = [
            'nombre' => $_SESSION['usuario_nombre'] ?? 'Usuario',
            'email' => '',
            'password' => '',
            'foto_perfil' => ''
        ];
    }
}

$fotoPerfilUsuario = trim((string)($perfilUsuario['foto_perfil'] ?? ''));
if ($fotoPerfilUsuario === '') {
    $fotoPerfilUsuario = 'img/user-default.svg';
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
                <a href="valoraciones.php" class="nav-link nav-main">Valoraciones</a>
                <?php if (isset($_SESSION['usuario_rol']) && $_SESSION['usuario_rol'] === 'admin'): ?>
                    <a href="panel-admin.php" class="nav-link nav-main">PANEL DE ADMINISTRADOR</a>
                <?php endif; ?>
            </nav>
        </div>
        
        <!-- DERECHA: USUARIO Y ACCIONES -->
        <div class="header-right">
            <div class="user-section">
                <?php if (isset($_SESSION['usuario_id'])): ?>
                    <span class="user-greeting">Bienvenido, <span class="user-name-value"><?php echo htmlspecialchars(strlen($_SESSION['usuario_nombre']) > 15 ? substr($_SESSION['usuario_nombre'], 0, 15) . '...' : $_SESSION['usuario_nombre']); ?></span></span>

                    <button type="button" class="icon-button profile-btn" id="open-profile-modal" title="Área personal" aria-label="Abrir área personal">
                        <span class="icon">👤</span>
                    </button>
                    
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

<?php if (isset($_SESSION['usuario_id']) && $perfilUsuario): ?>
    <div class="profile-modal-overlay" id="profile-modal-overlay"></div>
    <div class="profile-modal" id="profile-modal" role="dialog" aria-modal="true" aria-labelledby="profile-modal-title" aria-hidden="true">
        <button type="button" class="profile-modal-close" id="close-profile-modal" aria-label="Cerrar área personal">&times;</button>

        <div class="profile-header-block">
            <button type="button" class="profile-avatar-trigger" id="profile-avatar-trigger" title="Gestionar foto de perfil">
                <img src="<?php echo htmlspecialchars($fotoPerfilUsuario); ?>" alt="Foto de perfil" class="profile-avatar">
            </button>
            <div>
                <h2 id="profile-modal-title">Área personal</h2>
                <p class="profile-subtitle">Tu información y actividad en Veridi</p>
            </div>
        </div>

        <?php if ($profileMessageText !== ''): ?>
            <div class="profile-message <?php echo $profileMessageType === 'success' ? 'profile-message-success' : 'profile-message-error'; ?>">
                <?php echo htmlspecialchars($profileMessageText); ?>
            </div>
        <?php endif; ?>

        <div class="profile-actions-wrap">
            <div class="profile-actions-card" id="photo-actions" style="display:none;">
                <div class="profile-actions-head">
                    <p class="profile-actions-title">Foto de perfil</p>
                    <button type="button" class="profile-actions-close" id="close-photo-actions" aria-label="Cerrar opciones de foto">&times;</button>
                </div>
                <div class="profile-actions-buttons">
                    <button type="button" id="btn-view-photo" class="profile-small-btn">Ver foto de perfil</button>
                    <button type="button" id="btn-edit-photo" class="profile-small-btn">Modificar foto de perfil</button>
                </div>
            </div>
        </div>

        <form action="php/actualizar_perfil.php" method="POST" enctype="multipart/form-data" class="profile-edit-form" id="photo-edit-form" style="display:none;">
            <input type="hidden" name="redirect" value="<?php echo htmlspecialchars($currentPage); ?>">
            <input type="hidden" name="nombre" value="<?php echo htmlspecialchars($perfilUsuario['nombre'] ?? ($_SESSION['usuario_nombre'] ?? '')); ?>">

            <div class="profile-actions-head">
                <p class="profile-actions-title">Modificar foto de perfil</p>
                <button type="button" class="profile-actions-close" id="close-photo-edit" aria-label="Cerrar edición de foto">&times;</button>
            </div>

            <div class="profile-edit-grid">
                <div class="profile-edit-field">
                    <label for="perfil-foto">Foto de perfil (JPG, PNG, WEBP · máx 5MB)</label>
                    <input type="file" id="perfil-foto" name="foto_perfil" accept="image/jpeg,image/png,image/webp" required>
                </div>
            </div>

            <button type="submit" class="profile-save-btn">Guardar foto</button>
        </form>

        <form action="php/actualizar_perfil.php" method="POST" enctype="multipart/form-data" class="profile-edit-form" id="name-edit-form" style="display:none;">
            <input type="hidden" name="redirect" value="<?php echo htmlspecialchars($currentPage); ?>">

            <div class="profile-edit-grid">
                <div class="profile-edit-field">
                    <label for="perfil-nombre">Nombre de usuario</label>
                    <input type="text" id="perfil-nombre" name="nombre" value="<?php echo htmlspecialchars($perfilUsuario['nombre'] ?? ($_SESSION['usuario_nombre'] ?? '')); ?>" maxlength="100" required>
                </div>
            </div>

            <button type="submit" class="profile-save-btn">Guardar nombre</button>
        </form>

        <div class="profile-data-grid">
            <div class="profile-field">
                <span class="profile-label">Correo</span>
                <div class="profile-value"><?php echo htmlspecialchars($perfilUsuario['email'] ?? ''); ?></div>
            </div>
            <div class="profile-field">
                <span class="profile-label">Nombre de usuario</span>
                <button type="button" class="profile-value-btn" id="profile-username-trigger"><?php echo htmlspecialchars($perfilUsuario['nombre'] ?? ($_SESSION['usuario_nombre'] ?? 'Usuario')); ?></button>
            </div>
            <div class="profile-field">
                <span class="profile-label">Contraseña</span>
                <div class="profile-value">********</div>
            </div>
        </div>

        <div class="profile-section">
            <h3>Historial de pedidos</h3>
            <?php if (empty($historialPedidos)): ?>
                <p class="profile-empty">Aún no tienes pedidos registrados.</p>
            <?php else: ?>
                <div class="profile-list">
                    <?php foreach ($historialPedidos as $pedido): ?>
                        <article class="profile-card-item">
                            <div>
                                <p class="profile-card-title">Pedido #<?php echo str_pad((int)$pedido['id_pedido'], 6, '0', STR_PAD_LEFT); ?></p>
                                <p class="profile-card-meta"><?php echo date('d/m/Y H:i', strtotime($pedido['fecha'])); ?> · Estado: <?php echo htmlspecialchars(ucfirst((string)$pedido['estado'])); ?></p>
                            </div>
                            <div class="profile-card-right">
                                <strong>€<?php echo number_format((float)$pedido['total'], 2, ',', '.'); ?></strong>
                                <a href="confirmacion_pedido.php?id=<?php echo (int)$pedido['id_pedido']; ?>">Ver</a>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="profile-section">
            <h3>Mis valoraciones</h3>
            <?php if (empty($valoracionesUsuario)): ?>
                <p class="profile-empty">Todavía no has dejado valoraciones.</p>
            <?php else: ?>
                <div class="profile-list">
                    <?php foreach ($valoracionesUsuario as $valoracion): ?>
                        <?php $estrellas = max(1, min(5, (int)$valoracion['estrellas'])); ?>
                        <article class="profile-card-item">
                            <div>
                                <p class="profile-card-title">Pedido #<?php echo str_pad((int)$valoracion['id_pedido'], 6, '0', STR_PAD_LEFT); ?></p>
                                <p class="profile-card-meta"><?php echo date('d/m/Y H:i', strtotime($valoracion['fecha'])); ?></p>
                                <p class="profile-card-comment"><?php echo htmlspecialchars(trim((string)($valoracion['comentario'] ?? '')) !== '' ? $valoracion['comentario'] : 'Sin comentario adicional.'); ?></p>
                            </div>
                            <div class="profile-card-right profile-stars"><?php echo str_repeat('★', $estrellas) . str_repeat('☆', 5 - $estrellas); ?></div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="profile-photo-viewer-overlay" id="profile-photo-viewer-overlay"></div>
    <div class="profile-photo-viewer" id="profile-photo-viewer" aria-hidden="true">
        <button type="button" class="profile-photo-viewer-close" id="profile-photo-viewer-close" aria-label="Cerrar vista de foto">&times;</button>
        <img src="<?php echo htmlspecialchars($fotoPerfilUsuario); ?>" alt="Foto de perfil ampliada">
    </div>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const wrapper = document.querySelector('.auth-inline-wrapper');
    if (wrapper) {
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
    }

    const openProfileBtn = document.getElementById('open-profile-modal');
    const closeProfileBtn = document.getElementById('close-profile-modal');
    const profileModal = document.getElementById('profile-modal');
    const profileOverlay = document.getElementById('profile-modal-overlay');
    const profileShouldOpen = <?php echo $profileOpen ? 'true' : 'false'; ?>;
    const avatarTrigger = document.getElementById('profile-avatar-trigger');
    const usernameTrigger = document.getElementById('profile-username-trigger');
    const photoActions = document.getElementById('photo-actions');
    const closePhotoActions = document.getElementById('close-photo-actions');
    const btnViewPhoto = document.getElementById('btn-view-photo');
    const btnEditPhoto = document.getElementById('btn-edit-photo');
    const closePhotoEdit = document.getElementById('close-photo-edit');
    const photoEditForm = document.getElementById('photo-edit-form');
    const nameEditForm = document.getElementById('name-edit-form');
    const photoViewer = document.getElementById('profile-photo-viewer');
    const photoViewerOverlay = document.getElementById('profile-photo-viewer-overlay');
    const photoViewerClose = document.getElementById('profile-photo-viewer-close');

    if (openProfileBtn && closeProfileBtn && profileModal && profileOverlay) {
        function openProfileModal() {
            profileModal.classList.add('open');
            profileOverlay.classList.add('open');
            profileModal.setAttribute('aria-hidden', 'false');
            document.body.style.overflow = 'hidden';
        }

        function closeProfileModal() {
            profileModal.classList.remove('open');
            profileOverlay.classList.remove('open');
            profileModal.setAttribute('aria-hidden', 'true');
            document.body.style.overflow = '';
        }

        function hideInlineBlocks() {
            if (photoActions) photoActions.style.display = 'none';
            if (photoEditForm) photoEditForm.style.display = 'none';
            if (nameEditForm) nameEditForm.style.display = 'none';
        }

        function openPhotoViewer() {
            if (!photoViewer || !photoViewerOverlay) return;
            photoViewer.classList.add('open');
            photoViewerOverlay.classList.add('open');
            photoViewer.setAttribute('aria-hidden', 'false');
        }

        function closePhotoViewer() {
            if (!photoViewer || !photoViewerOverlay) return;
            photoViewer.classList.remove('open');
            photoViewerOverlay.classList.remove('open');
            photoViewer.setAttribute('aria-hidden', 'true');
        }

        openProfileBtn.addEventListener('click', openProfileModal);
        closeProfileBtn.addEventListener('click', closeProfileModal);
        profileOverlay.addEventListener('click', closeProfileModal);

        if (avatarTrigger && photoActions) {
            avatarTrigger.addEventListener('click', function() {
                const showing = photoActions.style.display === 'block';
                hideInlineBlocks();
                photoActions.style.display = showing ? 'none' : 'block';
            });
        }

        if (closePhotoActions && photoActions) {
            closePhotoActions.addEventListener('click', function() {
                photoActions.style.display = 'none';
            });
        }

        if (usernameTrigger && nameEditForm) {
            usernameTrigger.addEventListener('click', function() {
                const showing = nameEditForm.style.display === 'block';
                hideInlineBlocks();
                nameEditForm.style.display = showing ? 'none' : 'block';
            });
        }

        if (btnViewPhoto) {
            btnViewPhoto.addEventListener('click', function() {
                openPhotoViewer();
            });
        }

        if (btnEditPhoto && photoEditForm) {
            btnEditPhoto.addEventListener('click', function() {
                hideInlineBlocks();
                photoEditForm.style.display = 'block';
            });
        }

        if (closePhotoEdit && photoEditForm) {
            closePhotoEdit.addEventListener('click', function() {
                photoEditForm.style.display = 'none';
            });
        }

        if (photoViewerClose) {
            photoViewerClose.addEventListener('click', closePhotoViewer);
        }
        if (photoViewerOverlay) {
            photoViewerOverlay.addEventListener('click', closePhotoViewer);
        }

        if (profileShouldOpen) {
            openProfileModal();
        }

        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closePhotoViewer();
                closeProfileModal();
            }
        });
    }
});
</script>
