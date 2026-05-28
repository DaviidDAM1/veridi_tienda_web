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

    // Calzado (nuevos productos)
    51 => 'imgnuevas/zapatillasNegras.png',
    52 => 'imgnuevas/zapatillasRunningAzules.png',
    53 => 'imgnuevas/zapatillasRunningBlancas.png',
    54 => 'imgnuevas/zapatillasRunningNegras.png',
    55 => 'imgnuevas/zapatillasTNblancas.png',
    56 => 'imgnuevas/zapatillasTNnegras.png',
    57 => 'imgnuevas/zapatillasBlancas.png',

    // Compatibilidad con base antigua de 50 productos
    47 => 'imgnuevas/calzoncillosVeridi.png',
    48 => 'imgnuevas/gorraNegra.png',
    49 => 'imgnuevas/gorraBlanca.png',
    50 => 'imgnuevas/gorraAzul.png',
];

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
        'zapatillas urban blancas' => 'imgnuevas/zapatillasBlancas.png'
    ];

    $claveNombre = normalizarNombreImagen($nombreProducto);
    if ($claveNombre !== '' && isset($mapaPorNombre[$claveNombre])) {
        return $mapaPorNombre[$claveNombre];
    }

    return 'imgnuevas/LogoVeridi.png';
}

?>
