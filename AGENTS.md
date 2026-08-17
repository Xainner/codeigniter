# AGENTS.md

Project rules for AI agents and developers.

## Stack

- **Framework**: CodeIgniter 3.4.2 (`pocketarc/codeigniter` fork) — never edit `system/`.
- **Auth**: IonAuth in `application/third_party/ion_auth` (registered as a package in `autoload.php`).
- **PHP**: >= 7.2, works up to 8.x (dev currently on 8.2).
- **Email**: PHPMailer 6.9 via `application/libraries/MY_Email.php` (extends `CI_Email`, same API, falls back to native transport if vendor is missing).
- **API**: `chriskacerguis/codeigniter-restserver` — config in `application/config/rest.php`, example controller `application/controllers/Api.php`.
- **Composer**: autoloads root `vendor/autoload.php`. `composer.lock` is intentionally gitignored — pin versions in `composer.json` deliberately.

## Quick start

```bash
composer install
php -S localhost:8000
```

1. Set real values in `application/config/database.php` (committed placeholders: `root`/empty/`testdb`).
2. Import `database/database.sql` (IonAuth schema + seed admin `admin@admin.com`).
3. Configure `application/config/email.php` (SMTP) for real email delivery.

Useful URLs: `/auth/login`, `/auth/register`, `/auth/forgot_password`, `/api/ping` (health check).

## Architecture

```
application/
  controllers/   Auth (IonAuth flows: login/register/forgot/reset, HTML + AJAX JSON), Api (REST), Welcome
  models/        (empty today — business logic lives in controllers/libraries)
  libraries/     MY_Email (PHPMailer transport for CI_Email)
  core/          (empty — extend CI3 there with MY_ subclasses)
  config/        database.php, email.php, rest.php, routes.php, config.php (subclass_prefix)
  views/         auth/ (all auth pages on auth_template.php), errors/, welcome_message.php
  language/      english/ and spanish/ packs for auth, ion_auth, rest (default language: english)
  third_party/   ion_auth (library + model + its own config/ion_auth.php)
database/        database.sql (full base schema) + upgrade_*.sql migrations
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
- `database/database.sql` is the **complete base schema** (from-scratch setup). Currently holds the
  IonAuth schema (`users`, `groups`, `users_groups`, `login_attempts`) plus seed data.
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
- **Never edit `system/`**: the CI3 core comes from the upstream fork. Extend via
  `application/core/MY_*` or `application/libraries/MY_*` (`config.php` sets `subclass_prefix = 'MY_'`).
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
- Passwords: bcrypt cost 12 (IonAuth config). Never invent custom hashing.
- Production: `display_errors = 0` (`index.php` already switches error display by `ENVIRONMENT`).
- Never log sensitive data (passwords, tokens, API keys).
- Never put real credentials in the repo: `database.php` and `email.php` ship dev placeholders on
  purpose. This codebase does not read env vars natively — keep credentials out of committed files
  and change them per environment.
- Root `.htaccess` already blocks `application/`, `system/`, and sensitive files: do not weaken it.

## Testing

- Before opening a PR, lint every changed PHP file: `php -l path/to/file.php`.
- Smoke-test the affected flow (e.g. login / register / forgot password via `php -S localhost:8000`)
  and leave evidence in the PR description.
- When changing security configuration, verify forms still work (CSRF token present, AJAX still
  returns JSON).

## Gotchas

- IonAuth only sends email when `use_ci_email = TRUE` in its config — currently `FALSE`, so
  `forgotten_password()` and registration return the code/data instead of sending mail. Enable it
  (and configure `email.php`) before expecting auth emails.
- `database.sql` seeds a default admin account with a well-known hash — rotate credentials in any
  real environment.
- Bilingual UI: language packs live in `application/language/english|spanish`; the default is
  `english`, so new user-facing strings need both packs.
- `models/` and `core/` are empty; do not assume every flow follows "controller → model" today.