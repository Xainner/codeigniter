# CodeIgniter 3 + IonAuth 3 con Login, Register y Forgot Password Mejorados

Este proyecto es una plantilla de CodeIgniter 3 con IonAuth 3, donde se han mejorado las páginas de login, registro y recuperación de contraseña para que sean profesionales, genéricas y listas para adaptar, utilizando AJAX, SweetAlert2 y CSS propio.

## Características

- **Login profesional**: Página de inicio de sesión con diseño limpio, AJAX y SweetAlert2.
- **Registro de usuarios**: Página de registro público con validación AJAX.
- **Recuperación de contraseña**: Página para solicitar recuperación con AJAX y pantalla de restablecimiento unificada.
- **Responsive**: Diseño responsivo con CSS propio.

## Requisitos

- PHP 7.2+
- MySQL/MariaDB
- CodeIgniter 3
- IonAuth 3

## Instalación

1. Clona o descarga el proyecto.
2. Configura la base de datos en `application/config/database.php`.
3. Ejecuta el archivo `ion_auth.sql` en tu base de datos para crear las tablas base de IonAuth.
4. Inicia el servidor: `php -S localhost:8000` desde la raíz del proyecto.

## URLs

- Login: `http://localhost:8000/auth/login`
- Register: `http://localhost:8000/auth/register`
- Forgot Password: `http://localhost:8000/auth/forgot_password`

## Librerías utilizadas

- **SweetAlert2**: Para alertas bonitas.
- **jQuery**: Para AJAX.

## Notas

- Las vistas usan AJAX para enviar formularios sin recargar la página.
- Los controladores devuelven JSON para respuestas AJAX.
- Asegúrate de configurar correctamente IonAuth para emails (forgot password).

## Personalización

- Modifica los estilos CSS en las vistas para cambiar colores, fuentes, etc.
- Agrega más campos al registro editando el controlador y la vista.
