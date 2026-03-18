# Documentación Frontend (React) - Veridi

## 1) Visión general
El frontend del proyecto está en `frontend/` y está construido con **React + Vite + React Router + Axios**.

Su objetivo es ofrecer una SPA (Single Page Application) que consume el backend PHP (`php/api_*.php`) y mantiene experiencia de tienda completa: catálogo, detalle, carrito, checkout, confirmación, valoraciones, favoritos, perfil y panel admin.

### Stack principal
- **React** (componentes y estado)
- **React Router** (`HashRouter`) para navegación SPA
- **Axios** para consumir APIs PHP
- **CSS global heredado** desde `css/styles.css`

### Arquitectura funcional
- `src/main.jsx`: bootstrap de app y router.
- `src/App.jsx`: definición de rutas.
- `src/components/AppLayout.jsx`: layout global (header, auth, área personal, footer, contadores).
- `src/pages/*.jsx`: páginas funcionales por módulo.
- `src/services/api.js`: cliente HTTP y helper de assets backend.
- `src/utils/auth.js`: utilidades de eventos de autenticación.

---

## 2) Flujo general de frontend

1. Arranque en `main.jsx` con `HashRouter`.
2. `App.jsx` enruta según URL.
3. `AppLayout.jsx` envuelve todas las vistas (excepto comportamiento especial para bienvenida), carga usuario y contadores.
4. Cada página llama a su endpoint correspondiente (`/php/api_*.php`).
5. Eventos globales (`veridi:update-contador`, `veridi:open-auth`) sincronizan estado transversal (header/auth).

---

## 3) Estructura y archivos

### 3.1 Raíz de frontend
- `frontend/package.json`: scripts/dependencias.
- `frontend/vite.config.js`: build/configuración Vite.
- `frontend/index.html`: HTML base de SPA.
- `frontend/dist/`: salida de build para despliegue en Apache/XAMPP.

### 3.2 Carpeta `src/`
- `main.jsx`
- `App.jsx`
- `components/AppLayout.jsx`
- `pages/*`
- `services/api.js`
- `utils/auth.js`

---

## 4) Documentación archivo por archivo

## 4.1 `src/main.jsx` (CRÍTICO)
**Función:** punto de entrada de React.

### Qué hace
- Renderiza `App` dentro de `React.StrictMode`.
- Usa `HashRouter` (clave para despliegue estático en Apache sin rewrite avanzado).
- Importa estilos globales desde `../../css/styles.css`.

---

## 4.2 `src/App.jsx` (CRÍTICO)
**Función:** mapa de rutas.

### Qué define
Rutas principales de la app:
- `/` inicio
- `/tienda`
- `/producto/:id`
- `/carrito`
- `/checkout`
- `/confirmacion/:id`
- `/valoraciones`
- `/contacto`
- `/sobre-nosotros`
- `/lista-deseos`
- `/politica`
- `/bienvenida`
- `/admin`

Todas se renderizan dentro de `AppLayout`.

---

## 4.3 `src/components/AppLayout.jsx` (CRÍTICO)
**Función:** layout global + estado de sesión/usuario + header/área personal.

### Responsabilidades principales
- Carga de usuario con `api_usuario.php` (`loadUser`).
- Mantiene `currentUser`, `contador` (carrito/favoritos), historial y valoraciones del usuario.
- Header dinámico para invitado vs autenticado.
- Auth panel inline (login/registro) conectado a `api_auth_react.php`.
- Área personal/modal con edición de perfil y foto (`api_perfil_react.php`).
- Logout.

### Eventos globales usados
- `veridi:open-auth`: abre panel de login/registro desde otras páginas.
- `veridi:update-contador`: sincroniza contador de carrito/favoritos; además refresca desde `api_usuario.php` para consistencia final.

### Por qué es crítico
- Es la “columna vertebral” de UX global.
- Cualquier desajuste aquí impacta autenticación, navegación y estado global.

---

## 4.4 `src/services/api.js` (CRÍTICO)
**Función:** cliente HTTP central.

### Qué expone
- Instancia Axios `api` con:
  - `baseURL` configurable por `VITE_BACKEND_BASE_URL` (default `http://localhost/veridi_tienda_web`)
  - `withCredentials: true` para sesiones PHP
  - timeout de 10s
- `buildBackendAssetUrl(path)` para construir URLs absolutas de imágenes/assets del backend.

### Importancia
- Evita duplicar configuración en cada página.
- Unifica manejo de base URL y cookies de sesión.

---

## 4.5 `src/utils/auth.js` (ALTA)
**Función:** utilitario para abrir auth desde cualquier página.

### Qué hace
- `openAuthPanel(tab)` dispara evento `veridi:open-auth` con `login`/`register`.

---

## 4.6 `src/pages/HomePage.jsx` (ALTA)
**Función:** home/landing principal.

### Qué hace
- Carga destacados con `api_inicio.php`.
- Muestra tarjetas de `más vendido`, `nuevo`, `oferta`.
- CTA a tienda y enlaces a detalle de producto.

---

## 4.7 `src/pages/TiendaPage.jsx` (CRÍTICO)
**Función:** catálogo principal.

### Qué implementa
- Búsqueda + filtros (categoría, precio, talla, color, estilo).
- Ordenación por nombre/precio.
- Paginación.
- Modal de filtros avanzados.
- Render de cards de producto con imagen, precio, botón de carrito y favoritos.
- Toggle de favorito vía `api_deseos.php`.
- Visualización de contadores de carrito/favoritos en barra.

### Estado clave
- `query`: estado de filtros/orden/página.
- `productos`, `filtrosData`, `paginacion`, `contador`.
- `favLoadingId` para evitar doble click en favoritos.

