-- ================================
-- ELIMINAR Y CREAR BASE DE DATOS
-- ================================

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
('Ãšnica');

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
-- TABLA DESEOS_USUARIO
-- ================================
CREATE TABLE deseos_usuario (
    id_deseo INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT NOT NULL,
    id_producto INT NOT NULL,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    UNIQUE KEY uk_deseos_usuario_producto (id_usuario, id_producto),

    FOREIGN KEY (id_usuario)
        REFERENCES usuarios(id_usuario)
        ON DELETE CASCADE,

    FOREIGN KEY (id_producto)
        REFERENCES productos(id_producto)
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

-- ================================
-- TABLA VALORACIONES POR PRODUCTO
-- ================================
CREATE TABLE valoraciones_producto (
    id_valoracion_producto INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT NOT NULL,
    id_producto INT NOT NULL,
    id_pedido INT NULL,
    estrellas TINYINT NOT NULL,
    comentario TEXT NULL,
    fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT chk_valoracion_producto_estrellas CHECK (estrellas BETWEEN 1 AND 5),

    UNIQUE KEY uk_valoracion_producto_usuario_producto (id_usuario, id_producto),

    FOREIGN KEY (id_usuario)
        REFERENCES usuarios(id_usuario)
        ON DELETE CASCADE,

    FOREIGN KEY (id_producto)
        REFERENCES productos(id_producto)
        ON DELETE CASCADE,

    FOREIGN KEY (id_pedido)
        REFERENCES pedidos(id_pedido)
        ON DELETE SET NULL
);



-- ================================
-- INSERTS DE PRODUCTOS (basados en /imgnuevas)
-- ================================
INSERT INTO productos (nombre, descripcion, precio, color, estilo, material, id_categoria) VALUES
('Camiseta Veridi Blanca', 'Camiseta basica blanca con branding Veridi.', 19.90, 'Blanco', 'casual', 'Algodon', 1),
('Camiseta Deportiva Negra', 'Camiseta tecnica transpirable para entrenamiento.', 24.90, 'Negro', 'deportivo', 'Poliester tecnico', 1),
('Camiseta Deportiva Azul', 'Camiseta tecnica azul de secado rapido.', 24.90, 'Azul', 'deportivo', 'Poliester tecnico', 1),
('Camiseta Deportiva Blanca', 'Camiseta deportiva blanca ligera y comoda.', 24.90, 'Blanco', 'deportivo', 'Poliester tecnico', 1),
('Camiseta Veridi Gris', 'Camiseta gris de uso diario con corte regular.', 21.90, 'Gris', 'casual', 'Algodon', 1),
('Camiseta Negra Logo Blanco', 'Camiseta negra con logo frontal en contraste.', 22.90, 'Negro', 'casual', 'Algodon', 1),
('Camiseta Veridi Rosa', 'Camiseta rosa suave para look urbano.', 21.90, 'Rosa', 'casual', 'Algodon', 1),
('Camiseta Veridi Azul', 'Camiseta azul clasica con identidad Veridi.', 21.90, 'Azul', 'casual', 'Algodon', 1),
('Camiseta Veridi Negra', 'Camiseta negra versatil para cualquier combinacion.', 21.90, 'Negro', 'casual', 'Algodon', 1),
('Polo Blanco Veridi', 'Polo blanco de estilo limpio y elegante.', 29.90, 'Blanco', 'formal', 'Algodon pique', 1),
('Polo Negro Veridi', 'Polo negro de estilo minimal y atemporal.', 29.90, 'Negro', 'formal', 'Algodon pique', 1),

('Chaqueta Blanca con Capucha', 'Chaqueta ligera blanca con capucha ajustable.', 49.90, 'Blanco', 'casual', 'Poliester', 2),
('Chaqueta Bomber Negra', 'Bomber negra de estilo urbano.', 59.90, 'Negro', 'casual', 'Nailon', 2),
('Chaqueta Bomber Verde', 'Bomber verde para outfit streetwear.', 59.90, 'Verde', 'casual', 'Nailon', 2),
('Chaqueta Negra con Capucha', 'Chaqueta negra comoda para entretiempo.', 54.90, 'Negro', 'deportivo', 'Poliester', 2),
('Chaqueta Vaquera Azul', 'Chaqueta denim azul de corte clasico.', 64.90, 'Azul', 'casual', 'Denim', 2),
('Chaqueta Vaquera Azul Oscuro', 'Chaqueta vaquera en tono azul oscuro.', 66.90, 'Azul oscuro', 'casual', 'Denim', 2),

('Abrigo Plumas Veridi', 'Abrigo acolchado para dias frios.', 89.90, 'Negro', 'casual', 'Poliester acolchado', 3),
('Abrigo Plumas Veridi Cani', 'Abrigo plumas de estilo marcado y urbano.', 94.90, 'Negro', 'casual', 'Poliester acolchado', 3),

('Sudadera Negra Veridi', 'Sudadera negra basica para uso diario.', 39.90, 'Negro', 'casual', 'Algodon felpado', 4),

('Pantalon Chandal Corto Gris', 'Short de chandal gris para deporte y descanso.', 27.90, 'Gris', 'deportivo', 'Algodon', 5),
('Pantalon Chandal Corto Rosa', 'Short deportivo rosa de tacto suave.', 27.90, 'Rosa', 'deportivo', 'Algodon', 5),
('Pantalon Chandal Largo Azul', 'Pantalon largo azul de entrenamiento.', 34.90, 'Azul', 'deportivo', 'Algodon', 5),
('Pantalon Chandal Largo Gris', 'Pantalon largo gris con ajuste comodo.', 34.90, 'Gris', 'deportivo', 'Algodon', 5),
('Pantalon Chandal Largo Negro', 'Pantalon largo negro versatil para gimnasio.', 34.90, 'Negro', 'deportivo', 'Algodon', 5),
('Pantalon Chandal Largo Rosa', 'Pantalon largo rosa para look athleisure.', 34.90, 'Rosa', 'deportivo', 'Algodon', 5),
('Pantalon Chandal Corto Azul Claro', 'Short de chandal azul claro.', 27.90, 'Azul claro', 'deportivo', 'Algodon', 5),
('Pantalon Chandal Corto Blanco', 'Short blanco de chandal para verano.', 27.90, 'Blanco', 'deportivo', 'Algodon', 5),
('Pantalon Chandal Corto Negro', 'Short negro deportivo basico.', 27.90, 'Negro', 'deportivo', 'Algodon', 5),

('Vaqueros Azules Claros', 'Jeans azul claro de corte regular.', 44.90, 'Azul claro', 'casual', 'Denim', 6),
('Vaqueros Azules Claros Rotos', 'Jeans azul claro con roturas frontales.', 46.90, 'Azul claro', 'casual', 'Denim', 6),
('Vaqueros Cortos Azules', 'Bermuda vaquera azul para temporada calida.', 36.90, 'Azul', 'casual', 'Denim', 6),
('Vaqueros Cortos Azules Rotos', 'Bermuda vaquera azul con acabado roto.', 37.90, 'Azul', 'casual', 'Denim', 6),
('Vaqueros Cortos Negros', 'Bermuda vaquera negra de estilo urbano.', 36.90, 'Negro', 'casual', 'Denim', 6),
('Vaqueros Cortos Negros Rotos', 'Bermuda vaquera negra con roturas.', 37.90, 'Negro', 'casual', 'Denim', 6),
('Vaqueros Negros', 'Jeans negros para combinacion formal o casual.', 44.90, 'Negro', 'casual', 'Denim', 6),
('Vaqueros Negros Rotos', 'Jeans negros con detalles desgastados.', 46.90, 'Negro', 'casual', 'Denim', 6),

('Gorra Azul Classic', 'Gorra azul de visera curva.', 18.90, 'Azul', 'casual', 'Algodon', 8),
('Gorra Azul Sport', 'Gorra azul de estilo deportivo.', 19.90, 'Azul', 'deportivo', 'Algodon', 8),
('Gorra Blanca Veridi', 'Gorra blanca con logo frontal.', 18.90, 'Blanco', 'casual', 'Algodon', 8),
('Gorra Negra Veridi', 'Gorra negra basica para diario.', 18.90, 'Negro', 'casual', 'Algodon', 8),
('Gorra Negra Total', 'Gorra negra monocromatica.', 19.90, 'Negro', 'casual', 'Algodon', 8),
('Gorra Roja Veridi', 'Gorra roja para un look llamativo.', 18.90, 'Rojo', 'casual', 'Algodon', 8),

('Calcetines Blancos Veridi', 'Calcetines blancos comodos para uso diario.', 9.90, 'Blanco', 'deportivo', 'Algodon', 9),
('Calcetines Negros Veridi', 'Calcetines negros resistentes.', 9.90, 'Negro', 'deportivo', 'Algodon', 9),

('Boxer Veridi', 'Prenda interior comoda de ajuste elastico.', 12.90, 'Negro', 'casual', 'Algodon elastico', 10),

('Zapatillas Urban Negras', 'Sneaker urbana negra de perfil bajo para looks casuales.', 90.00, 'Negro', 'casual', 'Cuero sintetico', 7),
('Zapatillas Running Azul', 'Zapatilla running azul con amortiguacion ligera y gran transpiracion.', 64.90, 'Azul', 'deportivo', 'Malla tecnica', 7),
('Zapatillas Running Blancas', 'Zapatilla running blanca, comoda para entrenamiento diario.', 64.90, 'Blanco', 'deportivo', 'Malla tecnica', 7),
('Zapatillas Running Negras', 'Zapatilla running negra con suela de alta respuesta.', 64.90, 'Negro', 'deportivo', 'Malla tecnica', 7),
('Zapatillas TN Blancas', 'Sneaker blanca de estilo urbano con camara visible.', 100.00, 'Blanco', 'casual', 'Textil y sintetico', 7),
('Zapatillas TN Negras', 'Sneaker negra de estilo urbano con camara visible.', 100.00, 'Negro', 'casual', 'Textil y sintetico', 7),
('Zapatillas Urban Blancas', 'Sneaker urbana blanca de perfil bajo para combinaciones limpias.', 90.00, 'Blanco', 'casual', 'Cuero sintetico', 7),

('Pantalon Cargo Beige', 'Pantalon cargo premium en tono beige con corte estilizado y acabado formal.', 59.90, 'Beige', 'formal', 'Algodon premium con elastano', 5),
('Pantalon Cargo Blanco', 'Pantalon cargo blanco con fit moderno para estilismos elegantes de diario.', 59.90, 'Blanco', 'formal', 'Algodon premium con elastano', 5),
('Pantalon Cargo Negro', 'Pantalon cargo negro de presencia sobria para combinaciones formales contemporaneas.', 59.90, 'Negro', 'formal', 'Algodon premium con elastano', 5),
('Pantalon Cargo Verde', 'Pantalon cargo verde oliva con estructura limpia y estilo formal actual.', 59.90, 'Verde', 'formal', 'Algodon premium con elastano', 5);


INSERT INTO producto_tallas (id_producto, id_talla, stock)
SELECT p.id_producto, t.id_talla, 20
FROM productos p
CROSS JOIN tallas t
LEFT JOIN producto_tallas pt
    ON pt.id_producto = p.id_producto
   AND pt.id_talla = t.id_talla
WHERE pt.id_producto IS NULL;
