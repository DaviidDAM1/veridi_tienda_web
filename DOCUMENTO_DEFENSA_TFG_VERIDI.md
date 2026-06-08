# Documento De Defensa TFG - Veridi

## 1. Resumen Del Proyecto

Este proyecto es una tienda web de ropa desarrollada con una arquitectura separada en tres capas:

1. Frontend en React con Vite.
2. Backend en PHP con endpoints propios.
3. Base de datos MySQL.

La idea principal que debo transmitir en la defensa es que no hice una web monolitica sin orden, sino un sistema con responsabilidades separadas:

- React se encarga de la interfaz, navegacion y experiencia de usuario.
- PHP se encarga de la logica de negocio, sesiones, validaciones y acceso a datos.
- MySQL almacena usuarios, productos, stock, carrito, pedidos, valoraciones y favoritos.

---

## 2. Arquitectura General

### Frontend

- Entrada principal: `frontend/src/main.jsx`
- Enrutado principal: `frontend/src/App.jsx`
- Layout general de la app: `frontend/src/components/AppLayout.jsx`
- Cliente HTTP centralizado: `frontend/src/services/api.js`

El frontend usa React y React Router. En `main.jsx` se monta la aplicacion y se usa `HashRouter` para evitar problemas de recarga en rutas internas cuando la app esta desplegada como SPA.

### Backend

- Conexion a base de datos: `config/conexion.php`
- Endpoints PHP: carpeta `php/`
- Utilidad de imagenes: `config/imagenes.php`
- Logica de ofertas: `php/ofertas.php`

El backend devuelve JSON a React. Cada funcionalidad importante tiene su propio endpoint.

### Base De Datos

- Esquema principal: `bd/veridi.sql`

La base de datos esta separada por entidades funcionales: usuarios, productos, tallas, stock, carrito, pedidos, valoraciones, favoritos y contacto.

---

## 3. Como Se Levanta El Sistema

Si me preguntan como se arranca el proyecto, la respuesta es:

1. Importo la base de datos desde `bd/veridi.sql` en MySQL.
2. Configuro la conexion en `config/conexion.php`.
3. Levanto Apache y MySQL con XAMPP para servir el backend en `http://localhost/veridi_tienda_web`.
4. Entro en `frontend/` y ejecuto `npm install`.
5. Ejecuto `npm run dev` para lanzar Vite.

### Archivo clave para local

- `frontend/vite.config.js`

Este archivo tiene proxies para que las llamadas del frontend a `/php`, `/img` e `/imgnuevas` se redirijan al backend en local.

### Scripts del frontend

- `frontend/package.json`

Scripts principales:

- `npm run dev`
- `npm run build`
- `npm run preview`

### Comprobacion real

El build del frontend se ha verificado con exito usando `npm run build`.

---

## 4. Despliegue

Si me preguntan como esta desplegado, debo explicar que frontend y backend no estan exactamente en el mismo sitio.

### Archivos implicados

- `vercel.json`
- `frontend/vercel.json`
- `.github/workflows/deploy-hostinger.yml`

### Explicacion

- El frontend se sirve como SPA.
- Las rutas `/php`, `/img` e `/imgnuevas` se reescriben para apuntar al servidor remoto en Hostinger.
- El backend PHP y recursos asociados se despliegan por FTP mediante GitHub Actions.

### Idea fuerte para decir oralmente

"El frontend se publica como SPA y el backend PHP vive en Hostinger. Para conectar ambas partes, uso reescrituras en Vercel y una automatizacion de despliegue del backend mediante GitHub Actions por FTP."

---

## 5. Base De Datos

### Archivo principal

- `bd/veridi.sql`

### Tablas importantes

- `categorias`
- `productos`
- `tallas`
- `producto_tallas`
- `usuarios`
- `carrito`
- `carrito_detalle`
- `deseos_usuario`
- `pedidos`
- `pedido_detalle`
- `contacto`
- `valoraciones`
- `valoraciones_producto`

### Como defender el diseño

Puedo explicar la base de datos asi:

