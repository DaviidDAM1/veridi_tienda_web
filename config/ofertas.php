<?php

function veridiOfertaPorNombre(string $nombre): ?array
{
    if (strcasecmp(trim($nombre), 'Gorra Roja Veridi') === 0) {
        return [
            'porcentaje' => 40.0
        ];
    }

    return null;
}

function veridiCalcularPrecioOferta(string $nombre, float $precioBase): array
{
    $oferta = veridiOfertaPorNombre($nombre);

    if (!$oferta) {
        return [
            'precio' => round($precioBase, 2),
            'precio_original' => null,
            'descuento_porcentaje' => 0.0,
            'en_oferta' => false
        ];
    }

    $porcentaje = (float)($oferta['porcentaje'] ?? 0);
    $porcentaje = max(0.0, min(100.0, $porcentaje));
    $precioConOferta = max(0.01, round($precioBase * (1 - ($porcentaje / 100)), 2));

    return [
        'precio' => $precioConOferta,
        'precio_original' => round($precioBase, 2),
        'descuento_porcentaje' => $porcentaje,
        'en_oferta' => true
    ];
}

function veridiAplicarOfertaProducto(array $producto): array
{
    $nombre = (string)($producto['nombre'] ?? '');
    $precioBase = (float)($producto['precio'] ?? 0);
    $pricing = veridiCalcularPrecioOferta($nombre, $precioBase);

    $producto['precio'] = (float)$pricing['precio'];
    $producto['precio_original'] = $pricing['precio_original'] !== null ? (float)$pricing['precio_original'] : null;
    $producto['descuento_porcentaje'] = (float)$pricing['descuento_porcentaje'];
    $producto['en_oferta'] = (bool)$pricing['en_oferta'];

    return $producto;
}
