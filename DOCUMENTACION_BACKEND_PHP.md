# Documentación Backend (PHP) - Veridi

## 1) Visión general
La carpeta `php/` contiene el **backend API** que consume el frontend React. El diseño actual es tipo **API por endpoint** (un archivo por módulo funcional), con autenticación por **sesión PHP** y respuestas en **JSON**.

### Objetivos del backend
- Exponer datos de catálogo y detalle de producto.
- Gestionar autenticación, perfil y sesión de usuario.
- Gestionar carrito y favoritos (principalmente en sesión).
- Procesar checkout/pedidos y valoraciones.
- Exponer panel de administración para productos/stock/usuarios.

### Patrón común de los endpoints
La mayoría de archivos siguen este patrón:
1. `require_once "../config/conexion.php"`
2. `session_start()` si la sesión no está iniciada.
3. Cabeceras CORS (permitiendo `localhost:5173` / `127.0.0.1:5173` para desarrollo).
4. `Content-Type: application/json; charset=utf-8`.
5. Manejo de `OPTIONS` (preflight) devolviendo `204`.
6. Validación de método (`GET`/`POST`) y permisos.
7. Lógica del endpoint + `echo json_encode(...)`.

---

## 2) Dependencias importantes (fuera de `php/`)
- `config/conexion.php`: conexión PDO a MySQL.
- `config/imagenes.php`: mapeo manual de imagen por producto (`obtenerImagenProducto`).

Aunque no están dentro de la carpeta `php/`, son base del backend y se importan desde varios endpoints.

---

## 3) Resumen de endpoints

| Archivo | Método(s) | Función principal |
|---|---|---|
| `api_auth_react.php` | POST | Login, registro y logout |
| `api_usuario.php` | GET | Estado de sesión, perfil resumido, contadores, historial |
| `api_tienda.php` | GET | Catálogo con filtros, ordenación, paginación |
| `api_producto_detalle.php` | GET | Detalle de producto, tallas, relacionados, estado favorito |
| `api_carrito.php` | GET, POST | Lectura y operaciones de carrito |
| `api_deseos.php` | GET, POST | Favoritos (wishlist) |
| `api_checkout.php` | GET, POST | Datos de checkout y creación de pedido |
| `api_confirmacion.php` | GET | Confirmación de pedido + detalle + estado de valoración |
| `api_valoracion.php` | POST | Alta/edición de valoración de pedido |
| `api_valoraciones.php` | GET | Listado público de valoraciones |
| `api_contacto.php` | GET, POST | Datos para formulario y envío de mensaje |
| `api_perfil_react.php` | POST | Edición de nombre y foto de perfil |
| `api_admin.php` | GET, POST | Operaciones del panel admin |
| `api_inicio.php` | GET | Productos destacados de inicio |

---

## 4) Documentación archivo por archivo

## 4.1 `api_auth_react.php` (CRÍTICO)
**Responsabilidad:** autenticación React (register/login/logout).

### Acciones (`action` en body JSON)
- `register`: valida campos, email único y longitud mínima de password, guarda hash.
- `login`: valida credenciales (`password_verify`), setea sesión (`usuario_id`, `usuario_nombre`, `usuario_rol`) e inicializa `carrito` y `deseos` en sesión.
- `logout`: limpia sesión y cookie de sesión.

### Aspectos clave
- Solo acepta `POST`.
- Devuelve mensajes funcionales para UI de login/registro.
- Usa hash de contraseña (`PASSWORD_DEFAULT`).

---

## 4.2 `api_usuario.php` (CRÍTICO)
**Responsabilidad:** endpoint de contexto de usuario para el header/app.

### Qué devuelve
- `logueado` (true/false)
- `contador.carrito` y `contador.deseos`
- Si está logueado: perfil, historial de pedidos y valoraciones recientes.

### Aspectos clave
- Endpoint central para sincronizar estado global del frontend.
- Incluye una migración defensiva runtime: intenta agregar `foto_perfil` en `usuarios` si falta.

---

## 4.3 `api_tienda.php` (CRÍTICO)
**Responsabilidad:** catálogo principal con filtros.

