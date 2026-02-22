<?php
/**
 * ========================================
 * MAPEO MANUAL DE IMÁGENES POR PRODUCTO
 * ========================================
 * 
 * ✅ CÓMO FUNCIONA:
 * - Cada número (1, 2, 3...) es el ID del producto en la base de datos
 * - El valor ('img/...') es la ruta de la imagen que quieres mostrar
 * 
 * 📝 EJEMPLO:
 * Si tienes "Gorra Classic" con ID 36, y quieres que muestre gorraVeridi.png:
 *    36 => 'img/gorraVeridi.png',
 * 
 * 🔍 PARA VER LOS IDs DE TUS PRODUCTOS:
 * Ve a phpMyAdmin → tabla "productos" → columna "id_producto"
 * 
 * 📂 IMÁGENES DISPONIBLES EN TU CARPETA img/:
 * - camisetaNegraVeridi.png
 * - camisetaVeridiNegra2.png  
 * - camisetaVeridi_blanca.png
 * - chaquetaVeridi.png
 * - abrigoVeridiBlanco.png
 * - SudaderaNegra_Cremallera.png
 * - pantalonVeridiNegro.png
 * - PlumasVeridiNegro.png
 * - gorraVeridi.png
 * - gorraVeridiBlanca_logoBlanco.png
 * - gorraVeridi_blanca.png
 * - gorraVeridi_logoBlanco.png
 */

$imagenesProducto = [
    // ================= CAMISETAS (IDs 1-5) =================
    1 => 'img/camisetaVeridi_blanca.png',         // Camiseta Essential Blanca
    2 => 'img/camisetaVeridiNegra2.png',          // Camiseta Urban Negra
    3 => 'img/camisetaVeridi_blanca.png',         // Camiseta Sport Dry
    4 => 'img/camisetaNegraVeridi.png',           // Camiseta Premium Slim
    5 => 'img/camisetaVeridiNegra2.png',          // Camiseta Vintage
    
    // ================= CHAQUETAS (IDs 6-10) =================
    6 => 'img/chaquetaVeridi.png',                // Chaqueta Bomber Classic
    7 => 'img/chaquetaVeridi.png',                // Chaqueta Denim Azul
    8 => 'img/chaquetaVeridi.png',                // Chaqueta Sport Pro
    9 => 'img/chaquetaVeridi.png',                // Chaqueta Formal Slim
    10 => 'img/chaquetaVeridi.png',               // Chaqueta Cuero Urban
    
    // ================= ABRIGOS (IDs 11-15) =================
    11 => 'img/abrigoVeridiBlanco.png',           // Abrigo Largo Invierno
    12 => 'img/abrigoVeridiBlanco.png',           // Abrigo Casual Urban
    13 => 'img/abrigoVeridiBlanco.png',           // Abrigo Deportivo Tech
    14 => 'img/abrigoVeridiBlanco.png',           // Abrigo Slim Elegance
    15 => 'img/abrigoVeridiBlanco.png',           // Abrigo Parka
    
    // ================= SUDADERAS (IDs 16-20) =================
    16 => 'img/SudaderaNegra_Cremallera.png',    // Sudadera Basic
    17 => 'img/SudaderaNegra_Cremallera.png',    // Sudadera Hoodie Sport
    18 => 'img/SudaderaNegra_Cremallera.png',    // Sudadera Minimal
    19 => 'img/SudaderaNegra_Cremallera.png',    // Sudadera Premium
    20 => 'img/SudaderaNegra_Cremallera.png',    // Sudadera Training Pro
    
    // ================= PANTALONES (IDs 21-25) =================
    21 => 'img/pantalonVeridiNegro.png',          // Pantalón Chino Beige
    22 => 'img/pantalonVeridiNegro.png',          // Pantalón Jogger
    23 => 'img/pantalonVeridiNegro.png',          // Pantalón Slim Fit
    24 => 'img/pantalonVeridiNegro.png',          // Pantalón Cargo
    25 => 'img/pantalonVeridiNegro.png',          // Pantalón Formal Classic
    
    // ================= VAQUEROS (IDs 26-30) =================
    26 => 'img/pantalonVeridiNegro.png',          // Vaquero Slim Blue
    27 => 'img/pantalonVeridiNegro.png',          // Vaquero Regular Fit
    28 => 'img/pantalonVeridiNegro.png',          // Vaquero Destroyed
    29 => 'img/pantalonVeridiNegro.png',          // Vaquero Black Edition
    30 => 'img/pantalonVeridiNegro.png',          // Vaquero Stretch Pro
    
    // ================= CALZADO (IDs 31-35) =================
    31 => 'img/PlumasVeridiNegro.png',            // Zapatillas Urban
    32 => 'img/PlumasVeridiNegro.png',            // Zapatillas Running Pro
    33 => 'img/PlumasVeridiNegro.png',            // Zapato Formal Elegance
    34 => 'img/PlumasVeridiNegro.png',            // Botines Casual
    35 => 'img/PlumasVeridiNegro.png',            // Sneakers Classic
    
    // ================= GORRAS (IDs 36-40) =================
    36 => 'img/gorraVeridi.png',                  // Gorra Classic
    37 => 'img/gorraVeridi_blanca.png',           // Gorra Sport
    38 => 'img/gorraVeridiBlanca_logoBlanco.png', // Gorra Snapback
    39 => 'img/gorraVeridi_logoBlanco.png',       // Gorra Premium
    40 => 'img/gorraVeridi.png',                  // Gorra Training
    
    // ================= CALCETINES (IDs 41-45) =================
    41 => 'img/camisetaNegraVeridi.png',          // Calcetines Sport Pack
    42 => 'img/camisetaNegraVeridi.png',          // Calcetines Classic
    43 => 'img/camisetaNegraVeridi.png',          // Calcetines Urban
    44 => 'img/camisetaNegraVeridi.png',          // Calcetines Pro Running
    45 => 'img/camisetaNegraVeridi.png',          // Calcetines Premium
    
    // ================= ACCESORIOS (IDs 46-50) =================
    46 => 'img/gorraVeridi.png',                  // Cinturón Cuero
    47 => 'img/gorraVeridi.png',                  // Mochila Urban
    48 => 'img/gorraVeridi.png',                  // Bolso Deportivo
    49 => 'img/gorraVeridi.png',                  // Bufanda Lana
    50 => 'img/gorraVeridi.png',                  // Pulsera Minimal
];

/**
 * Función para obtener la imagen de un producto
 * Retorna la imagen asignada o una por defecto si no existe
 */
function obtenerImagenProducto($idProducto) {
    global $imagenesProducto;
    
    // Si el ID está en el array, devolver su imagen
    if (isset($imagenesProducto[$idProducto])) {
        return $imagenesProducto[$idProducto];
    }
    
    // Si no existe, devolver imagen por defecto
    return 'img/camisetaNegraVeridi.png';
}
?>
