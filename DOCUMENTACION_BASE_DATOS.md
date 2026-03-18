# Documentación de Base de Datos - Veridi

## 1) Visión general
La base de datos del proyecto se llama **veridi** y está orientada a e-commerce de moda. Cubre:
- Catálogo (categorías, productos, tallas y stock por talla)
- Usuarios y roles
- Flujo de compra (pedidos y detalle de pedido)
- Funcionalidades de soporte (contacto y valoraciones)

El esquema está definido en `bd/veridi.sql` e incluye datos semilla de categorías, productos, tallas, stock inicial y un usuario administrador.

---

## 2) Tablas (explicación tabla por tabla)

## 2.1 `categorias`
**Propósito:** clasificar productos.

**Campos principales:**
- `id_categoria` (PK, autoincrement)
- `nombre` (único, obligatorio)

**Notas:**
- Es tabla maestra usada por `productos`.
- Tiene seed inicial con 10 categorías.

---

## 2.2 `productos`
**Propósito:** almacenar información de catálogo.

**Campos principales:**
- `id_producto` (PK)
- `nombre`, `descripcion`, `precio`
- `color`, `estilo` (ENUM: `casual`, `formal`, `deportivo`), `material`
- `id_categoria` (FK a `categorias`)
- `oculto` (flag lógico para ocultar producto)
- `fecha_creacion`

**Relaciones:**
- N:1 con `categorias`
- 1:N con `producto_tallas`
- 1:N con `pedido_detalle`
- 1:N con `carrito_detalle`

**Reglas FK importantes:**
- `id_categoria` usa `ON DELETE SET NULL` y `ON UPDATE CASCADE`.

---

## 2.3 `tallas`
**Propósito:** catálogo central de tallas.

**Campos principales:**
- `id_talla` (PK)
- `nombre` (único)

**Datos semilla:**
- `S`, `M`, `L`, `XL`, `40`, `41`, `42`, `Única`

**Relaciones:**
- 1:N con `producto_tallas`
- 1:N con `pedido_detalle`
- 1:N con `carrito_detalle`

---

## 2.4 `producto_tallas`
**Propósito:** modelar stock por talla para cada producto.

**Campos principales:**
- `id_producto` (FK)
- `id_talla` (FK)
- `stock`

**Clave primaria compuesta:**
- (`id_producto`, `id_talla`)

**Relaciones:**
- N:1 con `productos`
- N:1 con `tallas`

**Reglas FK:**
- Ambas con `ON DELETE CASCADE`.

**Nota importante:**
- El script inicial inserta stock para todas las combinaciones producto+talla con `CROSS JOIN` y stock 20.

---

## 2.5 `usuarios`
**Propósito:** cuentas del sistema (clientes y admin).

**Campos principales:**
- `id_usuario` (PK)
- `nombre`, `email` (único), `password`
- `foto_perfil`
- `rol` (`cliente` o `admin`)
- `fecha_registro`

**Relaciones:**
- 1:N con `pedidos`
- 1:N con `valoraciones`
- 1:N con `carrito`

**Notas:**
- Se inserta un usuario admin semilla con password hasheada.

---

## 2.6 `carrito`
**Propósito:** cabecera de carrito por usuario (modelo relacional clásico).

**Campos principales:**
- `id_carrito` (PK)
- `id_usuario` (FK)
- `fecha_creacion`

**Relaciones:**
- N:1 con `usuarios`
- 1:N con `carrito_detalle`

**Reglas FK:**
- `id_usuario` con `ON DELETE CASCADE`.

**Nota funcional del proyecto:**
- Aunque existe este modelo en BD, el flujo actual del proyecto trabaja principalmente con carrito en sesión PHP para runtime.

---

## 2.7 `carrito_detalle`
**Propósito:** líneas de producto/talla en carrito relacional.

**Campos principales:**
- `id_detalle` (PK)
- `id_carrito` (FK)
- `id_producto` (FK)
- `id_talla` (FK)
- `cantidad`

**Reglas FK:**
- todas con `ON DELETE CASCADE`.

**Nota:**
- Igual que `carrito`, su uso puede ser secundario frente al carrito en sesión actual.

---

## 2.8 `pedidos`
**Propósito:** cabecera de compra confirmada.

**Campos principales:**
- `id_pedido` (PK)
- `id_usuario` (FK)
- `direccion`
- `total`
- `estado` (`pendiente`, `pagado`, `enviado`, `cancelado`)
- `fecha`

