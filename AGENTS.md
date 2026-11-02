# AGENTS.md

Project rules for AI agents and developers.

## Stack

- **Framework**: CodeIgniter (`pocketarc/codeigniter` 3.4.7) installed by Composer in `vendor/pocketarc/codeigniter/system/`.
- **Auth**: IonAuth in `application/third_party/ion_auth` (registered as a package in `autoload.php`).
- **PHP**: >= 8.4 with `curl`, `openssl`, `gd`, `mbstring`, `mysqli` and `xml`.
- **2FA**: TOTP via `spomky-labs/otphp` with QR codes from `bacon/bacon-qr-code`, wrapped by
  `application/libraries/Two_factor.php`.
- **Email**: PHPMailer 7.1 via `application/libraries/MY_Email.php` (extends `CI_Email`, same API). The web entry point requires Composer dependencies.
- **API**: `chriskacerguis/codeigniter-restserver` — config in `application/config/rest.php`. API controllers extend
  `application/core/Api_controller.php` (X-API-Key authentication); example controller `application/controllers/Api.php`.
- **Composer**: autoloads root `vendor/autoload.php`. Commit `composer.lock` and keep Composer's platform PHP at 8.4.

## Quick start

```bash
composer install
php -S localhost:8000 -t public public/router.php
```

1. Set `APP_KEY` (64 hexadecimal characters), `APP_URL`, `CI_ENV`, `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER` and `DB_PASS` in the PHP process environment. `APP_KEY` is required even in development.
   Optional: `TRUSTED_PROXIES` (comma-separated proxy IPs/CIDRs in front of PHP), `LOG_THRESHOLD`
   (defaults to `1`, errors only; `0` disables logging), `SESSION_DRIVER` (`files` by default,
   `database` uses `ci_sessions`, `redis` needs `SESSION_SAVE_PATH`) and `SESSION_SAVE_PATH`.
2. Import `database/database.sql` into an empty database. Set a random `APP_SETUP_TOKEN` of at least 32 characters, then create the first administrator at `/auth/setup`. The wizard permanently closes after completion.
3. Configure and test SMTP from `/auth/settings`. Public registration starts disabled.

Useful URLs: `/auth`, `/auth/audit`, `/auth/api-keys`, `/auth/profile`, `/auth/security`, `/auth/settings`, `/auth/login`, `/auth/setup`, `/auth/register` (when enabled), `/auth/forgot_password`, `/api/ping`.

## Architecture

```
application/
  controllers/   Auth, Setup, Account_security, Admin_users, Admin_groups, Admin_settings, Admin_audit,
                 Admin_api_keys, Brand, Api, Welcome
  models/        App_settings_model, Admin_user_model, Admin_audit_model, Rate_limiter_model, User_mfa_model,
                 Api_key_model
  libraries/     MY_Email (PHPMailer transport for CI_Email), Secret_box (purpose-bound encryption),
                 Two_factor (TOTP and recovery codes), Password_policy (zxcvbn strength check)
  core/          MY_Controller (settings, load_auth(), shared auth helpers), MY_Exceptions (HTML error escaping),
                 Api_controller (API key authentication for REST controllers)
  config/        database.php, email.php, rest.php, routes.php, config.php (subclass_prefix)
  views/         auth/, dashboard/, errors/, welcome_message.php
  language/      english/ and spanish/ packs for auth, ion_auth, rest; spanish/ also carries the CI3
                 system translations (default language: spanish)
  third_party/   ion_auth (library + model + its own config/ion_auth.php)
database/        database.sql (full base schema) + upgrade_*.sql migrations
public/          web document root, index.php, router.php, assets/, .htaccess
vendor/          Composer packages, including PocketArc system/ (ignored by Git)
```

Conventions: controllers that need authentication extend `MY_Controller` and call `$this->load_auth()`
instead of loading `ion_auth` directly; use `require_admin()`, `csrf_data()` and
`register_active_user()` from `MY_Controller` rather than re-implementing them. `Auth` fills
`$this->data` (title, message, per-field input arrays) and renders through `_render_page()`. REST endpoints are controller methods named `<resource>_<verb>` (e.g. `ping_get`) in controllers that
extend `Api_controller`; every method requires an API key unless listed in `$public_methods`.

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
    (`_get_csrf_nonce()` / `_valid_csrf_nonce()` in `MY_Controller`). Keep both in place.
