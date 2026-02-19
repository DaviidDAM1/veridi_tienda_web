-- Crear base de datos
CREATE DATABASE veridi CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE veridi;

-- =========================
-- TABLA: categorias
-- =========================

CREATE TABLE categorias (
    id_categoria INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(50) NOT NULL
);

-- =========================
-- TABLA: usuarios
-- =========================
CREATE TABLE usuarios (
    id_usuario INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- =========================
-- TABLA: productos
-- =========================
CREATE TABLE productos (
    id_producto INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    descripcion TEXT,
    precio DECIMAL(10,2) NOT NULL,
    talla VARCHAR(10),
    stock INT NOT NULL,
    id_categoria INT,
    CONSTRAINT fk_productos_categoria
        FOREIGN KEY (id_categoria)
        REFERENCES categorias(id_categoria)
        ON DELETE SET NULL
        ON UPDATE CASCADE
);

-- =========================
-- TABLA: carritos
-- =========================
CREATE TABLE carritos (
    id_carrito INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT UNIQUE,
    CONSTRAINT fk_carritos_usuario
        FOREIGN KEY (id_usuario)
        REFERENCES usuarios(id_usuario)
        ON DELETE CASCADE
        ON UPDATE CASCADE
);

-- =========================
-- TABLA: carrito_productos
-- =========================
CREATE TABLE carrito_productos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_carrito INT,
    id_producto INT,
    cantidad INT NOT NULL,
    CONSTRAINT fk_cp_carrito
        FOREIGN KEY (id_carrito)
        REFERENCES carritos(id_carrito)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    CONSTRAINT fk_cp_producto
        FOREIGN KEY (id_producto)
        REFERENCES productos(id_producto)
        ON DELETE CASCADE
        ON UPDATE CASCADE
);

-- =========================
-- TABLA: pedidos
-- =========================
CREATE TABLE pedidos (
    id_pedido INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT,
    fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    total DECIMAL(10,2),
    CONSTRAINT fk_pedidos_usuario
        FOREIGN KEY (id_usuario)
        REFERENCES usuarios(id_usuario)
        ON DELETE CASCADE
        ON UPDATE CASCADE
);

-- =========================
-- TABLA: pedido_productos
-- =========================
CREATE TABLE pedido_productos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_pedido INT,
    id_producto INT,
    cantidad INT NOT NULL,
    precio DECIMAL(10,2) NOT NULL,
    CONSTRAINT fk_pp_pedido
        FOREIGN KEY (id_pedido)
        REFERENCES pedidos(id_pedido)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    CONSTRAINT fk_pp_producto
        FOREIGN KEY (id_producto)
        REFERENCES productos(id_producto)
        ON DELETE CASCADE
        ON UPDATE CASCADE
);
CREATE TABLE contactos (
    id_contacto INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL,
    tipo ENUM('consulta','queja','reclamacion','otro') NOT NULL,
    mensaje TEXT NOT NULL,
    fecha DATETIME DEFAULT CURRENT_TIMESTAMP,
    id_usuario INT NULL,
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario) ON DELETE SET NULL
);


ALTER TABLE productos
ADD mas_vendido BOOLEAN DEFAULT FALSE,
ADD en_oferta BOOLEAN DEFAULT FALSE,
ADD es_nuevo BOOLEAN DEFAULT FALSE;

INSERT INTO categorias (nombre) VALUES
('Camisetas'),
('Pantalones'),
('Chaquetas');

INSERT INTO productos (nombre, descripcion, precio, talla, stock, id_categoria, mas_vendido, en_oferta, es_nuevo)
VALUES
('Camiseta Veridi Classic', 'Camiseta básica de algodón', 19.99, 'M', 50, 1, TRUE, FALSE, FALSE),
('Pantalón Urban Style', 'Pantalón moderno slim fit', 39.99, 'L', 30, 2, FALSE, TRUE, FALSE),
('Chaqueta Winter Pro', 'Chaqueta ideal para invierno', 79.99, 'XL', 20, 3, FALSE, FALSE, TRUE);

INSERT INTO categorias (nombre) VALUES 
('Abrigos'),
('Sudaderas'),
('Camisas'),
('Bermudas'),
('Calzado'),
('Accesorios');

INSERT INTO productos (nombre, descripcion, precio, talla, stock, id_categoria, mas_vendido, en_oferta, es_nuevo) VALUES
('Camiseta Básica Blanca', 'Camiseta de algodón 100% blanca, cómoda y ligera', 15.99, 'S', 50, 1, TRUE, FALSE, FALSE),
('Camiseta Básica Negra', 'Camiseta de algodón negro, clásica y versátil', 15.99, 'M', 40, 1, FALSE, TRUE, FALSE),
('Pantalón Vaquero Slim', 'Vaquero azul ajustado con estilo moderno', 39.99, 'L', 30, 2, TRUE, FALSE, FALSE),
('Pantalón Chino Beige', 'Pantalón chino beige elegante y cómodo', 42.99, 'M', 25, 2, FALSE, TRUE, FALSE),
('Chaqueta de Cuero', 'Chaqueta de cuero sintético con cremallera frontal', 79.99, 'M', 20, 3, FALSE, TRUE, FALSE),
('Chaqueta Bomber', 'Chaqueta bomber ligera con bolsillos laterales', 69.99, 'L', 18, 3, TRUE, FALSE, TRUE),
('Abrigo Largo de Lana', 'Abrigo elegante de lana, ideal para invierno', 129.99, 'L', 15, 4, FALSE, FALSE, TRUE),
('Abrigo Corto de Paño', 'Abrigo de paño gris corto, moderno', 119.99, 'M', 12, 4, TRUE, FALSE, FALSE),
('Sudadera con Capucha', 'Sudadera cómoda con capucha y bolsillo frontal', 29.99, 'XL', 25, 5, TRUE, FALSE, TRUE),
('Sudadera Estampada', 'Sudadera con estampado frontal moderno', 34.99, 'L', 20, 5, FALSE, TRUE, TRUE),
('Camisa Casual Azul', 'Camisa de algodón azul, manga larga', 39.99, 'M', 30, 6, FALSE, TRUE, FALSE),
('Camisa Formal Blanca', 'Camisa blanca de vestir, elegante', 49.99, 'L', 18, 6, TRUE, FALSE, TRUE),
('Bermuda Vaquera', 'Bermuda vaquera azul para verano', 24.99, 'M', 40, 7, FALSE, TRUE, TRUE),
('Bermuda Chino', 'Bermuda chino beige, cómoda y ligera', 29.99, 'L', 35, 7, TRUE, FALSE, FALSE),
('Zapatillas Deportivas', 'Zapatillas cómodas para uso diario', 59.99, '42', 35, 8, TRUE, TRUE, FALSE),
('Botas de Piel Marrón', 'Botas de piel marrón, cómodas y duraderas', 89.99, '41', 25, 8, TRUE, FALSE, TRUE),
('Gorra Negra', 'Gorra de algodón negro con logo bordado', 12.99, 'Única', 50, 9, FALSE, TRUE, TRUE),
('Cinturón de Piel', 'Cinturón de piel marrón, clásico', 19.99, 'Única', 40, 9, FALSE, FALSE, TRUE),
('Calcetines Deportivos', 'Pack de 3 calcetines transpirables', 9.99, 'Única', 60, 9, FALSE, TRUE, FALSE),
('Camiseta Deportiva', 'Camiseta transpirable para deporte', 22.99, 'M', 35, 1, TRUE, TRUE, TRUE);