### Entradas (`GET`)
- `buscar`, `categoria`, `precio_min`, `precio_max`
- arrays: `talla[]`, `color[]`, `estilo[]`
- `ordenar` (`nombre_asc`, `nombre_desc`, `precio_asc`, `precio_desc`)
- `pagina`

### Salida
- `productos` (nombre, precio, imagen, categoría, estado favorito, etc.)
- `filtros` (categorías/tallas/colores/estilos disponibles)
- `paginacion`
- `contador` (carrito/favoritos en sesión)

### Aspectos clave
- Excluye productos ocultos (`oculto = 0 OR NULL`).
- Construye SQL dinámica de filtros con parámetros preparados.

---

## 4.4 `api_producto_detalle.php` (ALTA)
**Responsabilidad:** detalle de un producto.

### Qué hace
- Valida `id`.
- Recupera producto + categoría (si está visible).
- Recupera tallas con stock > 0 (con lógica por tipo de categoría).
- Recupera productos relacionados por categoría.
- Devuelve si el producto está en favoritos de la sesión.

### Aspectos clave
- Ordena tallas con prioridad (`Única`, `S-XL`, numeración).

---

## 4.5 `api_carrito.php` (CRÍTICO)
**Responsabilidad:** gestionar carrito en sesión.

### Requisitos
- Usuario logueado (si no, `requiresLogin`).

### Operaciones
- `GET`: devuelve ítems, subtotales, total y nombre de talla.
- `POST` con `action`:
  - `add_item` (valida stock por producto+talla)
  - `update_quantity` (incremento/decremento con control de stock)
  - `remove_item`
  - `clear_cart`

### Aspectos clave
- Carrito indexado por `itemKey = id_producto_id_talla`.
- Controla límites por stock en cada operación.

---

## 4.6 `api_deseos.php` (ALTA)
**Responsabilidad:** favoritos (wishlist) en sesión.

### Requisitos
- Usuario logueado para `GET` y `POST`.

### Operaciones
- `GET`: lista favoritos y total.
- `POST`:
  - `add`
  - `remove`
  - `check`
  - `move_to_cart` (redirige al detalle para elegir talla)

### Aspectos clave
- Devuelve `total` para actualizar contadores en UI.

---

## 4.7 `api_checkout.php` (CRÍTICO)
**Responsabilidad:** checkout y creación de pedido.

### `GET`
- Devuelve datos de checkout: usuario, ítems carrito, total y bandera `isEmpty`.

### `POST`
- Valida campos de dirección + email/password.
- Revalida credenciales contra BD.
- Crea pedido (`pedidos`) y líneas (`pedido_detalle`) dentro de transacción.
- Vacía carrito en sesión al confirmar.

### Aspectos clave
- Usa `beginTransaction/commit/rollBack`.
- Guarda `precio_unitario` en detalle para trazabilidad histórica.

---

## 4.8 `api_confirmacion.php` (ALTA)
**Responsabilidad:** datos de la pantalla de confirmación de pedido.

### Qué hace
- Requiere sesión.
- Valida `id` de pedido y que pertenezca al usuario actual.
- Devuelve cabecera del pedido, detalle de líneas y si ya fue valorado.

---

## 4.9 `api_valoracion.php` (ALTA)
**Responsabilidad:** crear o actualizar valoración de pedido.

### Validaciones
- Usuario logueado.
- `id_pedido` válido y perteneciente al usuario.
- `estrellas` entre 1 y 5.
- Comentario truncado a 500 chars.

### Lógica
- Si existe valoración previa del usuario para ese pedido: `UPDATE`.
- Si no existe: `INSERT`.

---

## 4.10 `api_valoraciones.php` (MEDIA)
**Responsabilidad:** listado global de valoraciones.

### Funcionalidad
- Filtro opcional por estrellas (`?estrellas=1..5`).
- Devuelve resumen agregado (`total`, `promedio`) + lista ordenada por fecha.

---

## 4.11 `api_contacto.php` (MEDIA-ALTA)
**Responsabilidad:** formulario de contacto para usuario autenticado.

