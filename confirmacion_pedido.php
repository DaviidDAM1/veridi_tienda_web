<?php
$reactBase = rtrim(getenv('REACT_APP_URL') ?: 'http://localhost/veridi_tienda_web/frontend/dist', '/');
$idPedido = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($idPedido > 0) {
    header('Location: ' . $reactBase . '/#/confirmacion/' . $idPedido);
} else {
    header('Location: ' . $reactBase . '/#/tienda');
}

exit;
