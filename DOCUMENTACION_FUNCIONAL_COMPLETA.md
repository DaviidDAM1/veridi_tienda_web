# Documentacion Funcional Completa - Veridi

## 1. Resumen Del Proyecto
Veridi es una tienda online con frontend SPA en React y backend PHP por endpoints, apoyado en MySQL.

Arquitectura principal:
- Frontend: React + Vite + React Router + Axios
- Backend: PHP (endpoints JSON en `php/api_*.php`)
- Base de datos: MySQL (`bd/veridi.sql`)
- Sesion PHP: autenticacion, carrito y favoritos en tiempo de ejecucion

Entrada de la app:
- `index.php` redirige al build React con hash route.
- Ruta inicial actual: `/#/` -> pantalla de bienvenida.
- Inicio real de tienda: `/#/inicio`.

---

## 2. Estructura General De Archivos

### Frontend
- `frontend/src/main.jsx`: arranque React con HashRouter e import de estilos globales.
- `frontend/src/App.jsx`: definicion de rutas.
- `frontend/src/components/AppLayout.jsx`: layout global, header, auth panel, perfil, footer, sincronizacion de contadores.
- `frontend/src/pages/*.jsx`: paginas funcionales por modulo.
- `frontend/src/services/api.js`: cliente Axios y helper de assets backend.
- `frontend/src/utils/auth.js`: helper para abrir login/registro desde cualquier pagina.

### Backend
- `php/api_auth_react.php`
- `php/api_usuario.php`
- `php/api_tienda.php`
- `php/api_producto_detalle.php`
- `php/api_carrito.php`
- `php/api_checkout.php`
- `php/api_confirmacion.php`
- `php/api_valoracion.php`
- `php/api_valoraciones.php`
- `php/api_deseos.php`
- `php/api_contacto.php`
- `php/api_perfil_react.php`
- `php/api_admin.php`
- `php/api_inicio.php`

### Config y datos
- `config/conexion.php`: conexion PDO.
- `config/imagenes.php`: helper de imagenes de producto.
- `bd/veridi.sql`: esquema y seeds.
- `css/styles.css`: estilos globales (usado por React y vistas PHP).

---

## 3. Rutas Frontend Actuales
Definidas en `frontend/src/App.jsx`.

- `/` -> `WelcomePage` (pantalla de bienvenida)
- `/inicio` -> `HomePage`
- `/tienda` -> `TiendaPage`
- `/producto/:id` -> `ProductDetailPage`
- `/carrito` -> `CartPage`
- `/checkout` -> `CheckoutPage`
- `/confirmacion/:id` -> `ConfirmationPage`
- `/valoraciones` -> `RatingsPage`
- `/contacto` -> `ContactPage`
- `/sobre-nosotros` -> `AboutPage`
- `/lista-deseos` -> `WishlistPage`
- `/politica` -> `PolicyPage`
- `/bienvenida` -> `WelcomePage`
- `/admin` -> `AdminPage`

---

## 4. Funcionalidades Detalladas

## 4.1 Pantalla De Bienvenida
Objetivo:
- Pantalla visual previa al inicio de tienda.
- Boton de entrada a `/inicio`.

Frontend:
- `frontend/src/pages/WelcomePage.jsx`
- `frontend/src/components/ui/LiquidEther.jsx`
- `frontend/src/components/ui/LiquidEther.css`
- estilos en `css/styles.css`

Backend:
- No requiere endpoint especifico para pintar la splash.

---

## 4.2 Inicio (Home)
Objetivo:
- Mostrar productos destacados: mas vendido, nuevo, oferta.

Frontend:
- `frontend/src/pages/HomePage.jsx`

Backend:
- GET `php/api_inicio.php`

Datos usados:
- `productos`, `pedido_detalle`, `pedidos`.

---

## 4.3 Login, Registro Y Logout
Objetivo:
- Alta de cuenta, inicio de sesion y cierre de sesion.
- Gestion central de sesion de usuario.

Frontend:
- `frontend/src/components/AppLayout.jsx` (auth panel inline)
- `frontend/src/utils/auth.js` (evento para abrir auth)

