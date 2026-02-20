<?php
require_once "../config/conexion.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_POST['registro'])) {

    $nombre = trim($_POST['nombre'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $passwordConfirm = $_POST['password_confirm'] ?? '';

    if ($nombre === '' || $email === '' || $password === '') {
        header("Location: ../registro.php?error=faltan_campos");
        exit();
    }

    if (strlen($password) < 6) {
        header("Location: ../registro.php?error=password_corta");
        exit();
    }

    if ($password !== $passwordConfirm) {
        header("Location: ../registro.php?error=password_no_coincide");
        exit();
    }

    // Hashear contraseña
    $password_hash = password_hash($password, PASSWORD_DEFAULT);

    try {
        $stmtExiste = $conexion->prepare("SELECT id_usuario FROM usuarios WHERE email = :email LIMIT 1");
        $stmtExiste->bindParam(':email', $email);
        $stmtExiste->execute();

        if ($stmtExiste->fetch(PDO::FETCH_ASSOC)) {
            header("Location: ../registro.php?error=email_existente");
            exit();
        }

        $sql = "INSERT INTO usuarios (nombre, email, password) 
                VALUES (:nombre, :email, :password)";

        $stmt = $conexion->prepare($sql);
        $stmt->bindParam(':nombre', $nombre);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':password', $password_hash);

        $stmt->execute();

        header("Location: ../login.php?success=registro");
        exit();

    } catch (PDOException $e) {
        header("Location: ../registro.php?error=general");
        exit();
    }
}

// LOGIN
if (isset($_POST['login'])) {

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']) && $_POST['remember'] === '1';

    if ($email === '' || $password === '') {
        header("Location: ../login.php?error=faltan_campos");
        exit();
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

            // Inicializar carrito y deseos si no existen
            if (!isset($_SESSION['carrito']) || !is_array($_SESSION['carrito'])) {
                $_SESSION['carrito'] = [];
            }
            if (!isset($_SESSION['deseos']) || !is_array($_SESSION['deseos'])) {
                $_SESSION['deseos'] = [];
            }

            header("Location: ../index.php");
            exit();

        } else {
            header("Location: ../login.php?error=credenciales");
            exit();
        }

    } catch (PDOException $e) {
        header("Location: ../login.php?error=general");
        exit();
    }
}

header("Location: ../login.php");
exit();


