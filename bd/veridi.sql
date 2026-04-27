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
    oculto TINYINT(1) NOT NULL DEFAULT 0,
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
    foto_perfil VARCHAR(255) NULL,
    rol ENUM('cliente','admin') DEFAULT 'cliente',
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO usuarios (nombre, email, password, rol)
VALUES ('Administrador Veridi', 'admin@veridi.com', '$2y$10$z83aPWgL14cT47TZO0QH9OgNmO77Pa1BOZblk1.vfC6w3GHHEpfjq', 'admin');

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
    direccion TEXT NOT NULL,
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

-- ================================
-- TABLA VALORACIONES
-- ================================
CREATE TABLE valoraciones (
    id_valoracion INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT NOT NULL,
    id_pedido INT NOT NULL,
    estrellas TINYINT NOT NULL,
    comentario TEXT NULL,
    fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT chk_valoracion_estrellas CHECK (estrellas BETWEEN 1 AND 5),

    UNIQUE KEY uk_valoracion_usuario_pedido (id_usuario, id_pedido),

    FOREIGN KEY (id_usuario)
        REFERENCES usuarios(id_usuario)
        ON DELETE CASCADE,

    FOREIGN KEY (id_pedido)
        REFERENCES pedidos(id_pedido)
        ON DELETE CASCADE
);



-- INSERT INTO productos → añade aquí tus productos


INSERT INTO producto_tallas (id_producto, id_talla, stock)
SELECT p.id_producto, t.id_talla, 20
FROM productos p
CROSS JOIN tallas t;