**Regla FK clave:**
- `id_usuario` con `ON DELETE SET NULL` (preserva histórico del pedido).

---

## 2.9 `pedido_detalle`
**Propósito:** líneas del pedido (producto, talla, cantidad, precio al momento de compra).

**Campos principales:**
- `id_detalle` (PK)
- `id_pedido` (FK)
- `id_producto` (FK)
- `id_talla` (FK)
- `cantidad`
- `precio_unitario`

**Reglas FK:**
- `id_pedido` con `ON DELETE CASCADE`
- `id_producto` con `ON DELETE SET NULL`
- `id_talla` con `ON DELETE SET NULL`

**Nota importante:**
- Guardar `precio_unitario` evita perder trazabilidad histórica si cambia el precio del producto luego.

---

## 2.10 `contacto`
**Propósito:** mensajes enviados por usuarios/clientes desde formulario de contacto.

**Campos principales:**
- `id_contacto` (PK)
- `nombre`, `email`, `asunto`, `mensaje`
- `contrasena` (campo heredado; revisar si se necesita)
- `fecha`
- `leido` (booleano)

**Nota importante:**
- El campo `contrasena` en una tabla de contacto no suele ser recomendable. Conviene validar si realmente se usa.

---

## 2.11 `valoraciones`
**Propósito:** valoración de pedidos por usuario.

**Campos principales:**
- `id_valoracion` (PK)
- `id_usuario` (FK, obligatorio)
- `id_pedido` (FK, obligatorio)
- `estrellas` (1-5)
- `comentario`
- `fecha`

**Restricciones clave:**
- `CHECK (estrellas BETWEEN 1 AND 5)`
- `UNIQUE (id_usuario, id_pedido)` para impedir doble valoración del mismo pedido por el mismo usuario.

**Reglas FK:**
- ambas con `ON DELETE CASCADE`.

---

## 3) Relaciones entre tablas (resumen)

- `categorias` 1 --- N `productos`
- `productos` 1 --- N `producto_tallas`
- `tallas` 1 --- N `producto_tallas`
- `usuarios` 1 --- N `pedidos`
- `pedidos` 1 --- N `pedido_detalle`
- `productos` 1 --- N `pedido_detalle`
- `tallas` 1 --- N `pedido_detalle`
- `usuarios` 1 --- N `valoraciones`
- `pedidos` 1 --- N `valoraciones` (con unicidad por usuario+pedido)
- `usuarios` 1 --- N `carrito`
- `carrito` 1 --- N `carrito_detalle`
- `productos` 1 --- N `carrito_detalle`
- `tallas` 1 --- N `carrito_detalle`

---

## 4) Aspectos importantes a saber

### 4.1 Integridad y borrado
- Hay mezcla intencional de `CASCADE` y `SET NULL` según necesidad de histórico.
- En pedidos se prioriza conservar historial (`SET NULL` en usuario, producto y talla en algunos casos).

### 4.2 Modelo de carrito
- Existen tablas `carrito` y `carrito_detalle`, pero el flujo activo del proyecto usa carrito en sesión PHP para operación diaria.
- Esto es válido, pero conviene documentarlo para evitar confusión en mantenimiento.

### 4.3 Productos ocultos
- `productos.oculto` permite despublicar sin borrar datos.
- Las APIs usan esta bandera para filtrar catálogo visible.

### 4.4 Restricciones de negocio
- `valoraciones` impide valoraciones duplicadas por pedido/usuario.
- `estrellas` está acotado de 1 a 5.

### 4.5 Datos semilla
- El SQL carga muchas filas de ejemplo (productos, categorías, tallas, stock inicial, admin).
- Útil en desarrollo/demo, pero en producción suele separarse seed de esquema.

### 4.6 Posibles mejoras futuras
- Añadir índices adicionales en columnas de filtrado frecuente (`productos.precio`, `productos.estilo`, `productos.color`, `productos.oculto`, `productos.fecha_creacion`).
- Revisar necesidad del campo `contacto.contrasena`.
- Si se migra a carrito persistente real, unificar uso con `carrito`/`carrito_detalle` y eliminar dependencia de sesión para ese módulo.

---

## 5) Fuentes del modelo
- Script principal de esquema y seed: `bd/veridi.sql`