- `productos` guarda la informacion general del producto.
- `tallas` guarda el catalogo de tallas disponibles.
- `producto_tallas` separa el stock por talla, que era necesario para controlar disponibilidad real.
- `carrito` y `carrito_detalle` permiten separar cabecera del carrito y sus lineas.
- `pedidos` y `pedido_detalle` hacen lo mismo para las compras.
- `deseos_usuario` guarda favoritos por usuario.
- `valoraciones` guarda valoraciones generales de pedidos.
- `valoraciones_producto` guarda valoraciones especificas por producto.

### Frase util para el tribunal

"La parte importante del diseño fue separar el stock por talla en `producto_tallas`, porque si el stock estuviera solo en `productos`, no podria controlar bien la disponibilidad real de cada variante."

---

## 6. Autenticacion, Sesion Y Perfil

### Archivos implicados

- `frontend/src/components/AppLayout.jsx`
- `php/api_auth_react.php`
- `php/api_usuario.php`
- `php/api_perfil_react.php`

### Como funciona

La autenticacion se gestiona desde el layout general de la aplicacion.

- `AppLayout.jsx` controla login, registro, logout, panel de usuario y contadores.
- `api_auth_react.php` se usa para registrar, iniciar sesion y cerrar sesion.
- `api_usuario.php` devuelve si el usuario esta logueado, datos del perfil, historial de pedidos y contadores de carrito y favoritos.
- `api_perfil_react.php` actualiza nombre y foto de perfil.

### Aspectos tecnicos defendibles

- Se usa `$_SESSION` para mantener la sesion del usuario.
- Se configuran cookies de sesion con `httponly` y `samesite`.
- El frontend usa `withCredentials: true` en Axios para conservar la sesion.

### Si me preguntan por seguridad

Puedo decir:

- Las contraseñas se guardan con hash usando `password_hash` y se validan con `password_verify`.
- La sesion se controla en backend, no solo en frontend.
- Los endpoints protegidos comprueban si existe `$_SESSION['usuario_id']`.
- El panel admin tambien comprueba `$_SESSION['usuario_rol']`.

---

## 7. Catalogo Y Tienda

### Archivos implicados

- `frontend/src/pages/TiendaPage.jsx`
- `frontend/src/services/api.js`
- `php/api_tienda.php`
- `config/imagenes.php`
- `php/ofertas.php`

### Como funciona

La pagina de tienda carga el catalogo desde `api_tienda.php`.

Ese endpoint permite:

- buscar por nombre
- filtrar por categoria
- filtrar por precio minimo y maximo
- filtrar por talla
- filtrar por color
- filtrar por estilo
- ordenar por nombre o precio
- paginar resultados

### Detalle tecnico importante

En el frontend, `TiendaPage.jsx` guarda los filtros en `localStorage`, para que al volver a la tienda se mantenga el estado del catalogo.

### Imagenes

Las imagenes no se resuelven directamente desde la base de datos, sino con un mapeo en `config/imagenes.php`.

Esto me permite explicar que:

- la base de datos guarda el producto
- y el backend asigna la ruta de imagen segun ID o nombre

### Ofertas

Las ofertas se centralizan en `php/ofertas.php`, y varios endpoints reutilizan esa misma logica para que el precio con descuento sea consistente en toda la app.

---

## 8. Inicio O Home

### Archivos implicados

- `frontend/src/pages/HomePage.jsx`
- `php/api_inicio.php`

### Como funciona

La home carga productos destacados:

- producto mas vendido
- producto mas nuevo
- producto en oferta

El endpoint `api_inicio.php` consulta productos, calcula ventas y selecciona destacados para mostrarlos en la portada.

---

## 9. Detalle De Producto

### Archivos implicados

- `frontend/src/pages/ProductDetailPage.jsx`
- `frontend/src/pages/ProductDetailPage.css`
- `php/api_producto_detalle.php`
- `php/api_deseos.php`
- `php/api_carrito.php`

### Que devuelve el backend

`api_producto_detalle.php` devuelve:

- datos del producto
- tallas disponibles con stock
- productos relacionados
- valoraciones del producto
- estado del usuario respecto a favoritos

### Logica importante

- Se limita que solo aparezcan tallas validas segun categoria.
- Se muestra disponibilidad real segun `producto_tallas`.
- El usuario puede anadir a favoritos o al carrito desde esta pantalla.
- Tambien puede lanzar el asistente IA usando el producto como prenda base.

---

## 10. Carrito

### Archivos implicados

