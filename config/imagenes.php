<?php

$imagenesProducto = [
    1 => 'imgnuevas/camisetaBlancaVeridi2.png',
    2 => 'imgnuevas/camisetaDeportiva.png',
    3 => 'imgnuevas/camisetaDeportivaAzul.png',
    4 => 'imgnuevas/camisetaDeportivaBlanca.png',
    5 => 'imgnuevas/camisetaGrisVeridi2.png',
    6 => 'imgnuevas/camisetaNegraLogoBlanco.png',
    7 => 'imgnuevas/camisetaRosaVeridi2.png',
    8 => 'imgnuevas/camisetaVeridiAzul2.png',
    9 => 'imgnuevas/camisetaveridiNegra2.png',
    10 => 'imgnuevas/poloBlanco.png',
    11 => 'imgnuevas/poloNegro.png',

    12 => 'imgnuevas/chaquetaBlancaConCapucha.png',
    13 => 'imgnuevas/chaquetaBomberNegra.png',
    14 => 'imgnuevas/chaquetaBomberVerde.png',
    15 => 'imgnuevas/chaquetaNegraConCapucha.png',
    16 => 'imgnuevas/chaquetaVaquera.png',
    17 => 'imgnuevas/chaquetaVaqueraAzulOscuro.png',

    18 => 'imgnuevas/plumasVeridi.png',
    19 => 'imgnuevas/plumasVeridiCani.png',

    20 => 'imgnuevas/sudaderaNegra.png',

    21 => 'imgnuevas/pantalonChandalCortosGris.png',
    22 => 'imgnuevas/pantalonChandalCortosRosa.png',
    23 => 'imgnuevas/pantalonChandalLargoAzul.png',
    24 => 'imgnuevas/pantalonChandalLargoGris.png',
    25 => 'imgnuevas/pantalonChandalLargoNegro.png',
    26 => 'imgnuevas/pantalonChandalLargoRosa.png',
    27 => 'imgnuevas/pantalonesChandalCortosAzulClaro.png',
    28 => 'imgnuevas/pantalonesChandalCortosBlancos.png',
    29 => 'imgnuevas/pantalonesChandalCortosNegros.png',

    30 => 'imgnuevas/vaquerosAzulesClaros.jpg',
    31 => 'imgnuevas/vaquerosAzulesClarosRotos.jpg',
    32 => 'imgnuevas/vaquerosCortosAzules.jpg',
    33 => 'imgnuevas/vaquerosCortosAzulesRotos.jpg',
    34 => 'imgnuevas/vaquerosCortosNegros.jpg',
    35 => 'imgnuevas/vaquerosCortosNgerosRoto.jpg',
    36 => 'imgnuevas/vaquerosNegros.jpg',
    37 => 'imgnuevas/vaquerosNegrosRotos.jpg',

    38 => 'imgnuevas/gorraAzul.png',
    39 => 'imgnuevas/gorraAzul2.png',
    40 => 'imgnuevas/gorraBlanca.png',
    41 => 'imgnuevas/gorraNegra.png',
    42 => 'imgnuevas/gorraNegraEntera.png',
    43 => 'imgnuevas/gorraRoja.png',

    44 => 'imgnuevas/calcetinesBlancos.png',
    45 => 'imgnuevas/calcetinesNegros.png',

    46 => 'imgnuevas/calzoncillosVeridi.png',

    // Calzado
    47 => 'imgnuevas/zapatillasNegras.png',
    48 => 'imgnuevas/zapatillasRunningAzules.png',
    49 => 'imgnuevas/zapatillasRunningBlancas.png',
    50 => 'imgnuevas/zapatillasRunningNegras.png',
    51 => 'imgnuevas/zapatillasTNblancas.png',
    52 => 'imgnuevas/zapatillasTNnegras.png',
    53 => 'imgnuevas/zapatillasBlancas.png',

    // Pantalones cargo formales
    54 => 'imgnuevas/pantalonesCargoMarrones.png',
    55 => 'imgnuevas/pantalonNegroBlanco.png',
    56 => 'imgnuevas/pantalonNegroCargo.png',
    57 => 'imgnuevas/pantalonVerdeCargo.png',

    // Mapeo para BD ya poblada (IDs reales actuales)
    58 => 'imgnuevas/pantalonesCargoMarrones.png',
    59 => 'imgnuevas/pantalonNegroBlanco.png',
    60 => 'imgnuevas/pantalonNegroCargo.png',
    61 => 'imgnuevas/pantalonVerdeCargo.png',
];

function rutaImagenesPersonalizadasConfig()
{
    return __DIR__ . DIRECTORY_SEPARATOR . 'imagenes_personalizadas.json';
}

