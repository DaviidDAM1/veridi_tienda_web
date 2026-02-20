<?php
session_start();

if (isset($_SESSION['usuario_id'])) {
	require_once "config/conexion.php";

	// El carrito y deseos se mantienen en sesión
	// Se pueden guardar en BD si es necesario aquí
}

session_destroy();
header("Location: login.php");
exit();
?>