Backend:
- POST `php/api_auth_react.php`
  - `action=register`
  - `action=login`
  - `action=logout`

Estado y sincronizacion:
- GET `php/api_usuario.php` para refrescar usuario y contadores tras login/logout.

BD:
- tabla `usuarios`.

---

## 4.4 Perfil De Usuario
Objetivo:
- Visualizar informacion personal.
- Editar nombre.
- Subir/modificar foto de perfil.
- Ver historial de pedidos y valoraciones recientes.

Frontend:
- Modal en `frontend/src/components/AppLayout.jsx`

Backend:
- POST `php/api_perfil_react.php`
  - `action=name`
  - `action=photo`
- GET `php/api_usuario.php` (perfil + historial + contadores)

BD:
- `usuarios`, `pedidos`, `valoraciones`.

Archivos de imagen:
- Fotos en `img/perfiles/` (backend).

---

## 4.5 Catalogo/Tienda
Objetivo:
- Listado de productos con filtros, orden y paginacion.
- Toggle de favoritos desde listado.

Frontend:
- `frontend/src/pages/TiendaPage.jsx`

Backend:
- GET `php/api_tienda.php`
- POST `php/api_deseos.php` (add/remove/check)

Filtros soportados:
- busqueda, categoria, rango de precio, talla, color, estilo, ordenacion, pagina.

BD:
- `productos`, `categorias`, `producto_tallas`, `tallas`.

---

## 4.6 Detalle De Producto
Objetivo:
- Ver detalle del producto.
- Seleccionar talla.
- Anadir al carrito.
- Anadir o quitar de favoritos.
- Mostrar relacionados.

Frontend:
- `frontend/src/pages/ProductDetailPage.jsx`
- `frontend/src/pages/ProductDetailPage.css`

Backend:
- GET `php/api_producto_detalle.php`
- POST `php/api_carrito.php` (`action=add_item`)
- POST `php/api_deseos.php`

BD:
- `productos`, `producto_tallas`, `tallas`.

---

## 4.7 Carrito
Objetivo:
- Ver lineas de carrito.
- Actualizar cantidad.
- Eliminar linea.
- Vaciar carrito.

Frontend:
- `frontend/src/pages/CartPage.jsx`

Backend:
- GET `php/api_carrito.php`
- POST `php/api_carrito.php`
  - `action=update_quantity`
  - `action=remove_item`
  - `action=clear_cart`

Estado:
- Operacion principal en sesion PHP.

BD:
- Existen `carrito` y `carrito_detalle` en esquema, pero la operativa principal actual es por sesion.

---

## 4.8 Checkout Y Sistema De Pedidos
Objetivo:
- Confirmar compra.
- Crear pedido y lineas de pedido.
- Vaciar carrito tras compra.

Frontend:
- `frontend/src/pages/CheckoutPage.jsx`

Backend:
- GET `php/api_checkout.php` (resumen checkout)
- POST `php/api_checkout.php` (crear pedido)

BD:
- `pedidos`, `pedido_detalle`.

Notas de funcionamiento:
- Se valida usuario y credenciales antes de finalizar.
- Se usan transacciones para consistencia del pedido.

---

## 4.9 Confirmacion De Pedido
Objetivo:
- Mostrar resumen final de pedido.
- Permitir abrir flujo de valoracion.

Frontend:
- `frontend/src/pages/ConfirmationPage.jsx`

Backend:
- GET `php/api_confirmacion.php`

BD:
- `pedidos`, `pedido_detalle`, `valoraciones`.

---

## 4.10 Sistema De Valoraciones
Objetivo:
- Valorar pedidos con estrellas y comentario.
- Mostrar listado publico de valoraciones.

Frontend:
- En confirmacion: `frontend/src/pages/ConfirmationPage.jsx`
- Listado global: `frontend/src/pages/RatingsPage.jsx`

Backend:
- POST `php/api_valoracion.php`
- GET `php/api_valoraciones.php`

BD:
- `valoraciones` con:
  - estrellas 1..5
  - unicidad por `id_usuario + id_pedido`

---

## 4.11 Lista De Deseos (Favoritos)
Objetivo:
- Guardar productos favoritos.
- Eliminar favoritos.
- Flujo de mover al carrito (pasando por detalle para talla).

