<?php
$reactBase = rtrim(getenv('REACT_APP_URL') ?: 'http://localhost/veridi_tienda_web/frontend/dist', '/');
header('Location: ' . $reactBase . '/#/');
exit;
