# CodeIgniter 3 + IonAuth 3 con Login, Register y Forgot Password Mejorados

Este proyecto es una plantilla de CodeIgniter 3 con IonAuth 3, donde se han mejorado las páginas de login, registro y recuperación de contraseña para que sean visualmente atractivas, utilizando AJAX, SweetAlert2, Bootstrap y mostrando información de la empresa desde la base de datos.

## Características

- **Login atractivo**: Página de inicio de sesión con diseño moderno, AJAX y SweetAlert2.
- **Registro de usuarios**: Página de registro público con validación AJAX.
- **Recuperación de contraseña**: Página para recuperar contraseña con AJAX.
- **Información de empresa**: Muestra logo, nombre, descripción, email y teléfono de la empresa desde la tabla `company`.
- **Responsive**: Diseño responsivo con Bootstrap 5.

## Requisitos

- PHP 7.2+
- MySQL/MariaDB
- CodeIgniter 3
- IonAuth 3

## Instalación

1. Clona o descarga el proyecto.
2. Configura la base de datos en `application/config/database.php`.
3. Ejecuta el archivo `company_table.sql` en tu base de datos para crear la tabla `company`.
4. Asegúrate de que la carpeta `uploads` exista y tenga permisos de escritura para el logo de la empresa.
5. Inicia el servidor: `php -S localhost:8000` desde la raíz del proyecto.

## URLs

- Login: `http://localhost:8000/auth/login`
- Register: `http://localhost:8000/auth/register`
- Forgot Password: `http://localhost:8000/auth/forgot_password`

## Librerías utilizadas

- **Bootstrap 5**: Para el diseño responsivo.
- **SweetAlert2**: Para alertas bonitas.
- **jQuery**: Para AJAX.
- **Font Awesome**: (Opcional, si quieres íconos).

## Notas

- Las vistas usan AJAX para enviar formularios sin recargar la página.
- Los controladores devuelven JSON para respuestas AJAX.
- La información de la empresa se carga desde la tabla `company`.
- Asegúrate de configurar correctamente IonAuth para emails (forgot password).

## Personalización

- Modifica los estilos CSS en las vistas para cambiar colores, fuentes, etc.
- Agrega más campos al registro editando el controlador y la vista.
- Para el logo, sube la imagen a `uploads/` y actualiza la BD.