- Cookies: `httponly` + `samesite=Lax`; do not weaken without review.
- `MY_Controller` sets IonAuth's `recheck_timer` to one second so deactivated users lose
  existing sessions promptly. Keep a two-session integration test when changing this behavior.
- `load_auth()` binds every session to an HMAC of the password hash through IonAuth hooks, so any
  password change ends the user's other sessions. Changing one's own email or password requires the
  current password. IonAuth's `logout()` does not revoke the remember-me token; use
  `revoke_remember_token()` alongside it.
- `load_auth()` also regenerates the session id, destroying the old one, before IonAuth stores a
  login: IonAuth's own `sess_regenerate(FALSE)` would leave a pre-login (possibly planted) id signed in.
- `Auth::login()` caps failed logins per address (4 × `maximum_login_attempts` within
  `lockout_time`, counted from `login_attempts`) on top of IonAuth's identity + address lockout,
  and spends the same password-hashing work for unknown accounts as for existing ones so timing
  does not reveal which accounts exist.
- Two-factor authentication: when `user_mfa` has a row for the account, `Auth::login()` signs the
  session out again after the password step (`mfa_pending`, five minutes) until `Auth::two_factor()`
  accepts a TOTP code or an unused recovery code. Codes are single use (`last_step`, `used_at`),
  attempts share the `mfa-user` limit, and remember-me cookies are issued only after the second
  step. Enabling, disabling and regenerating codes require the current password; resets by an
  administrator and self-service changes are audited.
- API keys (`/auth/api-keys`) are shown once and stored as HMACs (`Api_key_model`); `api/` routes are
  excluded from CSRF because they authenticate with `X-API-Key` only. Never read the session in API
  controllers.
- Account and group administration is recorded in `admin_audit` (field names and ids only, never
  personal values or secrets); settings changes go to `settings_audit`.
- `public/index.php` sends a Content-Security-Policy with `script-src 'self'`: keep JavaScript in
  `public/assets/` and never add inline scripts or event handler attributes.
- Secrets the application stores (SMTP password, TOTP secrets) are sealed with `Secret_box`
  (AES-256-GCM with a per-purpose HKDF subkey of `APP_KEY` and the purpose as associated data).
  Derive HMAC keys with `Secret_box::key($purpose)`; never use `APP_KEY` directly.
- Passwords: bcrypt cost 12 (IonAuth config). Never invent custom hashing. Validate every new
  password with `new_password_rules()` from `MY_Controller`: besides the minimum length it rejects
  passwords that `bjeavons/zxcvbn-php` scores below 3, including ones built from the account's data.
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

- `MY_Controller::load_auth()` applies database settings to IonAuth before construction and enables CI email.
  SMTP is configured and verified in the dashboard; recovery reports unavailable until then, and also
  while `APP_URL` is not an absolute URL (email links are built from it).
- Use `database/upgrade_auth_settings_20260923.sql` for existing databases. It marks setup complete
  when users already exist, so existing installations cannot reopen the setup wizard. Then apply
  `database/upgrade_add_admin_audit_20261010.sql`, `database/upgrade_widen_login_attempts_20261010.sql`,
  `database/upgrade_add_two_factor_20261102.sql`, `database/upgrade_add_api_keys_20261102.sql`
  and `database/upgrade_add_sessions_20261102.sql`.
- IonAuth's `activate($id, $code)` compares the id strictly with the string mysqli returns. Resolve the
  user with `get_user_by_activation_code()` and activate by the returned id (see `Auth::activate()`).
- IonAuth language packs live in `application/language/english|spanish`; the dashboard and setup copy
  is Spanish. Keep new dashboard text consistent and escape it in views.
- `.github/workflows/ci.yml` runs lint and the smoke suites on PHP 8.4/8.5/8.6 and the integration
  suite on PHP 8.4 with MariaDB, once with file sessions and once with database sessions (including
  dashboard query counts).
