<?php
$host = "localhost";
$dbname = "u336643015_veridi";
$user = "u336643015_veridiusr";
$password = "Unionelipa4.";

try {
    $conexion = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $password);
    $conexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}
?>