Frontend:
- `frontend/src/pages/WishlistPage.jsx`

Backend:
- GET/POST `php/api_deseos.php`

Estado:
- Principalmente basado en sesion.

---

## 4.12 Contacto
Objetivo:
- Formulario de contacto de usuario autenticado.

Frontend:
- `frontend/src/pages/ContactPage.jsx`

Backend:
- GET `php/api_contacto.php` (prefill)
- POST `php/api_contacto.php` (enviar mensaje)

BD:
- tabla `contacto`.

---

## 4.13 Panel De Administracion
Objetivo:
- Gestion de productos y stock (rol admin).
- Ver usuarios.

Frontend:
- `frontend/src/pages/AdminPage.jsx`

Backend:
- GET/POST `php/api_admin.php`
  - crear producto
  - editar producto
  - eliminar producto
  - ocultar/mostrar
  - ajustar stock por talla

BD:
- `productos`, `categorias`, `tallas`, `producto_tallas`, `usuarios`.

---

## 4.14 Paginas Informativas
Objetivo:
- Contenido institucional.

Frontend:
- `frontend/src/pages/AboutPage.jsx`
- `frontend/src/pages/PolicyPage.jsx`

Backend:
- No dependen de endpoint especifico para render principal.

---

## 5. Sistema Global De Layout, Estado Y Eventos
Archivo clave:
- `frontend/src/components/AppLayout.jsx`

Responsabilidades:
- Header y footer globales.
- Apertura de auth panel.
- Carga de usuario/contador global.
- Modal de perfil.
- Control de visibilidad especial en welcome.

Eventos globales:
- `veridi:open-auth` -> abrir login/registro desde cualquier pagina.
- `veridi:update-contador` -> sincronizar carrito y favoritos.

---

## 6. Base De Datos - Resumen Funcional
Definida en `bd/veridi.sql`.

Tablas principales:
- `categorias`
- `productos`
- `tallas`
- `producto_tallas`
- `usuarios`
- `carrito`
- `carrito_detalle`
- `pedidos`
- `pedido_detalle`
- `contacto`
- `valoraciones`

Puntos clave:
- `productos.oculto` permite despublicar sin borrar.
- `valoraciones` evita duplicados por usuario/pedido.
- `pedido_detalle` guarda `precio_unitario` historico.

---

## 7. Seguridad Y Reglas De Acceso
- Sesion PHP para identificar usuario logueado.
- Endpoints con guardas por login (`requiresLogin`) y admin (`requiresAdmin`).
- CORS preparado para entorno local de desarrollo (Vite).
- Passwords en usuarios gestionados con hash.

---

## 8. Mapa Rapido Frontend -> Endpoint
- Home: `HomePage.jsx` -> `api_inicio.php`
- Tienda: `TiendaPage.jsx` -> `api_tienda.php`, `api_deseos.php`
- Detalle: `ProductDetailPage.jsx` -> `api_producto_detalle.php`, `api_carrito.php`, `api_deseos.php`
- Carrito: `CartPage.jsx` -> `api_carrito.php`
- Checkout: `CheckoutPage.jsx` -> `api_checkout.php`
- Confirmacion: `ConfirmationPage.jsx` -> `api_confirmacion.php`, `api_valoracion.php`
- Valoraciones publicas: `RatingsPage.jsx` -> `api_valoraciones.php`
- Contacto: `ContactPage.jsx` -> `api_contacto.php`
- Favoritos: `WishlistPage.jsx` -> `api_deseos.php`
- Admin: `AdminPage.jsx` -> `api_admin.php`
- Auth y perfil global: `AppLayout.jsx` -> `api_auth_react.php`, `api_usuario.php`, `api_perfil_react.php`

---

## 9. Documentos Relacionados
- `DOCUMENTACION_FRONTEND_REACT.md`
- `DOCUMENTACION_BACKEND_PHP.md`
- `DOCUMENTACION_BASE_DATOS.md`

Este archivo unifica las funcionalidades de extremo a extremo para consulta rapida de desarrollo y mantenimiento.

