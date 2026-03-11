<?php
require_once "../config/conexion.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function redirectWithParams(string $basePage, array $params = []): void {
    $basePage = trim($basePage);
    if (!preg_match('/^[a-zA-Z0-9._-]+\.php$/', $basePage)) {
        $basePage = 'index.php';
    }

    $query = http_build_query($params);
    $url = '../' . $basePage . ($query !== '' ? '?' . $query : '');
    header('Location: ' . $url);
    exit();
}

function getRedirectTarget(): string {
    $redirect = trim($_POST['redirect'] ?? 'index.php');
    if (!preg_match('/^[a-zA-Z0-9._-]+\.php$/', $redirect)) {
        return 'index.php';
    }
    return $redirect;
}

$redirectPage = getRedirectTarget();

if (isset($_POST['registro'])) {

    $nombre = trim($_POST['nombre'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $passwordConfirm = $_POST['password_confirm'] ?? '';

    if ($nombre === '' || $email === '' || $password === '') {
        redirectWithParams($redirectPage, ['auth_open' => '1', 'auth_tab' => 'register', 'auth_error' => 'faltan_campos']);
    }

    if (strlen($password) < 6) {
        redirectWithParams($redirectPage, ['auth_open' => '1', 'auth_tab' => 'register', 'auth_error' => 'password_corta']);
    }

    if ($password !== $passwordConfirm) {
        redirectWithParams($redirectPage, ['auth_open' => '1', 'auth_tab' => 'register', 'auth_error' => 'password_no_coincide']);
    }

    // Hashear contraseña
    $password_hash = password_hash($password, PASSWORD_DEFAULT);

    try {
        $stmtExiste = $conexion->prepare("SELECT id_usuario FROM usuarios WHERE email = :email LIMIT 1");
        $stmtExiste->bindParam(':email', $email);
        $stmtExiste->execute();

        if ($stmtExiste->fetch(PDO::FETCH_ASSOC)) {
            redirectWithParams($redirectPage, ['auth_open' => '1', 'auth_tab' => 'register', 'auth_error' => 'email_existente']);
        }

        $sql = "INSERT INTO usuarios (nombre, email, password) 
                VALUES (:nombre, :email, :password)";

        $stmt = $conexion->prepare($sql);
        $stmt->bindParam(':nombre', $nombre);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':password', $password_hash);

        $stmt->execute();

        redirectWithParams($redirectPage, ['auth_open' => '1', 'auth_tab' => 'login', 'auth_success' => 'registro']);

    } catch (PDOException $e) {
        redirectWithParams($redirectPage, ['auth_open' => '1', 'auth_tab' => 'register', 'auth_error' => 'general']);
    }
}

// LOGIN
if (isset($_POST['login'])) {

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        redirectWithParams($redirectPage, ['auth_open' => '1', 'auth_tab' => 'login', 'auth_error' => 'faltan_campos']);
    }

    try {
        $sql = "SELECT * FROM usuarios WHERE email = :email";
        $stmt = $conexion->prepare($sql);
        $stmt->bindParam(':email', $email);
        $stmt->execute();

        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($usuario && password_verify($password, $usuario['password'])) {

            $_SESSION['usuario_id'] = $usuario['id_usuario'];
            $_SESSION['usuario_nombre'] = $usuario['nombre'];
            $_SESSION['usuario_rol'] = $usuario['rol'] ?? 'cliente';

            // Inicializar carrito y deseos si no existen
            if (!isset($_SESSION['carrito']) || !is_array($_SESSION['carrito'])) {
                $_SESSION['carrito'] = [];
            }
            if (!isset($_SESSION['deseos']) || !is_array($_SESSION['deseos'])) {
                $_SESSION['deseos'] = [];
            }

            redirectWithParams($redirectPage);

        } else {
            redirectWithParams($redirectPage, ['auth_open' => '1', 'auth_tab' => 'login', 'auth_error' => 'credenciales']);
        }

    } catch (PDOException $e) {
        redirectWithParams($redirectPage, ['auth_open' => '1', 'auth_tab' => 'login', 'auth_error' => 'general']);
    }
}

redirectWithParams('index.php');


