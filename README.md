# CodeIgniter 3 + IonAuth Base

Plantilla base de CodeIgniter 3 con IonAuth 3, login, registro publico, recuperacion de contrasena y restablecimiento de acceso.

## Caracteristicas

- Login con AJAX y SweetAlert2.
- Registro publico de usuarios.
- Recuperacion y restablecimiento de contrasena.
- Vistas de auth unificadas en una plantilla responsive.
- Estructura limpia para iniciar nuevos proyectos sobre CodeIgniter 3.

## Requisitos

- PHP 7.2 o superior.
- MySQL o MariaDB.
- Servidor web compatible con CodeIgniter 3.

## Instalacion

1. Configura la base de datos en `application/config/database.php`.
2. Ejecuta `database/database.sql` en tu base de datos.
3. Revisa la configuracion base en `application/config/config.php`.
4. Inicia el proyecto desde la raiz, por ejemplo:

```bash
php -S localhost:8000
```

## URLs utiles

- Login: `http://localhost:8000/auth/login`
- Register: `http://localhost:8000/auth/register`
- Forgot Password: `http://localhost:8000/auth/forgot_password`

## Personalizacion

- Ajusta las vistas de autenticacion en `application/views/auth/`.
- Cambia reglas y campos de registro en `application/controllers/Auth.php`.
- Configura IonAuth en `application/third_party/ion_auth/config/ion_auth.php`.