---

## 10. Tecnologias Usadas

### 10.1 React
Que es:
- Libreria de JavaScript para construir interfaces por componentes y manejar estado en cliente.

Por que se uso:
- Permite separar la tienda en modulos reutilizables (layout, paginas, componentes UI) y escalar el proyecto sin duplicar codigo.

Funcion en el proyecto:
- Renderiza toda la SPA y la logica de vistas de catalogo, carrito, checkout, perfil, admin y paginas informativas.
- Archivos clave: `frontend/src/main.jsx`, `frontend/src/App.jsx`, `frontend/src/components/AppLayout.jsx`, `frontend/src/pages/*.jsx`.

### 10.2 React Router (HashRouter)
Que es:
- Libreria de enrutado para aplicaciones React.

Por que se uso:
- Permite tener navegacion tipo web completa en una SPA sin recargar la pagina.
- Se eligio HashRouter para simplificar despliegue en Apache/XAMPP sin reglas complejas de rewrite.

Funcion en el proyecto:
- Gestiona rutas como `/`, `/inicio`, `/tienda`, `/producto/:id`, `/checkout`, `/admin`, etc.
- Archivos clave: `frontend/src/main.jsx`, `frontend/src/App.jsx`.

### 10.3 Vite
Que es:
- Herramienta de desarrollo y build para frontend moderno, muy rapida en servidor dev y compilacion.

Por que se uso:
- Acelera el ciclo de desarrollo (arranque instantaneo, HMR) y genera build optimizado para produccion.

Funcion en el proyecto:
- Levanta entorno de desarrollo (`npm run dev`) y genera salida final (`npm run build`) en `frontend/dist`.
- Archivos clave: `frontend/package.json`, `frontend/vite.config.js`, `frontend/dist/`.

### 10.4 Axios
Que es:
- Cliente HTTP para navegador con API sencilla para GET/POST y manejo consistente de errores.

Por que se uso:
- Centraliza llamadas al backend PHP y evita repetir configuracion de baseURL, credenciales y timeouts en cada pagina.

Funcion en el proyecto:
- Consume todos los endpoints `php/api_*.php` desde React.
- Archivos clave: `frontend/src/services/api.js`, y uso en `frontend/src/pages/*.jsx` y `frontend/src/components/AppLayout.jsx`.

### 10.5 PHP
Que es:
- Lenguaje de backend orientado a web y muy integrado con Apache/XAMPP.

Por que se uso:
- Es adecuado para un backend rapido de ecommerce con sesiones, acceso MySQL y endpoints JSON.

Funcion en el proyecto:
- Implementa autenticacion, perfil, tienda, carrito, checkout, pedidos, valoraciones, contacto y administracion.
- Archivos clave: `php/api_auth_react.php`, `php/api_usuario.php`, `php/api_tienda.php`, `php/api_carrito.php`, `php/api_checkout.php`, `php/api_admin.php` y resto de `php/api_*.php`.

### 10.6 MySQL
Que es:
- Sistema gestor de base de datos relacional.

Por que se uso:
- Permite modelar entidades de tienda (usuarios, productos, stock por talla, pedidos, valoraciones) con integridad referencial.

Funcion en el proyecto:
- Persistencia principal del negocio: catalogo, cuentas, pedidos, valoraciones y contacto.
- Archivos clave: `bd/veridi.sql`, `config/conexion.php`.

### 10.7 PDO (PHP Data Objects)
Que es:
- Capa de acceso a base de datos en PHP compatible con prepared statements.

Por que se uso:
- Mejora seguridad y mantenibilidad al ejecutar consultas parametrizadas.

Funcion en el proyecto:
- Ejecuta consultas SQL desde los endpoints PHP con conexion unificada.
- Archivo clave: `config/conexion.php`.

### 10.8 Sesiones PHP
Que es:
- Mecanismo de estado en servidor por usuario autenticado.

Por que se uso:
- Facilita login persistente y manejo rapido de datos temporales de compra por usuario.

Funcion en el proyecto:
- Guarda identidad (`usuario_id`, rol), carrito runtime y favoritos runtime.
- Archivos clave: `php/api_auth_react.php`, `php/api_usuario.php`, `php/api_carrito.php`, `php/api_deseos.php`.

