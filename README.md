# CodeIgniter 3: autenticación y dashboard

Plantilla de CodeIgniter 3 (fork PocketArc) con IonAuth, PHPMailer y un dashboard administrativo en `/auth`.

## Requisitos

- PHP 8.4 con `curl`, `openssl`, `gd`, `mbstring`, `mysqli` y `xml`.
- Composer 2, MySQL o MariaDB.
- Servidor web cuyo directorio publico sea `public/`. `application/`, `vendor/` y los archivos de almacenamiento deben quedar fuera de ese directorio.

## Instalacion

1. Instala las dependencias con `composer install --no-dev`.
2. Crea una base de datos vacía e importa `database/database.sql`. El esquema no instala cuentas conocidas.
3. Define las variables de entorno del proceso PHP. Genera `APP_KEY` con `php -r "echo bin2hex(random_bytes(32)), PHP_EOL;"` y mantenla estable entre despliegues. Debe tener 64 caracteres hexadecimales. Genera también `APP_SETUP_TOKEN` aleatoriamente, con al menos 32 caracteres.

| Variable | Uso |
| --- | --- |
| `APP_KEY` | Clave obligatoria de la aplicacion y los ajustes cifrados. No la incluyas en el repositorio. |
| `APP_URL` | URL publica sin barra final; obligatoria en produccion. |
| `CI_ENV` | `development`, `testing` o `production`. |
| `DB_HOST`, `DB_PORT` | Host y puerto de MariaDB/MySQL (`127.0.0.1` y `3306` por defecto). |
| `DB_NAME`, `DB_USER`, `DB_PASS` | Nombre, usuario y contraseña de la base de datos. |
| `APP_SETUP_TOKEN` | Clave del asistente inicial; se puede retirar después de crear el primer administrador. Nunca se incluye en la URL. |
| `TRUSTED_PROXIES` | Opcional. IPs o rangos CIDR (separados por comas) de los proxies o balanceadores delante de PHP. Sin ella, detrás de un proxy todos los visitantes comparten IP y los bloqueos por IP se vuelven globales. |
| `LOG_THRESHOLD` | Opcional. Nivel de log de CodeIgniter; por defecto `1` (solo errores) en `application/logs/`. `0` lo desactiva. |

4. Sirve `public/` como document root y abre `/auth/setup` para crear el primer administrador. El asistente exige una base sin usuarios y se cierra permanentemente al completarse. Después configura y prueba SMTP en `/auth/settings`. Para desarrollo local:

```bash
php -S localhost:8000 -t public public/router.php
```

En PowerShell puedes configurar temporalmente las variables con `$env:APP_KEY = '...'` y `$env:DB_NAME = '...'`. En produccion, configura HTTPS, `CI_ENV=production`, `APP_URL` y permisos de escritura para `application/cache/`, `application/logs/` y `storage/brand/`. Conserva `APP_KEY` para poder descifrar la contraseña SMTP.

El servidor de desarrollo solo sirve los tipos de archivo estatico permitidos desde `public/`; Apache usa `public/.htaccess` para reescribir las rutas. Para Nginx, envía las rutas no estaticas a `public/index.php` y conserva `public/` como unica raiz web.

## URLs

- `/auth/login`: acceso.
- `/auth`: dashboard de usuarios y grupos para administradores.
- `/auth/settings`: registro, seguridad, SMTP y marca; los cambios se auditan.
- `/auth/register`: registro publico desactivado por defecto; devuelve 404 mientras siga desactivado.
- `/auth/forgot_password`: recuperacion de contraseña.
- `/api/ping`: devuelve unicamente `{"status":"ok"}` en JSON.

Para actualizar una instalación previa, haz una copia de la base y ejecuta `database/upgrade_auth_settings_20260923.sql` y después `database/upgrade_add_admin_audit_20261010.sql` y `database/upgrade_widen_login_attempts_20261010.sql`. Las migraciones conservan usuarios existentes y marcan el asistente como completado. Después cambia el document root a `public/`.

Las sesiones quedan ligadas a la contraseña con la que se iniciaron: cualquier cambio de contraseña cierra las demás sesiones de esa cuenta. Tras desplegar esta versión, las sesiones abiertas previamente deben iniciar sesión una vez más.

## Desarrollo

El framework se instala en `vendor/pocketarc/codeigniter/system/`; no edites `vendor/` directamente. Las extensiones del nucleo pertenecen a `application/core/MY_*`. La correccion de escape de las paginas de error se conserva en `application/core/MY_Exceptions.php`.

Ejecuta `composer validate --no-check-publish`, `composer audit`, lint de los PHP modificados y los scripts existentes de `tests/` antes de abrir un PR. `.github/workflows/ci.yml` ejecuta lint y las pruebas de humo en PHP 8.4, 8.5 y 8.6, y la integración en PHP 8.4 con MariaDB y una base desechable; incluye la medición de consultas del listado y la revocación de sesiones tras desactivar una cuenta o cambiar su contraseña. `composer.lock` fija PocketArc 3.4.5 y la plataforma PHP 8.4.
