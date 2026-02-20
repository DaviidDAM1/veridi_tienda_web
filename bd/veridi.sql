-- ================================
-- ELIMINAR Y CREAR BASE DE DATOS
-- ================================
DROP DATABASE IF EXISTS veridi;
CREATE DATABASE veridi;
USE veridi;

-- ================================
-- TABLA CATEGORIAS
-- ================================
CREATE TABLE categorias (
    id_categoria INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL UNIQUE
);

INSERT INTO categorias (nombre) VALUES
('Camisetas'),
('Chaquetas'),
('Abrigos'),
('Sudaderas'),
('Pantalones'),
('Vaqueros'),
('Calzado'),
('Gorras'),
('Calcetines'),
('Accesorios');

select*from categorias;

-- ================================
-- TABLA PRODUCTOS
-- ================================
CREATE TABLE productos (
    id_producto INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(150) NOT NULL,
    descripcion TEXT,
    precio DECIMAL(10,2) NOT NULL,
    color VARCHAR(50),
    estilo ENUM('casual','formal','deportivo') NOT NULL,
    material VARCHAR(100),
    id_categoria INT,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (id_categoria)
        REFERENCES categorias(id_categoria)
        ON DELETE SET NULL
        ON UPDATE CASCADE
);

-- ================================
-- TABLA TALLAS
-- ================================
CREATE TABLE tallas (
    id_talla INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(20) NOT NULL UNIQUE
);

INSERT INTO tallas (nombre) VALUES
('S'),
('M'),
('L'),
('XL'),
('40'),
('41'),
('42'),
('Única');

-- ================================
-- TABLA PRODUCTO_TALLAS (Stock por talla)
-- ================================
CREATE TABLE producto_tallas (
    id_producto INT,
    id_talla INT,
    stock INT NOT NULL,

    PRIMARY KEY (id_producto, id_talla),

    FOREIGN KEY (id_producto)
        REFERENCES productos(id_producto)
        ON DELETE CASCADE,

    FOREIGN KEY (id_talla)
        REFERENCES tallas(id_talla)
        ON DELETE CASCADE
);