### 10.9 CSS Global
Que es:
- Hoja de estilos principal compartida por React y vistas PHP.

Por que se uso:
- Mantiene una identidad visual consistente y reduce duplicidad de estilos.

Funcion en el proyecto:
- Define sistema visual completo (tipografia, colores, layout, componentes, responsive y animaciones).
- Archivo clave: `css/styles.css`.

### 10.10 Three.js
Que es:
- Libreria de graficos 3D/WebGL para navegador.

Por que se uso:
- Permite crear fondos interactivos avanzados con buena calidad visual para mejorar la experiencia de marca.

Funcion en el proyecto:
- Soporta el efecto `LiquidEther` de la pantalla de bienvenida.
- Archivos clave: `frontend/src/components/ui/LiquidEther.jsx`, dependencia en `frontend/package.json`.

### 10.11 OGL
Que es:
- Libreria WebGL ligera para rendering de shaders/escenas de alto rendimiento.

Por que se uso:
- Permite efectos visuales fluidos y controlados por shader con bajo overhead.

Funcion en el proyecto:
- Soporta el componente `PlasmaWave` usado en el hero de inicio.
- Archivos clave: `frontend/src/components/ui/PlasmaWave.jsx`, dependencia en `frontend/package.json`.

### 10.12 Apache + XAMPP
Que es:
- Entorno local que integra servidor web Apache, PHP y MySQL.

Por que se uso:
- Simplifica desarrollo local completo full-stack en Windows.

Funcion en el proyecto:
- Sirve el backend PHP y los recursos del proyecto desde `htdocs`.
- Archivos/ruta clave: `C:/xampp/htdocs/veridi_tienda_web/`.

---

## 11. Metodologia Del Proyecto

### 11.1 Enfoque General
El proyecto siguio una metodologia iterativa e incremental adaptada a un equipo reducido.
No se aplico Scrum ni Waterfall estricto, sino un ciclo de mejora continua orientado a entregables visibles:
- Implementar una funcionalidad o mejora concreta.
- Verificar visualmente en navegador y ajustar hasta que el resultado fuera correcto.
- Pasar al siguiente modulo o mejora.

Este enfoque permite avanzar con rapidez, mantener el codigo bajo control y adaptar decisiones de diseno sobre la marcha sin comprometer la estabilidad del proyecto.

---

### 11.2 Fases De Desarrollo

#### Fase 1 - Planificacion y diseno inicial
- Definicion del alcance: tienda online con catalogo, carrito, checkout, valoraciones, admin y paginas informativas.
- Eleccion de tecnologias: React + Vite para frontend, PHP con endpoints JSON para backend, MySQL para persistencia.
- Diseno conceptual de base de datos (entidades, relaciones, restricciones de integridad).
- Definicion de la arquitectura: SPA React con HashRouter consumiendo API PHP por CORS local.

#### Fase 2 - Base tecnica
- Configuracion del entorno XAMPP (Apache + PHP + MySQL).
- Creacion del esquema de base de datos (`bd/veridi.sql`) con tablas, claves foraneas y seeds iniciales.
- Configuracion del proyecto Vite con React Router y Axios.
- Implementacion de la conexion PDO centralizada (`config/conexion.php`).
- Estructura inicial de archivos y carpetas del proyecto.

#### Fase 3 - Desarrollo del backend
- Implementacion modulo a modulo de los endpoints PHP:
  - Autenticacion y sesion (`api_auth_react.php`, `api_usuario.php`).
  - Catalogo y filtros (`api_tienda.php`).
  - Detalle de producto con tallas y stock (`api_producto_detalle.php`).
  - Carrito por sesion PHP (`api_carrito.php`).
  - Checkout con transacciones MySQL (`api_checkout.php`).
  - Confirmacion y valoraciones (`api_confirmacion.php`, `api_valoracion.php`).
  - Favoritos (`api_deseos.php`).
  - Contacto (`api_contacto.php`).
  - Perfil con subida de foto (`api_perfil_react.php`).
  - Panel de administracion (`api_admin.php`).
