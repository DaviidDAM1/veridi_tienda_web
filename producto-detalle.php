<?php
$reactBase = rtrim(getenv('REACT_APP_URL') ?: 'http://localhost/veridi_tienda_web/frontend/dist', '/');
$idProducto = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($idProducto > 0) {
    header('Location: ' . $reactBase . '/#/producto/' . $idProducto);
} else {
    header('Location: ' . $reactBase . '/#/tienda');
}

exit;