- `frontend/src/pages/CartPage.jsx`
- `php/api_carrito.php`
- `bd/veridi.sql`

### Tablas implicadas

- `carrito`
- `carrito_detalle`
- `producto_tallas`

### Como funciona

El carrito no es solo visual. Tiene persistencia en sesion y tambien en base de datos.

En `api_carrito.php` se gestionan acciones como:

- `add_item`
- `update_quantity`
- `update_size`
- `remove_item`
- `clear_cart`
- `add_outfit`

### Como lo defenderia oralmente

"El carrito lo resolvi con una combinacion de sesion y persistencia en base de datos. La sesion da rapidez de uso, pero el estado real tambien se sincroniza con `carrito` y `carrito_detalle`, para que no dependa solo del cliente."

### Detalle tecnico interesante

- El item del carrito se identifica por `id_producto + id_talla`.
- Esto evita mezclar en una misma linea el mismo producto con tallas distintas.
- El backend valida stock antes de anadir o modificar cantidades.

---

## 11. Checkout Y Compra

### Archivos implicados

- `frontend/src/pages/CheckoutPage.jsx`
- `php/api_checkout.php`
- `php/ofertas.php`

### Tablas implicadas

- `pedidos`
- `pedido_detalle`
- `producto_tallas`
- `carrito`
- `carrito_detalle`

### Como funciona

El checkout primero carga el resumen de compra y los datos del usuario. Cuando se confirma el pago:

1. El backend valida los datos obligatorios.
2. Vuelve a calcular precios desde base de datos.
3. Agrupa carrito por producto y talla.
4. Bloquea stock con `SELECT ... FOR UPDATE`.
5. Comprueba si hay stock suficiente.
6. Descuenta stock.
7. Inserta el pedido en `pedidos`.
8. Inserta sus lineas en `pedido_detalle`.
9. Limpia el carrito.

### Esta es una de las mejores respuestas del proyecto

Si te preguntan por robustez o concurrencia, aqui tienes una respuesta muy buena:

"En checkout no me fie del precio que venia del frontend. Lo recalculo otra vez en backend y, ademas, el stock se actualiza dentro de una transaccion bloqueando las filas de `producto_tallas` con `FOR UPDATE`, para evitar vender stock que ya no existe."

---

## 12. Confirmacion Y Valoraciones

### Archivos implicados

- `frontend/src/pages/ConfirmationPage.jsx`
- `php/api_confirmacion.php`
- `php/api_valoracion.php`
- `frontend/src/pages/RatingsPage.jsx`
- `php/api_valoraciones.php`

### Como funciona

Tras la compra:

- `api_confirmacion.php` devuelve la informacion del pedido.
- Tambien indica si ese pedido ya fue valorado.
- `api_valoracion.php` guarda o actualiza la valoracion general del pedido.
- `api_valoraciones.php` devuelve el listado global de valoraciones para la pagina publica.

### Diferencia importante

Hay dos tipos de valoracion:

- valoracion general del pedido (`valoraciones`)
- valoracion por producto (`valoraciones_producto`)

Eso es importante porque demuestra que el modelo no es simplista.

---

## 13. Favoritos O Lista De Deseos

### Archivos implicados

- `frontend/src/pages/WishlistPage.jsx`
- `frontend/src/pages/ProductDetailPage.jsx`
- `php/api_deseos.php`
- tabla `deseos_usuario`

### Como funciona

El usuario puede marcar un producto como favorito desde el detalle o ver su lista completa en la pagina de deseos.

`api_deseos.php` permite:

- consultar favoritos
- anadir favorito
- eliminar favorito
- comprobar si un producto esta en favoritos

### Como defenderlo

"Los favoritos se guardan por usuario en una tabla independiente, con una restriccion unica por usuario y producto para evitar duplicados."

---

## 14. Contacto

### Archivos implicados

- `frontend/src/pages/ContactPage.jsx`
- `php/api_contacto.php`
- tabla `contacto`

### Como funciona

El formulario de contacto solo lo puede enviar un usuario autenticado.

El backend:

- recupera el usuario actual
- valida que el email enviado coincida con el de la cuenta
- valida campos obligatorios
- asegura que la tabla `contacto` exista
- inserta el mensaje en base de datos

### Punto defendible