- Validacion de acceso en cada endpoint (login requerido, rol admin cuando aplica).
- Respuestas JSON uniformes con `success`, `data` y `error`.

#### Fase 4 - Desarrollo del frontend
- Implementacion pagina a pagina de los componentes React:
  - Layout global con header, footer, auth panel y modal de perfil (`AppLayout.jsx`).
  - Paginas de negocio: tienda, detalle, carrito, checkout, confirmacion, valoraciones, favoritos, contacto, sobre nosotros, politica.
  - Panel de administracion con formularios de producto y gestion de stock.
- Sistema de eventos custom (`CustomEvent`) para comunicacion entre componentes desacoplados:
  - `veridi:open-auth` para abrir login desde cualquier pagina.
  - `veridi:update-contador` para sincronizar contadores de carrito y favoritos.
- Integracion de efectos visuales avanzados:
  - `LiquidEther` (Three.js) en la pantalla de bienvenida.
  - `PlasmaWave` (OGL) en el hero de inicio.

#### Fase 5 - Integracion y ajuste visual
- Conexion real frontend-backend verificando flujos completos: registro → login → compra → valoracion.
- Ajuste del sistema de estilos global (`css/styles.css`) para coherencia visual entre vistas React y PHP.
- Correcciones de contraste y accesibilidad en modal de perfil, footer y areas de texto.
- Ajuste de paginacion y filtros del catalogo.
- Responsive design revisado en los modulos principales.

#### Fase 6 - Pruebas y refinamiento
- Pruebas manuales de los flujos principales en navegador (Chrome, Edge).
- Validacion de casos limite: stock a cero, usuario sin sesion intentando comprar, valoracion duplicada.
- Correcciones puntuales en PHP (transacciones, validaciones) y en React (estados de carga, errores de red).
- Verificacion de seguridad basica: passwords con hash bcrypt, prepared statements PDO, sesion PHP como barrera de autorizacion.

#### Fase 7 - Documentacion
- Generacion de documentacion funcional completa cubriendo frontend, backend, base de datos, tecnologias y metodologia.
- Documentos:
  - `DOCUMENTACION_FUNCIONAL_COMPLETA.md` (este archivo).
  - `DOCUMENTACION_FRONTEND_REACT.md`.
  - `DOCUMENTACION_BACKEND_PHP.md`.
  - `DOCUMENTACION_BASE_DATOS.md`.

---

### 11.3 Herramientas De Desarrollo Utilizadas

| Herramienta      | Uso principal                                                        |
|------------------|----------------------------------------------------------------------|
| VS Code          | Editor principal para PHP, JSX, CSS y SQL                           |
| XAMPP            | Entorno local Apache + PHP + MySQL en Windows                       |
| phpMyAdmin       | Administracion visual de base de datos durante desarrollo           |
| Vite dev server  | Servidor de desarrollo React con HMR para iteracion rapida          |
| Chrome DevTools  | Inspeccion de red, consola, estilos y depuracion de React           |
| GitHub Copilot   | Asistencia en generacion y revision de codigo durante el desarrollo |

---

### 11.4 Principios De Diseno Aplicados

