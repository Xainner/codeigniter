# AGENTS.md

Reglas del proyecto para agentes de IA y desarrolladores.

## Stack

- **Framework**: CodeIgniter 3.4.2 (fork `pocketarc/codeigniter`)
- **Auth**: IonAuth (tercer_party en `application/third_party/ion_auth`)
- **PHP**: >= 7.2 (compatible hasta 8.5)
- **Email**: PHPMailer vía `application/libraries/MY_Email.php`
- **API**: `chriskacerguis/codeigniter-restserver` (`application/controllers/Api.php`)
- **Composer**: `composer_autoload` apunta a `vendor/autoload.php` (raíz)

## Flujo de trabajo Git

### Ramas

- **`main`**: producción estable. Solo acepta merges vía PR.
- **`develop`**: integración continua. Base para toda nueva funcionalidad.
- **`feature/<nombre>`**: creada desde `develop` para nueva funcionalidad.
  Naming: `feature/login-social`, `feature/reportes-ventas`.
- **`hotfix/<nombre>`**: creada desde `main` para bugs críticos en producción.
  Naming: `hotfix/sql-injection-login`, `hotfix/pass-reset-500`.

### Reglas de merge (obligatorias)

- **Nunca** se hace merge/push directo a `main`.
- Todo cambio se entrega como **Pull Request**.
- El merge de una PR a `main` **solo** puede ejecutarlo el **dueño del repositorio**
  tras revisarla y aprobarla.
- Si el agente o un colaborador crea la PR, debe **quedar pendiente de revisión**
  y **no** mergearse por sí mismo.
- El merge a `develop` puede hacerse tras revisión; si el trabajo es de un agente,
  dejar constancia en la descripción de la PR del alcance y pruebas realizadas.

### Commits

- Mensajes claros en inglés, formato imperativo:
  `Add`, `Fix`, `Update`, `Remove`, `Refactor`, `Migrate`.
- Commits atómicos: una responsabilidad por commit.
- No commitear secretos, credenciales ni `vendor/`.

## Base de datos

- **Todo** script SQL vive en la carpeta `database/` del repositorio.
- `database/database.sql` es el **esquema base completo** (creación desde cero).
  Actualmente contiene el esquema de IonAuth (users, groups, users_groups, login_attempts).
- **Obligatorio**: cualquier cambio de BD debe hacerse en los scripts SQL, no solo
  "a mano" en el servidor.

### Cómo reflejar un cambio de BD

1. **Actualizar** `database/database.sql` con el cambio aplicado al esquema base.
2. **Crear** un script de migración incremental:
   `database/upgrade_<nombre-relacionado-al-cambio>_<YYYYMMDD>.sql`
   Ejemplo: `upgrade_add_user_avatar_20260815.sql`
   - El upgrade contiene **solo** las sentencias de migración (ALTER/CREATE/UPDATE)
     necesarias para pasar de la versión anterior a la nueva.
   - Debe ser idempotente en lo posible (usar `IF NOT EXISTS` / comprobaciones).
3. Ambos archivos se entregan en la misma PR.

## Buenas prácticas de programación

- **Seguir MVC de CI3**: controladores delgados, lógica de negocio en modelos o
  librerías (`application/libraries/`), vistas sin queries.
- **No editar `system/`**: el core de CI3 viene del fork upstream. Usar
  `application/core/MY_*` o `application/libraries/MY_*` para extender.
- **Validación**: toda entrada de usuario se valida con `form_validation`
  (server-side). Nunca confiar solo en validación de cliente.
- **Escapado**: usar `html_escape()` o `$this->security->xss_clean()` al imprimir
  datos en vistas. No imprimir entradas crudas.
- **SQL**: usar el Query Builder de CI3 con placeholders; nunca concatenar
  entradas del usuario en SQL.
- **Errores**: manejar y loguear con `log_message()`; no silenciar excepciones.
- **Nombres**: clases `Estilo_Estudio` (CI3), métodos `snake_case`, constantes UPPER.
- **Composer**: nuevas dependencias se agregan con `composer require` y se
  documenta su uso. No editar `vendor/` a mano.

## Seguridad

- Mantener CSRF activado (`csrf_protection = TRUE`) y token presente en todo
  formulario POST (incluidos los AJAX de auth).
- Cookies: `httponly` y `samesite=Lax` (no bajar sin revisión).
- Contraseñas: bcrypt cost >= 12 (config de IonAuth). No inventar hashes propios.
- No exponer errores en producción (`display_errors = 0`).
- No loguear datos sensibles (passwords, tokens, API keys).
- No hardcodear credenciales; usar variables de entorno.
- `.htaccess` ya bloquea `application/`, `system/`, archivos sensibles: no debilitar.

## Pruebas

- Antes de abrir una PR, verificar con `php -l` los archivos PHP modificados.
- Probar el flujo afectado (smoke test) y dejar evidencia en la descripción de la PR.
- Al cambiar configuración de seguridad, validar que los formularios siguen
  funcionando (CSRF token presente, etc.).