Aqui puedes decir que no permitiste mensajes anonimos desde esa interfaz para evitar spam y asegurar trazabilidad del emisor.

---

## 15. Panel De Administracion

### Archivos implicados

- `frontend/src/pages/AdminPage.jsx`
- `php/api_admin.php`
- `config/imagenes.php`

### Funcionalidades

- crear productos
- editar productos
- eliminar productos
- ajustar stock
- ocultar o mostrar productos
- listar usuarios
- borrar usuarios no administradores
- moderar valoraciones
- subir imagenes de productos

### Seguridad

`api_admin.php` comprueba dos cosas:

- que el usuario este autenticado
- que el rol sea `admin`

### Detalle tecnico bueno para mencionar

Cuando se crea un producto, el backend tambien crea stock inicial en `producto_tallas` segun categoria:

- ropa: `S`, `M`, `L`, `XL`
- calzado: `40`, `41`, `42`
- gorras y accesorios: `Única`

Esto demuestra que el panel admin no solo inserta un producto, sino que deja el producto preparado para venderse.

---

## 16. Asistente De IA

### Archivos implicados

- `frontend/src/components/AiStylistChat.jsx`
- `php/api_ai_stylist.php`
- `config/openai.local.example.php`
- `config/openai.local.php`
- `config/imagenes.php`
- `php/api_carrito.php`

### Como funciona

El asistente IA recibe un mensaje del usuario con lo que busca, por ejemplo:

- outfit casual
- look formal
- ropa deportiva
- salir de noche
- frio o verano
- con presupuesto

El endpoint `api_ai_stylist.php`:

- analiza el mensaje
- detecta estilo solicitado
- detecta exclusiones de estilo
- detecta si necesita capa superior o no segun clima
- cruza eso con el catalogo real
- construye un outfit por slots
- aplica restricciones de presupuesto
- devuelve explicaciones y metadatos

### Si OpenAI no esta disponible

El sistema puede seguir funcionando con logica local. Eso es importante decirlo, porque muestra tolerancia a fallos.

### Respuesta oral buena

"No depende ciegamente de una respuesta generativa. Primero tengo reglas locales de negocio y validacion sobre el catalogo, y OpenAI es una capa opcional para enriquecer la recomendacion. Si falla, hay fallback local."

### Integracion con carrito

Desde `AiStylistChat.jsx` se puede:

- anadir un producto recomendado
- anadir el outfit completo

Eso termina llamando a `api_carrito.php` con la accion `add_outfit`.

---

## 17. Sistema De Imagenes Y Recursos

### Archivo clave

- `config/imagenes.php`

### Funcion

Centraliza la resolucion de rutas de imagenes por producto.

### Por que es importante

Si me preguntan por que no guardo todo directamente en la tabla de productos, puedo responder que use una capa de resolucion para tener mas control sobre rutas, imagenes personalizadas y fallback.

Tambien soporta imagenes personalizadas guardadas en JSON para productos creados desde admin.

---

## 18. Pruebas Que He Hecho

Esta parte hay que decirla con honestidad, porque en el repositorio no hay una suite automatizada montada con Jest, Vitest, PHPUnit o Cypress.

### Lo que si puedo defender

He hecho principalmente:

- pruebas manuales funcionales
- pruebas de integracion entre frontend, backend y base de datos
- depuracion de errores de sesion, CORS y stock
- validaciones de formularios y flujos completos

### Flujos probados

- registro de usuario
- login y logout
- carga de usuario actual
- filtrado de productos
- detalle de producto
- anadir y eliminar favoritos
- anadir al carrito
- cambiar talla en carrito
- actualizar cantidades
- checkout completo
- creacion de pedido
- decremento de stock
- valoracion de pedido y producto
- uso del panel admin
- contacto
- asistente IA

### Como decirlo sin que suene debil

"No llegue a montar una suite automatizada completa, pero si hice pruebas funcionales e integradas sobre los flujos de negocio principales, especialmente autenticacion, carrito, checkout, stock, admin y asistente IA. Una mejora futura clara seria automatizar parte de esos flujos con tests end-to-end."

---

## 19. Puntos Tecnicos Fuertes Que Conviene Destacar

Si quieres sonar solido, estos son de los mejores puntos del proyecto:

1. Separacion clara entre frontend, backend y base de datos.
2. Persistencia real del carrito, no solo estado en cliente.
3. Control de stock por talla con tabla propia.
4. Checkout con transaccion y bloqueo de stock.
5. Validaciones duplicadas en frontend y backend.
6. Proteccion de endpoints por sesion y rol.
7. Centralizacion de precios con oferta en un unico modulo.
8. Integracion de IA con fallback local.
9. Reescrituras y despliegue desacoplado entre frontend y backend.
10. Panel admin funcional con gestion real de productos y stock.

---

## 20. Respuestas Cortas A Preguntas Probables

### Como hiciste el carrito

"La parte visual esta en `frontend/src/pages/CartPage.jsx`, pero la logica real esta en `php/api_carrito.php`. El carrito se guarda en sesion y tambien en base de datos con `carrito` y `carrito_detalle`, y cada linea se identifica por producto mas talla para no mezclar variantes."

### Como controlas el stock

"El stock no esta solo en productos, sino en `producto_tallas`, porque necesitaba saber cuantas unidades habia por talla. En checkout lo bloqueo con transaccion para evitar inconsistencias."

### Como hiciste el login

"Desde React lo controlo en `AppLayout.jsx`, y el backend esta en `php/api_auth_react.php`. Ahi registro usuarios, valido contrasena con hash y creo la sesion en PHP."

### Como sabes si un usuario es admin

"Porque guardo el rol del usuario en la tabla `usuarios` y en sesion. Luego `php/api_admin.php` comprueba que exista sesion y que el rol sea `admin`."

### Como hiciste la tienda

"La interfaz esta en `frontend/src/pages/TiendaPage.jsx` y el endpoint es `php/api_tienda.php`. Ese endpoint recibe filtros, construye la consulta SQL y devuelve productos, paginacion y datos auxiliares para filtros."

### Como hiciste el checkout

"La pantalla esta en `frontend/src/pages/CheckoutPage.jsx` y el proceso real en `php/api_checkout.php`. El backend recalcula el total, comprueba stock, descuenta unidades, crea el pedido y limpia el carrito dentro de una transaccion."

### Como hiciste la IA

"La interfaz esta en `frontend/src/components/AiStylistChat.jsx` y el motor en `php/api_ai_stylist.php`. Interpreta el mensaje, filtra el catalogo por reglas de negocio y puede usar OpenAI como apoyo, pero con fallback local si no esta disponible."

### Como desplegaste el sistema

"El frontend esta preparado como SPA y las rutas del backend se reescriben con `vercel.json`. El backend PHP se despliega por FTP a Hostinger con GitHub Actions, en `.github/workflows/deploy-hostinger.yml`."

### Que pruebas hiciste

"Principalmente pruebas funcionales e integradas. Probe autenticacion, carrito, checkout, stock, admin, favoritos, valoraciones y contacto. No llegue a montar una suite automatizada completa, aunque seria una mejora futura clara."

---

## 21. Posibles Preguntas Trampa Y Como Responderlas

### Por que usaste HashRouter

"Porque al desplegar una SPA con rutas internas, `HashRouter` evita problemas de recarga directa en rutas como `/producto/5` o `/checkout`, especialmente cuando el servidor no resuelve esas rutas como paginas fisicas."

### Por que recalculas precios en backend si ya estaban en frontend

"Porque no debo fiarme del cliente. El frontend solo muestra datos, pero el importe real de compra se valida y recalcula en backend."

### Por que guardas tambien carrito o favoritos en sesion

"Porque me permite una experiencia mas fluida y tener datos disponibles rapidamente, pero el estado persistente importante sigue sincronizado con la base de datos."

### Que mejorarias si tuvieras mas tiempo

"Automatizaria pruebas end-to-end, separaria aun mas servicios del backend, moveria secretos sensibles a variables de entorno de forma mas estricta y optimizaria el bundle del frontend con code splitting."

---

## 22. Cierre Recomendado Para La Defensa

Si necesito cerrar una explicacion con seguridad, puedo usar una idea como esta:

"Mi objetivo no fue solo hacer una tienda que se viera bien, sino una aplicacion coherente a nivel tecnico: con autenticacion, carrito persistente, stock por talla, checkout consistente, panel admin, valoraciones, contacto y un asistente de IA integrado con reglas de negocio reales sobre el catalogo."
