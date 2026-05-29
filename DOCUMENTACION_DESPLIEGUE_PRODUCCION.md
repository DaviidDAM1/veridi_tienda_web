# Documentación de Despliegue a Producción — Veridi Tienda Web

## Índice

1. [Arquitectura elegida](#1-arquitectura-elegida)
2. [Por qué Hostinger y Vercel](#2-por-qué-hostinger-y-vercel)
3. [Fase 1: Preparación del backend en Hostinger](#3-fase-1-preparación-del-backend-en-hostinger)
4. [Fase 2: Base de datos MySQL en Hostinger](#4-fase-2-base-de-datos-mysql-en-hostinger)
5. [Fase 3: Preparación del frontend para Vercel](#5-fase-3-preparación-del-frontend-para-vercel)
6. [Fase 4: Despliegue en Vercel](#6-fase-4-despliegue-en-vercel)
7. [Problemas encontrados y soluciones aplicadas](#7-problemas-encontrados-y-soluciones-aplicadas)
8. [Mejoras de estabilidad aplicadas tras el despliegue](#8-mejoras-de-estabilidad-aplicadas-tras-el-despliegue)
9. [Mejoras de UX móvil](#9-mejoras-de-ux-móvil)
10. [Flujo de trabajo para futuros cambios](#10-flujo-de-trabajo-para-futuros-cambios)

---

## 1. Arquitectura elegida

Este proyecto tiene dos partes claramente separadas:

| Parte | Tecnología | Servidor elegido |
|---|---|---|
| Frontend | React + Vite | Vercel |
| Backend APIs | PHP | Hostinger |
| Base de datos | MySQL | Hostinger |
| Imágenes y assets | Archivos estáticos PHP | Hostinger |

La aplicación sigue un modelo **SPA + API REST**:
- El frontend React corre en Vercel y llama a los endpoints PHP.
- Los endpoints PHP en Hostinger leen y escriben en MySQL.
- Vercel actúa como proxy inverso hacia Hostinger para evitar problemas de CORS.

---

## 2. Por qué Hostinger y Vercel

### Vercel para el frontend
- **Despliegue automático desde GitHub**: cada `git push` lanza un redeploy automático sin intervención manual.
- **Plan gratuito (Hobby/Pasatiempo)** más que suficiente para proyectos personales y de curso.
- **Red CDN global**: los archivos estáticos del frontend se sirven desde el borde (edge) más cercano al usuario.
- **Soporte nativo para Vite/React**: detecta automáticamente el framework y configura el build.
- **Dominios HTTPS gratuitos** con certificado SSL automático (por ejemplo `veridi-tienda-web.vercel.app`).

### Hostinger para el backend
- **Soporte completo para PHP y MySQL** en planes de hosting compartido, que es exactamente lo que usa este proyecto.
- **phpMyAdmin integrado** para administrar la base de datos visualmente.
- **Administrador de archivos web** que permite subir, editar y desarchivrar ficheros sin necesidad de FTP externo.
- En el caso de este proyecto, se usa el espacio cedido por el profesor del curso (`masterendaw.es`), concretamente el subdominio `davidvaldes.masterendaw.es`, sin coste adicional.

---

## 3. Fase 1: Preparación del backend en Hostinger

### Carpetas subidas a `public_html`

Se subieron las siguientes carpetas del proyecto local a la carpeta `public_html` del subdominio en Hostinger:

```
public_html/
├── php/          ← todos los endpoints de la API
├── config/       ← conexion.php e imagenes.php
├── img/          ← imágenes de perfil de usuarios
└── imgnuevas/    ← imágenes de los productos
```

**Proceso de subida**:
1. Se comprimieron las 4 carpetas en un archivo `php.zip` desde Windows.
2. Se subió el zip al administrador de archivos de Hostinger.
3. Se usó la opción **Desarchivar** (equivalente a "Extraer" en Windows) para descomprimir en `public_html`.
4. Se movieron las carpetas al nivel raíz de `public_html` (el zip las dejó dentro de una subcarpeta intermedia).
5. Se eliminó el zip para limpiar.

### Edición de `config/conexion.php`

El archivo de conexión local apuntaba a XAMPP local:

```php
// ANTES (configuración local XAMPP)
$host = "localhost";
$dbname = "veridi";
$user = "root";
$password = "root";
```

Se editó directamente desde el administrador de archivos de Hostinger para usar las credenciales reales del servidor:

```php
// DESPUÉS (configuración producción Hostinger)
$host = "localhost";
$dbname = "u336643015_veridi";   // nombre con prefijo asignado por Hostinger
$user = "u336643015_veridiusr";  // usuario con prefijo asignado por Hostinger
$password = "contraseña_real";   // contraseña creada al crear la BD
```

> **Nota de seguridad**: este archivo **nunca debe subirse a GitHub** con credenciales reales. El repositorio debe tener `config/conexion.php` con valores de ejemplo o estar incluido en `.gitignore`.

---

## 4. Fase 2: Base de datos MySQL en Hostinger

### Creación de la base de datos

1. En el panel de Hostinger → Panel del sitio → **Bases de datos MySQL**.
2. Se creó una nueva base de datos con nombre `veridi`.
3. Se creó un nuevo usuario MySQL.
4. Se asignó el usuario a la base con **todos los permisos**.
5. Hostinger añade automáticamente el prefijo `u336643015_` al nombre de la base y al usuario.

### Problema con la importación del SQL original

El archivo `bd/veridi.sql` original contenía estas líneas al principio:

```sql
DROP DATABASE IF EXISTS veridi;
CREATE DATABASE veridi;
USE veridi;
```

Hostinger (como todo hosting compartido) **bloquea las sentencias `DROP DATABASE` y `CREATE DATABASE`** por seguridad, ya que el usuario no tiene permisos de administración de bases de datos a ese nivel.

**Solución**: se generó un archivo adaptado `bd/veridi_hostinger.sql` eliminando esas 4 líneas conflictivas:

```sql
-- Se eliminaron:
-- DROP DATABASE IF EXISTS veridi;
-- CREATE DATABASE veridi;
-- USE veridi;
-- select*from categorias;   ← también se eliminó este SELECT de prueba

-- El resto del SQL (CREATE TABLE, INSERT, etc.) se mantiene igual
```

El archivo `veridi_hostinger.sql` se importó correctamente en phpMyAdmin:
1. phpMyAdmin → seleccionar la base `u336643015_veridi`
2. Pestaña **Importar**
3. Seleccionar `bd/veridi_hostinger.sql`
4. Pulsar **Continuar**
5. Resultado: `Importación ejecutada exitosamente, 20 consultas ejecutadas`

> **Nota**: el mensaje rojo de `DROP DATABASE` que apareció al final no fue un fallo real de la importación, sino solo el aviso de que esa sentencia específica fue bloqueada. El resto de las tablas y datos se importaron correctamente.

### Verificación del backend

Tras configurar `conexion.php`, se verificó que la API respondía correctamente abriendo en el navegador:

```
https://davidvaldes.masterendaw.es/php/api_tienda.php
```

Resultado esperado (y obtenido): JSON con `"ok": true` y la lista de 53 productos.

---

## 5. Fase 3: Preparación del frontend para Vercel

Para que el frontend funcionara correctamente en Vercel (dominio raíz, sin la ruta local de XAMPP), fue necesario modificar 3 archivos:

### Archivo 1: `frontend/vite.config.js`

**Problema**: el `base` estaba configurado para la ruta de XAMPP local, no para un dominio raíz.

```js
// ANTES
export default defineConfig(({ command }) => ({
  base: command === 'build' ? '/veridi_tienda_web/frontend/dist/' : '/',
  ...
}));
```

```js
// DESPUÉS
export default defineConfig(() => ({
  base: '/',   // Vercel sirve desde la raíz del dominio
  ...
}));
```

### Archivo 2: `frontend/vercel.json` (archivo nuevo creado)

Este archivo le indica a Vercel dos cosas fundamentales:

**a) Proxy inverso hacia Hostinger** — redirige las peticiones `/php/...`, `/img/...` e `/imgnuevas/...` al backend en Hostinger. Esto evita los problemas de CORS (Cross-Origin Resource Sharing) entre dominios distintos y el problema de cookies de sesión entre dominios cruzados.

**b) SPA fallback** — redirige cualquier ruta desconocida a `index.html` para que React Router pueda manejarla. Sin esto, las rutas directas (por ejemplo `veridi-tienda-web.vercel.app/#/tienda`) darían error 404 al recargar la página.

```json
{
  "rewrites": [
    {
      "source": "/php/(.*)",
      "destination": "https://davidvaldes.masterendaw.es/php/$1"
    },
    {
      "source": "/img/(.*)",
      "destination": "https://davidvaldes.masterendaw.es/img/$1"
    },
    {
      "source": "/imgnuevas/(.*)",
      "destination": "https://davidvaldes.masterendaw.es/imgnuevas/$1"
    },
    {
      "source": "/(.*)",
      "destination": "/index.html"
    }
  ]
}
```

> **Por qué es importante el proxy de Vercel**: sin él, el frontend en `veridi-tienda-web.vercel.app` haría peticiones directas a `davidvaldes.masterendaw.es` (dominio diferente), lo que activaría las restricciones CORS del navegador. Con el proxy, Vercel hace las peticiones al backend en nombre del frontend, como si todo fuera el mismo dominio.

### Archivo 3: `frontend/src/services/api.js`

**Problema**: la URL base de la API tenía `http://localhost/veridi_tienda_web` como fallback por defecto, lo que hacía que en producción las peticiones fueran a `localhost` en lugar de usar rutas relativas que el proxy de Vercel pudiera interceptar.

```js
// ANTES
const BACKEND_BASE_URL = (rawEnvBackend === undefined
  ? 'http://localhost/veridi_tienda_web'   // ← siempre apuntaba a localhost
  : String(rawEnvBackend)
).replace(/\/$/, '');
```

```js
// DESPUÉS
const BACKEND_BASE_URL = (rawEnvBackend === undefined
  ? (import.meta.env.DEV ? 'http://localhost/veridi_tienda_web' : '')
  //                                                              ↑
  //     En DEV (XAMPP local) → usa localhost              En producción → rutas relativas
  //     → el proxy de Vite las redirige                   → el proxy de Vercel las intercepta
  : String(rawEnvBackend)
).replace(/\/$/, '');
```

Con este cambio:
- En **local (desarrollo)**: `api.get('/php/api_tienda.php')` → `http://localhost/veridi_tienda_web/php/api_tienda.php` (vía proxy de Vite).
- En **producción (Vercel)**: `api.get('/php/api_tienda.php')` → `https://veridi-tienda-web.vercel.app/php/api_tienda.php` → Vercel lo redirige a `https://davidvaldes.masterendaw.es/php/api_tienda.php`.

---

## 6. Fase 4: Despliegue en Vercel

### Pasos realizados en la interfaz de Vercel

1. Crear cuenta en [vercel.com](https://vercel.com) (plan Pasatiempo/Hobby gratuito).
2. **New Project** → **Continuar con GitHub**.
3. Importar el repositorio `DavidDAM1/veridi_tienda_web`.
4. Configurar el proyecto:
   - **Framework Preset**: Vite (detectado automáticamente al seleccionar la carpeta `Interfaz`/`frontend`)
   - **Root Directory**: `frontend` (carpeta donde está el `package.json` del frontend)
   - **Build Command**: `npm run build`
   - **Output Directory**: `dist`
   - **Variables de entorno**: ninguna (se usa `VITE_BACKEND_BASE_URL` vacía por defecto)
5. Pulsar **Desplegar**.
6. Vercel ejecuta `npm install` + `npm run build` y publica el resultado.

### URL pública resultante

```
https://veridi-tienda-web.vercel.app
```

### Redeploy automático

Cada vez que se hace `git push` a la rama `main`, Vercel detecta el cambio y lanza automáticamente un nuevo deploy sin intervención manual.

---

## 7. Problemas encontrados y soluciones aplicadas

### Problema 1: SQL bloqueado en Hostinger

**Síntoma**: al importar `bd/veridi.sql` en phpMyAdmin, aparecía error rojo con `Las sentencias "DROP DATABASE" están desactivadas`.

**Causa**: Hostinger bloquea comandos de administración de base de datos (`DROP DATABASE`, `CREATE DATABASE`) por seguridad en planes compartidos.

**Solución**: se generó `bd/veridi_hostinger.sql` eliminando esas líneas:
```powershell
$lines = Get-Content "bd\veridi.sql"
$filtered = $lines | Where-Object {
    $_ -notmatch '^DROP DATABASE IF EXISTS veridi;' -and
    $_ -notmatch '^CREATE DATABASE veridi;' -and
    $_ -notmatch '^USE veridi;' -and
    $_ -notmatch '^select\*from categorias;$'
}
Set-Content "bd\veridi_hostinger.sql" -Value $filtered -Encoding UTF8
```

---

### Problema 2: Carpetas del zip en subcarpeta intermedia

**Síntoma**: al desarchivar el zip en Hostinger, las carpetas `php`, `config`, `img`, `imgnuevas` quedaron dentro de una carpeta extra en lugar de directamente en `public_html`.

**Causa**: al comprimir en Windows con `Ctrl + clic derecho → Enviar a → Carpeta comprimida`, Windows crea el zip con el nombre de una carpeta raíz contenedora.

**Solución**: desde el Administrador de archivos de Hostinger se seleccionaron las 4 carpetas y se usó **Mover archivo** hacia `/public_html` directamente.

---

### Problema 3: Productos no cargaban en producción

**Síntoma**: la web en Vercel mostraba "No se pudieron cargar los productos destacados" aunque el backend respondía correctamente.

**Causa**: era un problema de caché del primer deploy. El navegador tenía cacheados recursos del build anterior.

**Solución**: recarga forzada con `Ctrl + F5` y esperar a que Vercel propagara el deploy completamente.

---

### Problema 4: Login no funcionaba visualmente

**Síntoma**: al intentar hacer login, no ocurría nada o aparecía error.

**Diagnóstico realizado**: se ejecutó una prueba automatizada desde terminal simulando el flujo completo registro → login → verificar sesión:

```powershell
$reg   = Invoke-RestMethod -Uri "$base/php/api_auth_react.php" -Method Post -Body (registro_json)
$login = Invoke-RestMethod -Uri "$base/php/api_auth_react.php" -Method Post -Body (login_json)
$user  = Invoke-RestMethod -Uri "$base/php/api_usuario.php"    -Method Get
```

Resultado: `login_ok: True`, `logueado: True` → el backend funcionaba perfectamente.

**Causa real**: el usuario `admin@veridi.com` del SQL de ejemplo tiene una contraseña hasheada que no era `admin123` ni ninguna variante obvia. Simplemente no se conocía esa contraseña.

**Solución 1 (credenciales)**: crear un usuario nuevo desde el formulario de registro de la propia web.

**Solución 2 (reset de admin)**: en phpMyAdmin ejecutar:
```sql
UPDATE usuarios
SET password = '$2y$10$IEhU6XOC32jdRcjY3F01yOCnPGG.cQjWTFo05JgLGidO86AQx9qO.'
WHERE email = 'admin@veridi.com';
```
Esto establece la contraseña `admin1234` para el administrador.

---

### Problema 5: Sesión se cerraba sola de forma intermitente

**Síntoma**: navegando entre páginas, de repente la sesión se cerraba sola y los productos dejaban de cargar temporalmente.

**Causa**: fallos de red intermitentes (timeout o error 5xx transitorio del servidor) hacían que la llamada a `api_usuario.php` fallara. El código original, ante cualquier error de red, ejecutaba `setCurrentUser(null)` lo que cerraba la sesión visualmente aunque en realidad el usuario seguía logueado en el servidor.

**Solución**: se aplicaron dos cambios de estabilidad en `frontend/src/services/api.js` y `frontend/src/components/AppLayout.jsx`:

**En `api.js`** — se aumentó el timeout y se añadió reintento automático para peticiones GET:
```js
// Timeout aumentado de 10s a 20s
timeout: 20000,

// Interceptor de reintento para GET fallidos
api.interceptors.response.use(
  (response) => response,
  async (error) => {
    const shouldRetry = method === 'get' && (isTimeout || isNetworkError || isRetryableStatus);
    if (!shouldRetry || retryCount >= 1) return Promise.reject(error);
    await new Promise((resolve) => setTimeout(resolve, 450));
    return api(config);
  }
);
```

**En `AppLayout.jsx`** — se evita cerrar la sesión ante fallos transitorios de red:
```js
// ANTES: cualquier error de red cerraba sesión
} catch (error) {
  setCurrentUser(null);   // ← esto causaba el cierre de sesión fantasma
  ...
}

// DESPUÉS: solo se cierra sesión si el servidor responde 401 (no autorizado)
} catch (error) {
  if (Number(error?.response?.status) === 401) {
    setCurrentUser(null);   // sesión realmente caducada
    ...
  }
  // Si es error de red/timeout → se mantiene el estado actual
}
```

---

## 8. Mejoras de estabilidad aplicadas tras el despliegue

| Archivo modificado | Cambio | Motivo |
|---|---|---|
| `frontend/src/services/api.js` | Timeout de 10s a 20s | Hosting compartido puede ser lento en horas pico |
| `frontend/src/services/api.js` | Reintento automático en GET | Red intermitente no debe romper la carga de datos |
| `frontend/src/components/AppLayout.jsx` | Solo cierra sesión con 401 | Evita logout fantasma por fallos de red transitorios |

---

## 9. Mejoras de UX móvil

Se añadieron mejoras exclusivamente dentro de los bloques `@media (max-width: 768px)` y `@media (max-width: 480px)` de `css/styles.css`, por lo que **no afectan en absoluto al aspecto en escritorio**.

| Mejora | Descripción |
|---|---|
| Navegación horizontal deslizante | En lugar de apilar los links de nav, ahora se desplazan horizontalmente con el dedo |
| Áreas táctiles mínimas de 44px | Botones, iconos y acciones respetan el tamaño mínimo recomendado por Apple/Google |
| Botones de acción más grandes en móvil | `min-height: 46px` en todos los CTAs principales |
| Hero más legible | Texto limitado a 34 caracteres por línea para mejor lectura en vertical |
| Tarjetas de producto más cómodas | Padding y altura de imagen ajustados para pantallas pequeñas |

---

## 10. Flujo de trabajo para futuros cambios

### Cambios en el frontend (React/CSS/JS)

```bash
# 1. Hacer los cambios en local y verificar con XAMPP
# 2. Subir a GitHub
git add .
git commit -m "Descripción del cambio"
git push
# 3. Vercel redespliega automáticamente en 1-2 minutos
```

### Cambios en el backend (PHP)

1. Modificar el archivo PHP en local.
2. Subir el archivo modificado al Administrador de archivos de Hostinger (misma ruta en `public_html`).
3. No hace falta ningún redeploy en Vercel.

### Cambios en la base de datos

1. Aplicar el cambio SQL directamente en phpMyAdmin de Hostinger.
2. Si es un ALTER TABLE o INSERT masivo, hacerlo también en local para mantener sincronía.

### Volver a una versión anterior

**Opción 1 (recomendada)** — revertir con Git (mantiene historial limpio):
```bash
git log --oneline          # ver commits recientes
git revert HASH_DEL_COMMIT # crea un commit inverso
git push                   # Vercel redespliega con el estado anterior
```

**Opción 2** — rollback desde Vercel:
1. Proyecto en Vercel → Deployments.
2. Elegir un deploy anterior.
3. Pulsar **Promote to Production**.

---

## Resumen final

| Elemento | URL/Estado |
|---|---|
| Frontend (producción) | https://veridi-tienda-web.vercel.app |
| Backend API (producción) | https://davidvaldes.masterendaw.es/php/ |
| Base de datos | MySQL en Hostinger — `u336643015_veridi` |
| Repositorio GitHub | https://github.com/DavidDAM1/veridi_tienda_web |
| Archivos modificados para deploy | `vite.config.js`, `vercel.json` (nuevo), `services/api.js`, `AppLayout.jsx`, `css/styles.css` |
| SQL de producción | `bd/veridi_hostinger.sql` (sin sentencias de BD bloqueadas) |
