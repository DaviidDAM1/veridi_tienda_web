# Despliegue automático a Hostinger

Este proyecto ya puede desplegar el backend automáticamente a Hostinger usando GitHub Actions.

## Qué se despliega

- `php/`
- `config/`
- `css/`
- `img/`
- `imgnuevas/`
- `includes/`
- `bd/`

El frontend sigue desplegándose en Vercel.

## Qué necesitas configurar en GitHub

En el repositorio, ve a `Settings > Secrets and variables > Actions` y crea estos secretos:

- `HOSTINGER_FTP_SERVER`
- `HOSTINGER_FTP_USERNAME`
- `HOSTINGER_FTP_PASSWORD`
- `HOSTINGER_FTP_REMOTE_DIR`

### Ejemplo de `HOSTINGER_FTP_REMOTE_DIR`

- `/public_html/`

## Cómo funciona

Cada vez que hagas push a `main` con cambios en backend o assets, GitHub Actions subirá esos archivos a Hostinger.

## Importante

- Si cambias solo el frontend, Vercel se encarga.
- Si cambias PHP, config o imágenes del servidor, ya no tendrás que subir nada a mano.