- **Separacion de responsabilidades**: frontend React puro para UI, backend PHP exclusivamente para logica y datos.
- **Mejora progresiva**: funcionalidad basica primero, luego mejoras visuales y de experiencia.
- **DRY (Don't Repeat Yourself)**: conexion PDO centralizada, estilos globales compartidos, cliente Axios unico.
- **Seguridad por defecto**: hash de passwords, prepared statements, validacion de sesion en cada endpoint sensible.
- **Diseno responsive**: breakpoints CSS para mobile, tablet y escritorio desde el inicio.

---

### 11.5 Ciclo De Mejora Continua
Cada modulo o mejora visual siguio este ciclo:

```
Identificar necesidad o problema
        ↓
Analizar el codigo afectado (frontend / backend / BD)
        ↓
Implementar el cambio minimo necesario
        ↓
Verificar en navegador / herramientas de red
        ↓
Ajustar si es necesario
        ↓
Pasar al siguiente punto
```

Este ciclo corto evita acumulacion de deuda tecnica y permite detectar errores de integracion de forma inmediata.

---

### 11.6 Plan De Despliegue (Produccion)
El proyecto fue desarrollado en local (XAMPP) con vistas a un despliegue en produccion con la siguiente arquitectura:

- **Frontend React**: despliegue estatico en Vercel ejecutando `npm run build` y subiendo `frontend/dist/`.
- **Backend PHP**: hosting PHP compatible (servidor con PHP 8+ y soporte MySQL).
- **Base de datos**: MySQL remoto (servicio gestionado o hosting compartido con phpMyAdmin).
- **CORS**: ajustar `Access-Control-Allow-Origin` en los endpoints PHP para apuntar al dominio Vercel.
- **Sesiones**: verificar que el hosting permite sesiones PHP persistentes entre peticiones del mismo origen.

Archivos a actualizar antes del despliegue:
- `config/conexion.php`: credenciales de base de datos de produccion.
- `frontend/src/services/api.js`: `baseURL` apuntando al dominio del backend en produccion.
- `frontend/vite.config.js`: revisar configuracion de `base` si aplica.

---

## 12. Mejoras A Futuro

### 12.1 Despliegue En Servidor (Vercel + Hosting PHP)
Estado actual:
- La aplicacion funciona unicamente en entorno local con XAMPP.

Mejora planificada:
- Desplegar el frontend React en **Vercel** como sitio estatico generado con `npm run build`.
- Alojar el backend PHP en un hosting compatible (PHP 8+, MySQL, sesiones persistentes).
- Conectar ambas partes ajustando `baseURL` en `frontend/src/services/api.js` y los headers CORS en los endpoints PHP.

Beneficios:
- La tienda seria accesible desde cualquier dispositivo y navegador sin necesidad de XAMPP.
- Vercel ofrece despliegue automatico desde repositorio Git (CI/CD sin configuracion extra).
- Mejora la credibilidad del proyecto al tener una URL publica real.

Pasos principales para realizarlo:
1. Subir el codigo a un repositorio Git (GitHub/GitLab).
2. Conectar el repositorio a Vercel y configurar el directorio raiz como `frontend/`.
3. Contratar hosting PHP con base de datos MySQL e importar `bd/veridi.sql`.
4. Actualizar credenciales en `config/conexion.php` y la `baseURL` en `api.js`.
5. Ajustar `Access-Control-Allow-Origin` en los endpoints PHP al dominio Vercel asignado.

---

### 12.2 Mejora Estetica De La Pagina
Estado actual:
- La tienda tiene un diseno funcional con identidad visual propia, efectos WebGL en hero y bienvenida, y un sistema de estilos global coherente.

Mejoras planificadas:
- **Animaciones de transicion entre paginas**: entradas y salidas suaves al navegar entre rutas.
- **Diseno de tarjetas de producto mejorado**: hover con zoom de imagen, badge de oferta/nuevo mas destacado, overlay rapido de talla.
- **Pagina de checkout rediseñada**: proceso paso a paso (stepper) con indicador de progreso visual.
- **Microinteracciones**: feedback visual inmediato en botones de anadir al carrito, favoritos y envio de formularios.
- **Modo claro/oscuro mejorado**: transicion animada al cambiar tema y persistencia de preferencia en `localStorage`.
- **Tipografia y espaciado**: revision global para mayor jerarquia visual y legibilidad en movil.
- **Imagenes optimizadas**: uso de formatos modernos (WebP) y lazy loading nativo para mejorar rendimiento percibido.

---

### 12.3 Agente IA De Recomendacion De Ropa Y Outfits
Estado actual:
- No existe asistencia inteligente. El usuario descubre productos unicamente mediante el catalogo con filtros manuales.

Mejora planificada:
- Integrar un **agente conversacional de IA** accesible desde cualquier pagina (icono flotante de chat).
- El agente permitiria al usuario describir su estilo, ocasion o preferencias y recibiria recomendaciones de productos del catalogo.
- Capacidades previstas:

| Capacidad                        | Descripcion                                                                              |
|----------------------------------|------------------------------------------------------------------------------------------|
| Recomendacion por estilo         | El usuario indica si prefiere casual, formal, deportivo, etc. y el agente sugiere prendas|
| Creacion de outfits completos    | El agente combina prendas del catalogo (top + bottom + calzado) formando un look completo|
| Filtrado por ocasion             | El usuario dice "busco ropa para una boda" y el agente filtra y recomienda               |
| Chat natural                     | Interfaz de conversacion en lenguaje natural, sin necesidad de usar filtros manuales     |
| Enlace directo a productos       | Las recomendaciones incluyen enlace al detalle del producto para compra inmediata        |

Tecnologias candidatas para la integracion:
- **OpenAI API (GPT-4o)**: modelo de lenguaje con function calling para consultar el catalogo PHP en tiempo real.
- **Vercel AI SDK**: si el frontend migra a un entorno Node/Next.js, facilita la integracion del streaming de respuestas.
- **Endpoint PHP de catalogo**: el agente consultaria `api_tienda.php` con los filtros adecuados segun el contexto de la conversacion.

Arquitectura propuesta:
```
Usuario escribe en el chat
        ↓
Frontend envia mensaje al endpoint de IA
        ↓
Backend PHP llama a OpenAI API con contexto del catalogo
        ↓
OpenAI decide que productos recomendar (function calling sobre api_tienda.php)
        ↓
Respuesta con texto + lista de productos recomendados
        ↓
Frontend muestra el mensaje y tarjetas de producto clicables
```

---

## 13. Bibliografia

### 13.1 Documentacion Oficial de Tecnologias

- **React** — Documentacion oficial de la libreria de interfaces de usuario.
  https://react.dev/

- **React Router v6** — Guia oficial de enrutado para aplicaciones React.
  https://reactrouter.com/en/main

- **Vite** — Documentacion oficial de la herramienta de build y desarrollo frontend.
  https://vitejs.dev/

- **Axios** — Documentacion oficial del cliente HTTP para navegador y Node.js.
  https://axios-http.com/docs/intro

- **PHP** — Manual oficial del lenguaje PHP.
  https://www.php.net/manual/es/

- **PDO (PHP Data Objects)** — Referencia oficial de la extension PDO en PHP.
  https://www.php.net/manual/es/book.pdo.php

- **MySQL** — Documentacion oficial del sistema gestor de base de datos.
  https://dev.mysql.com/doc/

- **Three.js** — Documentacion oficial de la libreria de graficos 3D/WebGL.
  https://threejs.org/docs/

- **OGL** — Repositorio y documentacion de la libreria WebGL ligera.
  https://github.com/oframe/ogl

- **Vercel** — Documentacion oficial de la plataforma de despliegue frontend.
  https://vercel.com/docs

- **XAMPP** — Sitio oficial del entorno de desarrollo local Apache + PHP + MySQL.
  https://www.apachefriends.org/es/index.html

---

### 13.2 Referencias de Seguridad

- **OWASP Top 10** — Lista de los diez riesgos de seguridad mas criticos en aplicaciones web.
  https://owasp.org/www-project-top-ten/

- **PHP password_hash** — Documentacion oficial del hashing seguro de contrasenas en PHP.
  https://www.php.net/manual/es/function.password-hash.php

- **MDN Web Docs: SameSite cookies** — Referencia sobre atributos de seguridad en cookies de sesion.
  https://developer.mozilla.org/es/docs/Web/HTTP/Headers/Set-Cookie/SameSite

---

### 13.3 Recursos de Aprendizaje y Consulta

- **MDN Web Docs** — Referencia completa de HTML, CSS, JavaScript y APIs web.
  https://developer.mozilla.org/es/

- **CSS-Tricks** — Articulos y guias de referencia sobre CSS avanzado y responsive design.
  https://css-tricks.com/

- **phpMyAdmin** — Documentacion de la herramienta de administracion visual de MySQL.
  https://www.phpmyadmin.net/

- **Can I Use** — Compatibilidad de caracteristicas web (CSS, JS, APIs) por navegador y version.
  https://caniuse.com/

- **OpenAI API** — Documentacion de la API de modelos de lenguaje (referencia para la mejora futura del agente IA).
  https://platform.openai.com/docs