-- ================================
-- TABLA USUARIOS
-- ================================
CREATE TABLE usuarios (
    id_usuario INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    rol ENUM('cliente','admin') DEFAULT 'cliente',
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ================================
-- TABLA CARRITO
-- ================================
CREATE TABLE carrito (
    id_carrito INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (id_usuario)
        REFERENCES usuarios(id_usuario)
        ON DELETE CASCADE
);

-- ================================
-- TABLA CARRITO_DETALLE
-- ================================
CREATE TABLE carrito_detalle (
    id_detalle INT AUTO_INCREMENT PRIMARY KEY,
    id_carrito INT,
    id_producto INT,
    id_talla INT,
    cantidad INT NOT NULL,

    FOREIGN KEY (id_carrito)
        REFERENCES carrito(id_carrito)
        ON DELETE CASCADE,

    FOREIGN KEY (id_producto)
        REFERENCES productos(id_producto)
        ON DELETE CASCADE,

    FOREIGN KEY (id_talla)
        REFERENCES tallas(id_talla)
        ON DELETE CASCADE
);

-- ================================
-- TABLA PEDIDOS
-- ================================
CREATE TABLE pedidos (
    id_pedido INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT,
    total DECIMAL(10,2) NOT NULL,
    estado ENUM('pendiente','pagado','enviado','cancelado') DEFAULT 'pendiente',
    fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (id_usuario)
        REFERENCES usuarios(id_usuario)
        ON DELETE SET NULL
);

-- ================================
-- TABLA PEDIDO_DETALLE
-- ================================
CREATE TABLE pedido_detalle (
    id_detalle INT AUTO_INCREMENT PRIMARY KEY,
    id_pedido INT,
    id_producto INT,
    id_talla INT,
    cantidad INT NOT NULL,
    precio_unitario DECIMAL(10,2) NOT NULL,

    FOREIGN KEY (id_pedido)
        REFERENCES pedidos(id_pedido)
        ON DELETE CASCADE,

    FOREIGN KEY (id_producto)
        REFERENCES productos(id_producto)
        ON DELETE SET NULL,

    FOREIGN KEY (id_talla)
        REFERENCES tallas(id_talla)
        ON DELETE SET NULL
);

-- ================================
-- ================================
-- TABLA CONTACTO
-- ================================
CREATE TABLE contacto (
    id_contacto INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL,
    asunto VARCHAR(200),
    mensaje TEXT NOT NULL,
    contrasena VARCHAR(255),
    fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    leido BOOLEAN DEFAULT FALSE
);



INSERT INTO productos (nombre, descripcion, precio, color, estilo, material, id_categoria) VALUES

-- ================= CAMISETAS (1)
('Camiseta Essential Blanca', 'Camiseta básica cómoda para uso diario', 19.99, 'Blanco', 'casual', 'Algodón', 1),
('Camiseta Urban Negra', 'Camiseta moderna estilo urbano', 22.99, 'Negro', 'casual', 'Algodón', 1),
('Camiseta Sport Dry', 'Camiseta transpirable para deporte', 24.99, 'Azul', 'deportivo', 'Poliéster', 1),
('Camiseta Premium Slim', 'Camiseta ajustada elegante', 29.99, 'Gris', 'formal', 'Algodón', 1),
('Camiseta Vintage', 'Camiseta estilo retro', 21.99, 'Verde', 'casual', 'Algodón', 1),

-- ================= CHAQUETAS (2)
('Chaqueta Bomber Classic',  'Chaqueta ligera tipo bomber', 69.99, 'Negro', 'casual', 'Poliéster', 2),
('Chaqueta Denim Azul', 'Chaqueta vaquera clásica', 74.99, 'Azul', 'casual', 'Algodón', 2),
('Chaqueta Sport Pro', 'Chaqueta deportiva impermeable', 89.99, 'Rojo', 'deportivo', 'Poliéster', 2),
('Chaqueta Formal Slim', 'Chaqueta elegante para eventos', 119.99, 'Gris', 'formal', 'Lana', 2),
('Chaqueta Cuero Urban', 'Chaqueta moderna de cuero sintético', 139.99, 'Marrón', 'casual', 'Cuero', 2),

-- ================= ABRIGOS (3)
('Abrigo Largo Invierno', 'Abrigo cálido para invierno', 159.99, 'Negro', 'formal', 'Lana', 3),
('Abrigo Casual Urban', 'Abrigo moderno casual', 129.99, 'Gris', 'casual', 'Lana', 3),
('Abrigo Deportivo Tech', 'Abrigo técnico resistente al frío', 149.99, 'Azul', 'deportivo', 'Poliéster', 3),
('Abrigo Slim Elegance', 'Abrigo formal ajustado', 169.99, 'Camel', 'formal', 'Lana', 3),
('Abrigo Parka', 'Parka resistente al agua', 139.99, 'Verde', 'casual', 'Poliéster', 3),

-- ================= SUDADERAS (4)
('Sudadera Basic', 'Sudadera cómoda básica', 39.99, 'Negro', 'casual', 'Algodón', 4),
('Sudadera Hoodie Sport', 'Sudadera con capucha deportiva', 44.99, 'Azul', 'deportivo', 'Poliéster', 4),
('Sudadera Minimal', 'Sudadera diseño minimalista', 42.99, 'Blanco', 'casual', 'Algodón', 4),
('Sudadera Premium', 'Sudadera elegante urbana', 49.99, 'Gris', 'casual', 'Algodón', 4),
('Sudadera Training Pro', 'Sudadera técnica deportiva', 54.99, 'Rojo', 'deportivo', 'Poliéster', 4),

-- ================= PANTALONES (5)
('Pantalón Chino Beige', 'Pantalón chino elegante', 49.99, 'Beige', 'formal', 'Algodón', 5),
('Pantalón Jogger', 'Pantalón cómodo deportivo', 39.99, 'Negro', 'deportivo', 'Algodón', 5),
('Pantalón Slim Fit', 'Pantalón ajustado moderno', 59.99, 'Gris', 'casual', 'Algodón', 5),
('Pantalón Cargo', 'Pantalón con bolsillos laterales', 54.99, 'Verde', 'casual', 'Algodón', 5),
('Pantalón Formal Classic', 'Pantalón elegante de vestir', 69.99, 'Azul marino', 'formal', 'Lana', 5),

-- ================= VAQUEROS (6)
('Vaquero Slim Blue', 'Vaquero ajustado azul', 59.99, 'Azul', 'casual', 'Algodón', 6),
('Vaquero Regular Fit', 'Vaquero clásico', 54.99, 'Azul oscuro', 'casual', 'Algodón', 6),
('Vaquero Destroyed', 'Vaquero roto moderno', 64.99, 'Azul claro', 'casual', 'Algodón', 6),
('Vaquero Black Edition', 'Vaquero negro elegante', 69.99, 'Negro', 'casual', 'Algodón', 6),
('Vaquero Stretch Pro', 'Vaquero elástico cómodo', 72.99, 'Gris', 'casual', 'Algodón', 6),

-- ================= CALZADO (7)
('Zapatillas Urban', 'Zapatillas casual urbanas', 79.99, 'Blanco', 'casual', 'Cuero', 7),
('Zapatillas Running Pro', 'Zapatillas deportivas', 89.99, 'Azul', 'deportivo', 'Poliéster', 7),
('Zapato Formal Elegance', 'Zapato de vestir elegante', 119.99, 'Negro', 'formal', 'Cuero', 7),
('Botines Casual', 'Botines modernos', 99.99, 'Marrón', 'casual', 'Cuero', 7),
('Sneakers Classic', 'Sneakers estilo clásico', 74.99, 'Gris', 'casual', 'Cuero', 7),

-- ================= GORRAS (8)
('Gorra Classic', 'Gorra básica ajustable', 19.99, 'Negro', 'casual', 'Algodón', 8),
('Gorra Sport', 'Gorra deportiva transpirable', 22.99, 'Azul', 'deportivo', 'Poliéster', 8),
('Gorra Snapback', 'Gorra estilo urbano', 24.99, 'Rojo', 'casual', 'Algodón', 8),
('Gorra Premium', 'Gorra elegante minimalista', 29.99, 'Blanco', 'casual', 'Algodón', 8),
('Gorra Training', 'Gorra ligera para deporte', 21.99, 'Gris', 'deportivo', 'Poliéster', 8),

-- ================= CALCETINES (9)
('Calcetines Sport Pack', 'Pack de 3 calcetines deportivos', 14.99, 'Blanco', 'deportivo', 'Algodón', 9),
('Calcetines Classic', 'Calcetines negros elegantes', 12.99, 'Negro', 'formal', 'Algodón', 9),
('Calcetines Urban', 'Calcetines modernos casual', 13.99, 'Gris', 'casual', 'Algodón', 9),
('Calcetines Pro Running', 'Calcetines técnicos', 16.99, 'Azul', 'deportivo', 'Poliéster', 9),
('Calcetines Premium', 'Calcetines alta calidad', 18.99, 'Negro', 'formal', 'Algodón', 9),

-- ================= ACCESORIOS (10)
('Cinturón Cuero', 'Cinturón clásico elegante', 34.99, 'Marrón', 'formal', 'Cuero', 10),
('Mochila Urban', 'Mochila moderna casual', 49.99, 'Negro', 'casual', 'Poliéster', 10),
('Bolso Deportivo', 'Bolso para gimnasio', 39.99, 'Azul', 'deportivo', 'Poliéster', 10),
('Bufanda Lana', 'Bufanda cálida de invierno', 29.99, 'Gris', 'casual', 'Lana', 10),
('Pulsera Minimal', 'Pulsera estilo moderno', 19.99, 'Negro', 'casual', 'Cuero', 10);


INSERT INTO producto_tallas (id_producto, id_talla, stock)
SELECT p.id_producto, t.id_talla, 20
FROM productos p
CROSS JOIN tallas t;