function obtenerMapeoImagenesPersonalizadas()
{
    static $cache = null;
    if (is_array($cache)) {
        return $cache;
    }

    $ruta = rutaImagenesPersonalizadasConfig();
    if (!is_file($ruta)) {
        $cache = [];
        return $cache;
    }

    $raw = @file_get_contents($ruta);
    if ($raw === false || trim($raw) === '') {
        $cache = [];
        return $cache;
    }

    $decoded = json_decode($raw, true);
    if (!is_array($decoded)) {
        $cache = [];
        return $cache;
    }

    $salida = [];
    foreach ($decoded as $id => $rutaRelativa) {
        $idProducto = (int)$id;
        $rutaLimpia = trim((string)$rutaRelativa);
        if ($idProducto > 0 && $rutaLimpia !== '') {
            $salida[$idProducto] = str_replace('\\', '/', $rutaLimpia);
        }
    }

    $cache = $salida;
    return $cache;
}

function guardarImagenProductoPersonalizada($idProducto, $rutaRelativa)
{
    $idProducto = (int)$idProducto;
    $rutaRelativa = trim((string)$rutaRelativa);
    if ($idProducto <= 0 || $rutaRelativa === '') {
        return false;
    }

    $rutaConfig = rutaImagenesPersonalizadasConfig();
    $actual = [];
    if (is_file($rutaConfig)) {
        $rawActual = @file_get_contents($rutaConfig);
        $decodedActual = $rawActual !== false ? json_decode($rawActual, true) : null;
        if (is_array($decodedActual)) {
            foreach ($decodedActual as $id => $ruta) {
                $idInt = (int)$id;
                $rutaLimpia = trim((string)$ruta);
                if ($idInt > 0 && $rutaLimpia !== '') {
                    $actual[$idInt] = str_replace('\\', '/', $rutaLimpia);
                }
            }
        }
    }

    $actual[$idProducto] = str_replace('\\', '/', $rutaRelativa);

    $json = json_encode($actual, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    if ($json === false) {
        return false;
    }

    $ok = @file_put_contents($rutaConfig, $json, LOCK_EX);
    if ($ok === false) {
        return false;
    }

    return true;
}

function normalizarNombreImagen($texto)
{
    $texto = mb_strtolower(trim((string)$texto), 'UTF-8');
    $texto = strtr($texto, [
        'á' => 'a',
        'é' => 'e',
        'í' => 'i',
        'ó' => 'o',
        'ú' => 'u',
        'ü' => 'u',
        'ñ' => 'n'
    ]);
    $texto = preg_replace('/\s+/', ' ', $texto);
    return $texto;
}

function obtenerImagenProducto($idProducto, $nombreProducto = '') {
    global $imagenesProducto;

    $personalizadas = obtenerMapeoImagenesPersonalizadas();
    if (isset($personalizadas[(int)$idProducto])) {
        return $personalizadas[(int)$idProducto];
    }

    if (isset($imagenesProducto[$idProducto])) {
        return $imagenesProducto[$idProducto];
    }

    $mapaPorNombre = [
        'zapatillas urban negras' => 'imgnuevas/zapatillasNegras.png',
        'zapatillas running azul' => 'imgnuevas/zapatillasRunningAzules.png',
        'zapatillas running blancas' => 'imgnuevas/zapatillasRunningBlancas.png',
        'zapatillas running negras' => 'imgnuevas/zapatillasRunningNegras.png',
        'zapatillas tn blancas' => 'imgnuevas/zapatillasTNblancas.png',
        'zapatillas tn negras' => 'imgnuevas/zapatillasTNnegras.png',
        'zapatillas urban blancas' => 'imgnuevas/zapatillasBlancas.png',

        'pantalon cargo beige' => 'imgnuevas/pantalonesCargoMarrones.png',
        'pantalon cargo blanco' => 'imgnuevas/pantalonNegroBlanco.png',
        'pantalon cargo negro' => 'imgnuevas/pantalonNegroCargo.png',
        'pantalon cargo verde' => 'imgnuevas/pantalonVerdeCargo.png'
    ];

    $claveNombre = normalizarNombreImagen($nombreProducto);
    if ($claveNombre !== '' && isset($mapaPorNombre[$claveNombre])) {
        return $mapaPorNombre[$claveNombre];
    }

    // Fallback flexible para nombres con variaciones (plural, sufijos, etc.)
    if ($claveNombre !== '' && strpos($claveNombre, 'cargo') !== false) {
        if (strpos($claveNombre, 'beige') !== false || strpos($claveNombre, 'marron') !== false) {
            return 'imgnuevas/pantalonesCargoMarrones.png';
        }
        if (strpos($claveNombre, 'blanco') !== false || strpos($claveNombre, 'blanca') !== false) {
            return 'imgnuevas/pantalonNegroBlanco.png';
        }
        if (strpos($claveNombre, 'verde') !== false || strpos($claveNombre, 'oliva') !== false) {
            return 'imgnuevas/pantalonVerdeCargo.png';
        }
        if (strpos($claveNombre, 'negro') !== false || strpos($claveNombre, 'negra') !== false) {
            return 'imgnuevas/pantalonNegroCargo.png';
        }
    }

    return 'imgnuevas/LogoVeridi.png';
}

?>
