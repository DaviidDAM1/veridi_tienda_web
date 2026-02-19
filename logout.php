<?php
session_start();

if (isset($_SESSION['usuario_id'])) {
	require_once "config/conexion.php";
	require_once "php/carrito_storage.php";

	$carrito = (isset($_SESSION['carrito']) && is_array($_SESSION['carrito'])) ? $_SESSION['carrito'] : [];
	carritoSaveByUser($conexion, (int)$_SESSION['usuario_id'], $carrito);
}

session_destroy();
header("Location: login.php");
exit();
?>