---

## 4.8 `src/pages/ProductDetailPage.jsx` + `ProductDetailPage.css` (CRÍTICO)
**Función:** detalle de producto y compra con talla.

### Qué implementa
- Carga de detalle con `api_producto_detalle.php`.
- Selección de talla y validación de stock.
- Añadir a carrito (`api_carrito.php`, action `add_item`).
- Añadir/quitar favorito (`api_deseos.php`).
- Productos relacionados.

### Integración transversal
- Emite `veridi:update-contador` al añadir carrito/favorito para sincronizar header.

---

## 4.9 `src/pages/CartPage.jsx` (CRÍTICO)
**Función:** gestión completa del carrito.

### Qué implementa
- Lectura del carrito (`GET api_carrito.php`).
- Operaciones:
  - `update_quantity`
  - `remove_item`
  - `clear_cart`
- Cálculo y render de total.
- Redirección/UX para usuarios no autenticados.

### Integración transversal
- Emite `veridi:update-contador` al cargar y tras cada acción, para sincronizar menú.

---

## 4.10 `src/pages/CheckoutPage.jsx` (CRÍTICO)
**Función:** checkout y confirmación de pago.

### Qué implementa
- Carga de resumen checkout (`GET api_checkout.php`).
- Formulario de dirección y credenciales.
- Submit de compra (`POST api_checkout.php`).
- Navegación a `/confirmacion/:id` cuando se crea pedido.

---

## 4.11 `src/pages/ConfirmationPage.jsx` (ALTA)
**Función:** confirmación de pedido y valoración.

### Qué implementa
- Carga de pedido y líneas (`api_confirmacion.php`).
- Muestra resumen visual de compra.
- Modal de valoración con estrellas/comentario (`api_valoracion.php`).

---

## 4.12 `src/pages/WishlistPage.jsx` (ALTA)
**Función:** lista de favoritos del usuario.

### Qué implementa
- Carga favoritos (`GET api_deseos.php`).
- Eliminar favorito (`remove`).
- Acción “mover al carrito” (redirige al detalle para escoger talla).
- Estado para no logueados.

### Integración transversal
- Emite `veridi:update-contador` al cargar y eliminar.

---

## 4.13 `src/pages/RatingsPage.jsx` (MEDIA)
**Función:** listado de valoraciones públicas.

### Qué implementa
- Carga resumen y lista (`api_valoraciones.php`).
- Filtro por estrellas.
- Render de estrellas y formato de fecha.

---

## 4.14 `src/pages/ContactPage.jsx` (MEDIA-ALTA)
**Función:** formulario de contacto autenticado.

### Qué implementa
- Carga datos de contacto (`GET api_contacto.php`).
- Formulario con nombre/email precargados de cuenta.
- Envío de mensaje (`POST api_contacto.php`) con validaciones backend.
- CTA a login si no está autenticado.

---

## 4.15 `src/pages/AdminPage.jsx` (CRÍTICO para rol admin)
**Función:** panel de administración en frontend.

### Qué implementa
- Carga data admin (`GET api_admin.php`).
- Formularios para:
  - crear producto
  - editar producto
  - eliminar producto
  - ocultar/mostrar producto
  - ajustar stock por talla
- Tablas de productos y usuarios.
- Manejo de `requiresLogin` / `requiresAdmin`.

---

## 4.16 `src/pages/AboutPage.jsx` (BAJA)
**Función:** contenido estático de marca (quiénes somos, misión, visión, valores).

---

## 4.17 `src/pages/PolicyPage.jsx` (BAJA)
**Función:** contenido estático de política de privacidad.

---

## 4.18 `src/pages/WelcomePage.jsx` (BAJA)
**Función:** pantalla de bienvenida/entrada con branding y CTA a inicio.

---

## 5) Aspectos importantes a tener en cuenta (frontend)

### 5.1 Router en modo hash
El uso de `HashRouter` evita problemas de rutas al servir estático en XAMPP/Apache sin configuración extra de rewrite.

### 5.2 Estado global distribuido por eventos
No hay Redux/Context global complejo; se usan eventos de ventana para sincronía puntual:
- `veridi:update-contador`
- `veridi:open-auth`

Es simple y efectivo, pero requiere disciplina para emitir/escuchar correctamente.

### 5.3 Dependencia fuerte del backend JSON
Cada página depende de la forma de respuesta de su endpoint (`ok`, `message`, flags de permiso, payload). Cualquier cambio en contrato backend debe reflejarse en frontend.

### 5.4 Estilos mayormente globales
El proyecto reutiliza `css/styles.css` global del sistema original. Esto acelera migración visual, pero aumenta acoplamiento entre páginas.

### 5.5 Manejo de sesión por cookies
El frontend confía en sesiones PHP (`withCredentials: true`). Si cambian dominio/puerto/HTTPS en despliegue, hay que revisar CORS y cookies.

---

## 6) Recomendaciones de evolución (frontend)
- Introducir componentes reutilizables (formularios, mensajes, modales) para reducir duplicación.
- Añadir capa de tipado (TypeScript o validación runtime de respuestas).
- Estandarizar manejo de errores/timeout en una utilidad común.
- Reducir estilos inline en páginas largas (checkout, confirmación, admin) y moverlos a CSS modular.
- Considerar Context para estado global de usuario/contadores en lugar de eventos globales.

---

## 7) Fuente analizada
- Carpeta principal: `frontend/src/`
- Archivos incluidos: `main.jsx`, `App.jsx`, `components/AppLayout.jsx`, `services/api.js`, `utils/auth.js`, y todas las páginas de `src/pages/`.
