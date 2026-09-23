# AGENTS.md

Project rules for AI agents and developers.

## Stack

- **Framework**: CodeIgniter (`pocketarc/codeigniter` 3.4.5) installed by Composer in `vendor/pocketarc/codeigniter/system/`.
- **Auth**: IonAuth in `application/third_party/ion_auth` (registered as a package in `autoload.php`).
- **PHP**: >= 8.4 with `curl`, `openssl`, `gd`, `mbstring`, `mysqli` and `xml`.
- **Email**: PHPMailer 7.1 via `application/libraries/MY_Email.php` (extends `CI_Email`, same API). The web entry point requires Composer dependencies.
- **API**: `chriskacerguis/codeigniter-restserver` — config in `application/config/rest.php`, example controller `application/controllers/Api.php`.
- **Composer**: autoloads root `vendor/autoload.php`. Commit `composer.lock` and keep Composer's platform PHP at 8.4.

## Quick start

```bash
composer install
php -S localhost:8000 -t public public/router.php
```

1. Set `APP_KEY` (64 hexadecimal characters), `APP_URL`, `CI_ENV`, `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER` and `DB_PASS` in the PHP process environment. `APP_KEY` is required even in development.
2. Import `database/database.sql` into an empty database. Set a random `APP_SETUP_TOKEN` of at least 32 characters, then create the first administrator at `/auth/setup`. The wizard permanently closes after completion.
3. Configure and test SMTP from `/auth/settings`. Public registration starts disabled.

Useful URLs: `/auth`, `/auth/settings`, `/auth/login`, `/auth/setup`, `/auth/register` (when enabled), `/auth/forgot_password`, `/api/ping`.

## Architecture

```
application/
  controllers/   Auth, Setup, Admin_users, Admin_groups, Admin_settings, Brand, Api, Welcome
  models/        App_settings_model, Admin_user_model, Rate_limiter_model
  libraries/     MY_Email (PHPMailer transport for CI_Email)
  core/          MY_Exceptions keeps HTML error escaping outside vendor/
  config/        database.php, email.php, rest.php, routes.php, config.php (subclass_prefix)
  views/         auth/, dashboard/, errors/, welcome_message.php
  language/      english/ and spanish/ packs for auth, ion_auth, rest (default language: english)
  third_party/   ion_auth (library + model + its own config/ion_auth.php)
database/        database.sql (full base schema) + upgrade_*.sql migrations
public/          web document root, index.php, router.php, assets/, .htaccess
vendor/          Composer packages, including PocketArc system/ (ignored by Git)
```

Conventions: controllers fill `$this->data` (title, message, per-field input arrays) and render through
`_render_page()`. REST endpoints are controller methods named `<resource>_<verb>` (e.g. `ping_get`).

## Git workflow

### Branches

- **`main`**: stable production. Merges only via PR. Note: it does not exist on origin yet — the
  current default branch is `develop`.
- **`develop`**: continuous integration. Base for all new work.
- **`feature/<name>`**: branched from `develop` for new features.
  Naming: `feature/login-social`, `feature/sales-reports`.
- **`hotfix/<name>`**: branched from `main` for critical production bugs.
  Naming: `hotfix/sql-injection-login`, `hotfix/pass-reset-500`.

### Merge rules (mandatory)

- **Never** merge/push directly to `main`.
- Every change is delivered as a **Pull Request**.
- Merging a PR into `main` is done **only** by the **repository owner** after review and approval.
- If an agent or collaborator opens the PR, it **stays pending review** and is **never self-merged**.
- Merging into `develop` may happen after review; when the work was done by an agent, record scope
  and tests performed in the PR description.

### Commits

- Clear messages in English, imperative form: `Add`, `Fix`, `Update`, `Remove`, `Refactor`, `Migrate`.
- Atomic commits: one responsibility per commit.
- Never commit secrets, credentials, or `vendor/`.

## Database

