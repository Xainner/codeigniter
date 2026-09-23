# CodeIgniter 3 + IonAuth Base

Plantilla de CodeIgniter 3 (fork PocketArc) con IonAuth, PHPMailer y un endpoint de ejemplo.

## Requisitos

- PHP 8.4 con `openssl`, `gd`, `mysqli` y `xml`.
- Composer 2, MySQL o MariaDB.
- Servidor web cuyo directorio publico sea `public/`. `application/`, `vendor/` y los archivos de almacenamiento deben quedar fuera de ese directorio.

## Instalacion

1. Instala las dependencias con `composer install --no-dev`.
2. Crea una base de datos e importa `database/database.sql`.
3. Define las variables de entorno del proceso PHP. Genera `APP_KEY` con `php -r "echo bin2hex(random_bytes(32)), PHP_EOL;"` y mantenla estable entre despliegues. Debe tener 64 caracteres hexadecimales.

| Variable | Uso |
| --- | --- |
| `APP_KEY` | Clave obligatoria de la aplicacion y los ajustes cifrados. No la incluyas en el repositorio. |
| `APP_URL` | URL publica sin barra final; obligatoria en produccion. |
| `CI_ENV` | `development`, `testing` o `production`. |
| `DB_HOST`, `DB_PORT` | Host y puerto de MariaDB/MySQL (`127.0.0.1` y `3306` por defecto). |
| `DB_NAME`, `DB_USER`, `DB_PASS` | Nombre, usuario y contraseña de la base de datos. |
| `APP_SETUP_TOKEN` | Clave de instalacion para crear el primer administrador cuando se active `/auth/setup`. |

4. Sirve `public/` como document root. Para desarrollo local:

```bash
php -S localhost:8000 -t public public/router.php
```

En PowerShell puedes configurar temporalmente las variables con `$env:APP_KEY = '...'` y `$env:DB_NAME = '...'`. En produccion, configura HTTPS, `CI_ENV=production`, `APP_URL` y permisos de escritura para `application/cache/` y `application/logs/`.

El servidor de desarrollo solo sirve los tipos de archivo estatico permitidos desde `public/`; Apache usa `public/.htaccess` para reescribir las rutas. Para Nginx, envía las rutas no estaticas a `public/index.php` y conserva `public/` como unica raiz web.

## URLs

- `/auth/login`: acceso.
- `/auth/register`: registro publico mientras este habilitado.
- `/auth/forgot_password`: recuperacion de contraseña.
- `/api/ping`: endpoint de ejemplo.

La configuracion de transporte de correo reside en `application/config/email.php` hasta que el panel de ajustes la gestione. Esta plantilla contiene un esquema heredado que se reemplazara por un asistente de instalacion para el primer administrador.

## Desarrollo

El framework se instala en `vendor/pocketarc/codeigniter/system/`; no edites `vendor/` directamente. Las extensiones del nucleo pertenecen a `application/core/MY_*`. La correccion de escape de las paginas de error se conserva en `application/core/MY_Exceptions.php`.

Ejecuta `composer validate --no-check-publish`, `composer audit` y los cuatro scripts de `tests/` antes de abrir un PR. `composer.lock` fija PocketArc 3.4.5 y la plataforma PHP 8.4.