### `GET`
- Devuelve datos de precarga (email web, email/nombre del usuario si está logueado).

### `POST`
- Requiere usuario logueado.
- Exige que el email enviado coincida con el de su cuenta.
- Requiere contraseña y la valida con hash del usuario.
- Inserta en tabla `contacto`.

### Aspectos a vigilar
- Guarda `contrasena` en tabla `contacto` (no recomendado para producción).

---

## 4.12 `api_perfil_react.php` (ALTA)
**Responsabilidad:** edición de perfil del usuario autenticado.

### Acciones (`POST` con form-data)
- `name`: actualiza nombre.
- `photo`: valida y guarda foto (`jpg/png/webp`, máx 5MB), elimina foto anterior en `img/perfiles`.

### Aspectos clave
- Mantiene sesión (`usuario_nombre`) sincronizada tras cambio de nombre.

---

## 4.13 `api_admin.php` (CRÍTICO)
**Responsabilidad:** backend del panel de administración.

### Seguridad
- Requiere login y rol `admin`.
- Devuelve `401` o `403` si no cumple permisos.

### `GET`
- Devuelve dataset del panel: categorías, tallas, productos (incluye `oculto`, stock total), usuarios.

### `POST` acciones
- `create_product`
- `edit_product`
- `delete_product`
- `toggle_hide`
- `adjust_stock`

### Aspectos clave
- Incluye migración defensiva de `productos.oculto`.
- Recalcula y devuelve `data` completo tras cada acción para refrescar frontend.

---

## 4.14 `api_inicio.php` (MEDIA)
**Responsabilidad:** destacados de home.

### Qué calcula
- `mas_vendido`: según suma de cantidades en `pedido_detalle`.
- `nuevo`: más reciente por `fecha_creacion`.
- `oferta`: producto visible de menor precio.

### Aspectos clave
- Excluye productos ocultos.
- Devuelve estructura normalizada con imagen.

---

## 5) Aspectos importantes del backend a tener en cuenta

### 5.1 Estado en sesión
El backend actual usa sesión para:
- autenticación (`usuario_id`, `usuario_rol`, etc.),
- carrito,
- favoritos.

Esto simplifica implementación, pero en escalado horizontal requiere estrategia de sesión compartida.

### 5.2 CORS limitado a entorno local
La política CORS está orientada a desarrollo (`localhost:5173`). En producción habría que ajustar `origin` permitido.

### 5.3 Convención de errores funcionales
Muchos endpoints devuelven `ok: false` con `message` claro, y en algunos casos banderas específicas (`requiresLogin`, `requiresAdmin`). Esto facilita UX del frontend.

### 5.4 Migraciones runtime
Hay endpoints que intentan `ALTER TABLE` en ejecución (ej. `api_usuario`, `api_admin`). Es práctico para compatibilidad, pero recomendable moverlo a migraciones formales en producción.

### 5.5 Uso mixto de modelo persistente y sesión
Existen tablas relacionales de carrito (`carrito`, `carrito_detalle`), pero el flujo principal operativo usa sesión para carrito/favoritos. Conviene decidir una estrategia única a medio plazo.

---

## 6) Recomendaciones de evolución (backend)
- Centralizar utilidades repetidas (CORS, respuesta JSON, auth guards) en helpers comunes.
- Incorporar logs estructurados de errores (sin exponer detalles sensibles al cliente).
- Añadir validación más estricta por esquema (por ejemplo, librería de validación).
- Revisar el manejo de credenciales en `api_contacto` para no persistir contraseña fuera de `usuarios`.
- Definir migraciones versionadas para cambios de esquema.

---

## 7) Fuente analizada
- Carpeta backend: `php/`
- Endpoints incluidos: `api_admin.php`, `api_auth_react.php`, `api_carrito.php`, `api_checkout.php`, `api_confirmacion.php`, `api_contacto.php`, `api_deseos.php`, `api_inicio.php`, `api_perfil_react.php`, `api_producto_detalle.php`, `api_tienda.php`, `api_usuario.php`, `api_valoracion.php`, `api_valoraciones.php`