- **All** SQL lives in `database/`.
- `database/database.sql` is the **complete base schema** (from-scratch setup). It includes IonAuth,
  settings, audit and rate-limit tables but no known administrator credentials.
- **Mandatory**: every DB change must be reflected in the SQL scripts, not done "by hand" on a server.

### Reflecting a DB change

1. **Update** `database/database.sql` with the change applied to the base schema.
2. **Create** an incremental migration: `database/upgrade_<change-name>_<YYYYMMDD>.sql`
   (e.g. `upgrade_add_user_avatar_20260815.sql`).
   - It contains **only** the ALTER/CREATE/UPDATE statements needed to reach the new version.
   - Make it idempotent where possible (`IF NOT EXISTS` / guards).
3. Ship both files in the same PR.

## Coding practices

- **Follow CI3 MVC**: thin controllers, business logic in models or libraries
  (`application/libraries/`), no queries in views.
- **Keep core changes traceable**: extend routine application behavior via
  `application/core/MY_*` or `application/libraries/MY_*` (`config.php` sets `subclass_prefix = 'MY_'`).
  Do not edit `vendor/pocketarc/codeigniter/system/`; update the dependency or extend it from `application/`.
- **Validation**: validate all user input server-side with `form_validation`.
  Never rely on client-side validation alone.
- **Escaping**: use `html_escape()` or `$this->security->xss_clean()` when printing data in views.
  Never print raw input.
- **SQL**: use the CI3 Query Builder with placeholders; never concatenate user input into SQL.
- **Errors**: handle and log with `log_message()`; never swallow exceptions.
- **Naming**: classes `Studly_Case` (CI3), methods `snake_case`, constants UPPER_CASE.
- **Composer**: add new dependencies with `composer require` and document their usage.
  Never edit `vendor/` by hand.

## Security

- Keep CSRF enabled (`csrf_protection = TRUE`) with the token present in every POST form,
  including auth AJAX.
- Auth views add a second layer: a session-based CSRF nonce
    (`_get_csrf_nonce()` / `_valid_csrf_nonce()` in `Auth.php`). Keep both in place.
- Cookies: `httponly` + `samesite=Lax`; do not weaken without review.
- `MY_Controller` sets IonAuth's `recheck_timer` to one second so deactivated users lose
  existing sessions promptly. Keep a two-session integration test when changing this behavior.
- Passwords: bcrypt cost 12 (IonAuth config). Never invent custom hashing.
- Production: `display_errors = 0` (`public/index.php` switches error display by `ENVIRONMENT`).
- Never log sensitive data (passwords, tokens, API keys).
- Never put real credentials in the repo. The database reads environment variables, and `APP_KEY`
  must be a stable, external 64-character hexadecimal key. Do not disclose or rotate it casually.
- Keep `APP_SETUP_TOKEN` outside the repository and remove it from the runtime environment after setup.
- Store brand images only under private `storage/brand/`; never serve that directory directly.
- Configure the web server's document root as `public/`; `application/`, `vendor/` and storage
  must stay outside it. Keep `public/.htaccess` and the development router restrictive.

## Testing

- Before opening a PR, lint every changed PHP file: `php -l path/to/file.php`.
- Smoke-test the affected flow (e.g. login / register / forgot password via `php -S localhost:8000 -t public public/router.php`)
  and leave evidence in the PR description.
- When changing security configuration, verify forms still work (CSRF token present, AJAX still
  returns JSON).

## Gotchas

- `MY_Controller` applies database settings to IonAuth before construction and enables CI email.
  SMTP is configured and verified in the dashboard; recovery reports unavailable until then.
- Use `database/upgrade_auth_settings_20260923.sql` for existing databases. It marks setup complete
  when users already exist, so existing installations cannot reopen the setup wizard.
- IonAuth language packs live in `application/language/english|spanish`; the dashboard and setup copy
  is Spanish. Keep new dashboard text consistent and escape it in views.
- The integration workflow runs on PHP 8.4 with MariaDB and measures dashboard query counts.
