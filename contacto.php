<?php
require_once "config/conexion.php";
require_once "includes/header.php";

$mensajeExito = "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $nombre = trim($_POST["nombre"]);
    $email = trim($_POST["email"]);
    $tipo = $_POST["tipo"];
    $mensaje = trim($_POST["mensaje"]);

    if (!empty($nombre) && !empty($email) && !empty($mensaje)) {

        $id_usuario = isset($_SESSION['usuario_id']) ? $_SESSION['usuario_id'] : null;

        $stmt = $conexion->prepare("INSERT INTO contactos (nombre, email, tipo, mensaje, id_usuario) 
                                    VALUES (?, ?, ?, ?, ?)");

        if ($stmt->execute([$nombre, $email, $tipo, $mensaje, $id_usuario])) {
            $mensajeExito = "Mensaje enviado correctamente. Te responderemos pronto.";
        } else {
            $error = "Hubo un error al enviar el mensaje.";
        }

    } else {
        $error = "Todos los campos son obligatorios.";
    }
}
?>

<main>
    <h2>Contacto</h2>

    <?php if ($mensajeExito): ?>
        <p style="color:green;"><?php echo $mensajeExito; ?></p>
    <?php endif; ?>

    <?php if ($error): ?>
        <p style="color:red;"><?php echo $error; ?></p>
    <?php endif; ?>

    <div class="contacto-container">
        <form method="POST" action="contacto.php" class="form-contacto">

            <label>Nombre</label>
            <input type="text" name="nombre" required>

            <label>Email</label>
            <input type="email" name="email" required>

            <label>Tipo de mensaje</label>
            <select name="tipo">
                <option value="consulta">Consulta</option>
                <option value="queja">Queja</option>
                <option value="reclamacion">Reclamación</option>
                <option value="otro">Otro</option>
            </select>

            <label>Mensaje</label>
            <textarea name="mensaje" rows="5" required></textarea>

            <button type="submit">Enviar mensaje</button>
        </form>
    </div>
</main>

<?php require_once "includes/footer.php"; ?>

