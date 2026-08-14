<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo html_escape($title ?? 'Acceso'); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <?php
        $auth_variant = $auth_variant ?? 'login';
        $messageText = trim(strip_tags((string) ($message ?? '')));

        $config = [
            'login' => [
                'eyebrow' => 'Acceso',
                'heading' => 'Iniciar sesion',
                'description' => 'Ingresa tus credenciales para continuar.',
                'form_id' => 'loginForm',
                'endpoint' => base_url('auth/login'),
                'success_title' => 'Sesion iniciada',
                'submit' => 'Iniciar sesion',
                'loading' => 'Validando...',
                'ajax' => TRUE,
            ],
            'register' => [
                'eyebrow' => 'Registro',
                'heading' => 'Crear cuenta',
                'description' => 'Completa tus datos para crear un acceso nuevo.',
                'form_id' => 'registerForm',
                'endpoint' => base_url('auth/register'),
                'success_title' => 'Cuenta creada',
                'submit' => 'Crear cuenta',
                'loading' => 'Creando cuenta...',
                'ajax' => TRUE,
            ],
            'forgot_password' => [
                'eyebrow' => 'Recuperacion',
                'heading' => 'Recuperar acceso',
                'description' => 'Escribe tu dato de acceso y te enviaremos las instrucciones.',
                'form_id' => 'forgotForm',
                'endpoint' => base_url('auth/forgot_password'),
                'success_title' => 'Solicitud recibida',
                'submit' => 'Enviar instrucciones',
                'loading' => 'Enviando...',
                'ajax' => TRUE,
            ],
            'reset_password' => [
                'eyebrow' => 'Nueva contrasena',
                'heading' => 'Restablecer acceso',
                'description' => 'Define una contrasena nueva para tu cuenta.',
                'form_id' => 'resetPasswordForm',
                'endpoint' => base_url('auth/reset_password/' . ($code ?? '')),
                'success_title' => 'Contrasena actualizada',
                'submit' => 'Actualizar contrasena',
                'loading' => 'Actualizando...',
                'ajax' => FALSE,
            ],
        ];

        $view = $config[$auth_variant] ?? $config['login'];
        $identityType = (($type ?? 'email') !== 'email') ? 'text' : 'email';
        $identityLabel = (($type ?? 'email') !== 'email') ? 'Usuario o correo' : 'Correo electronico';
    ?>
    <style>
        :root {
            --surface: #ffffff;
            --ink: #111827;
            --muted: #5b6472;
            --line: #d9dee7;
            --line-strong: #b9c2d0;
            --primary: #4f46e5;
            --primary-hover: #4338ca;
            --primary-soft: #eef2ff;
            --action: #059669;
            --action-hover: #047857;
            --danger: #b42318;
            --danger-soft: #fff1f2;
            --focus: rgba(79, 70, 229, 0.18);
        }

        * { box-sizing: border-box; }

        html,
        body {
            min-height: 100%;
        }

        body {
            margin: 0;
            min-height: 100svh;
            overflow-x: hidden;
            font-family: 'Plus Jakarta Sans', Arial, sans-serif;
            color: var(--ink);
            background: transparent;
        }

        a {
            color: var(--primary);
            font-weight: 700;
            text-decoration: none;
            transition: color 180ms ease;
        }

        a:hover { color: var(--primary-hover); }
        a:focus-visible,
        button:focus-visible,
        input:focus-visible {
            outline: 3px solid var(--focus);
            outline-offset: 2px;
        }

        .auth-shell {
            width: 100%;
            min-height: 100svh;
            display: grid;
            place-items: center;
            padding: 32px 18px;
        }

        .auth-card {
            width: min(100%, 520px);
            border: 1px solid var(--line);
            border-radius: 8px;
            background: var(--surface);
        }

        .auth-register .auth-card {
            width: min(100%, 760px);
        }

        .auth-panel {
            padding: 40px;
        }

        .auth-header {
            margin-bottom: 30px;
        }

        .panel-eyebrow {
            display: inline-flex;
            align-items: center;
            min-height: 28px;
            margin-bottom: 14px;
            padding: 4px 10px;
            border: 1px solid #c7d2fe;
            border-radius: 999px;
            background: var(--primary-soft);
            color: var(--primary-hover);
            font-size: 0.76rem;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .panel-title {
            margin: 0;
            color: var(--ink);
            font-size: clamp(1.8rem, 4vw, 2.35rem);
            font-weight: 700;
            line-height: 1.1;
            letter-spacing: 0;
        }

        .panel-description {
            margin: 12px 0 0;
            max-width: 36rem;
            color: var(--muted);
            font-size: 0.98rem;
            line-height: 1.65;
        }

        .auth-alert {
            margin-bottom: 22px;
            border: 1px solid #fecdd3;
            border-radius: 8px;
            background: var(--danger-soft);
            color: var(--danger);
            padding: 13px 14px;
            font-size: 0.93rem;
            line-height: 1.55;
        }

        .auth-form {
            margin: 0;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 18px;
        }

        .form-group {
            margin-bottom: 18px;
        }

        .form-group:last-child {
            margin-bottom: 0;
        }

        .form-group-full {
            grid-column: 1 / -1;
        }

        .form-label {
            display: block;
            margin-bottom: 8px;
            color: var(--ink);
            font-size: 0.9rem;
            font-weight: 700;
            line-height: 1.3;
        }

        .form-meta {
            color: var(--muted);
            font-size: 0.78rem;
            font-weight: 600;
        }

        .form-control {
            display: block;
            width: 100%;
            min-height: 48px;
            border: 1px solid var(--line-strong);
            border-radius: 8px;
            background: #ffffff;
            color: var(--ink);
            padding: 12px 13px;
            font: inherit;
            line-height: 1.4;
            transition: border-color 180ms ease, box-shadow 180ms ease, background-color 180ms ease;
        }

        .form-control::placeholder {
            color: #8a94a6;
        }

        .form-control:hover {
            border-color: #98a2b3;
        }

        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 4px var(--focus);
        }

        .form-options {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            margin: 2px 0 22px;
            color: var(--muted);
            font-size: 0.9rem;
        }

        .form-check {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            margin: 0;
            min-width: 0;
        }

        .form-check-input {
            width: 18px;
            height: 18px;
            margin: 0;
            border: 1px solid var(--line-strong);
            border-radius: 4px;
            accent-color: var(--primary);
            cursor: pointer;
        }

        .form-check-label {
            cursor: pointer;
            user-select: none;
        }

        .auth-button {
            display: inline-flex;
            width: 100%;
            min-height: 50px;
            align-items: center;
            justify-content: center;
            border: 1px solid var(--action);
            border-radius: 8px;
            background: var(--action);
            color: #ffffff;
            cursor: pointer;
            font: inherit;
            font-size: 0.95rem;
            font-weight: 700;
            line-height: 1;
            transition: background-color 180ms ease, border-color 180ms ease, color 180ms ease;
        }

        .auth-button:hover {
            border-color: var(--action-hover);
            background: var(--action-hover);
            color: #ffffff;
        }

        .auth-button:disabled {
            cursor: wait;
            opacity: 0.72;
        }

        .auth-links {
            display: grid;
            gap: 10px;
            margin-top: 24px;
            color: var(--muted);
            font-size: 0.92rem;
            text-align: center;
        }

        .auth-links span {
            color: var(--muted);
        }

        @media (max-width: 720px) {
            .auth-panel {
                padding: 30px 22px;
            }

            .form-grid {
                grid-template-columns: 1fr;
                gap: 0;
            }
        }

        @media (max-width: 420px) {
            .auth-shell {
                padding: 18px 12px;
            }

            .auth-panel {
                padding: 24px 18px;
            }

            .form-options {
                align-items: flex-start;
                flex-direction: column;
                gap: 12px;
            }

            .panel-title {
                font-size: 1.7rem;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            *,
            *::before,
            *::after {
                scroll-behavior: auto !important;
                transition-duration: 0.01ms !important;
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
            }
        }
    </style>
</head>
<body class="auth-page auth-<?php echo html_escape($auth_variant); ?>">
    <main class="auth-shell">
        <section class="auth-card" aria-labelledby="auth-title">
            <section class="auth-panel">
                <header class="auth-header">
                    <div class="panel-eyebrow"><?php echo html_escape($view['eyebrow']); ?></div>
                    <h1 class="panel-title" id="auth-title"><?php echo html_escape($view['heading']); ?></h1>
                    <p class="panel-description"><?php echo html_escape($view['description']); ?></p>
                </header>

                <?php if ($messageText !== ''): ?>
                    <div class="auth-alert" role="alert"><?php echo html_escape($messageText); ?></div>
                <?php endif; ?>

                <?php if ($auth_variant === 'login'): ?>
                    <form class="auth-form" id="loginForm" action="<?php echo html_escape($view['endpoint']); ?>" method="post" data-ajax-form="true">
                        <input type="hidden" name="<?php echo $this->security->get_csrf_token_name(); ?>" value="<?php echo $this->security->get_csrf_hash(); ?>">
                        <div class="form-group">
                            <label class="form-label" for="identity">Correo o usuario</label>
                            <input type="text" class="form-control" id="identity" name="identity" placeholder="tu@correo.com" value="<?php echo html_escape(set_value('identity')); ?>" autocomplete="username" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="password">Contrasena</label>
                            <input type="password" class="form-control" id="password" name="password" placeholder="Ingresa tu contrasena" autocomplete="current-password" required>
                        </div>
                        <div class="form-options">
                            <label class="form-check" for="remember">
                                <input class="form-check-input" type="checkbox" id="remember" name="remember" value="1">
                                <span class="form-check-label">Recordarme</span>
                            </label>
                            <a href="<?php echo base_url('auth/forgot_password'); ?>">Olvide mi contrasena</a>
                        </div>
                        <button type="submit" class="auth-button" data-submit-button data-loading-label="<?php echo html_escape($view['loading']); ?>"><?php echo html_escape($view['submit']); ?></button>
                    </form>

                    <div class="auth-links">
                        <span>No tienes cuenta? <a href="<?php echo base_url('auth/register'); ?>">Crear cuenta</a></span>
                    </div>
                <?php elseif ($auth_variant === 'register'): ?>
                    <form class="auth-form" id="registerForm" action="<?php echo html_escape($view['endpoint']); ?>" method="post" data-ajax-form="true">
                        <input type="hidden" name="<?php echo $this->security->get_csrf_token_name(); ?>" value="<?php echo $this->security->get_csrf_hash(); ?>">
                        <div class="form-grid">
                            <div class="form-group">
                                <label class="form-label" for="first_name">Nombre</label>
                                <input type="text" class="form-control" id="first_name" name="first_name" placeholder="Nombre" value="<?php echo html_escape(set_value('first_name')); ?>" autocomplete="given-name" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="last_name">Apellido</label>
                                <input type="text" class="form-control" id="last_name" name="last_name" placeholder="Apellido" value="<?php echo html_escape(set_value('last_name')); ?>" autocomplete="family-name" required>
                            </div>
                        </div>
                        <?php if (($identity_column ?? 'email') !== 'email'): ?>
                            <div class="form-group">
                                <label class="form-label" for="identity">Usuario</label>
                                <input type="text" class="form-control" id="identity" name="identity" placeholder="usuario" value="<?php echo html_escape(set_value('identity')); ?>" autocomplete="username" required>
                            </div>
                        <?php endif; ?>
                        <div class="form-group">
                            <label class="form-label" for="email">Correo electronico</label>
                            <input type="email" class="form-control" id="email" name="email" placeholder="tu@correo.com" value="<?php echo html_escape(set_value('email')); ?>" autocomplete="email" required>
                        </div>
                        <div class="form-grid">
                            <div class="form-group">
                                <label class="form-label" for="company">Organizacion <span class="form-meta">opcional</span></label>
                                <input type="text" class="form-control" id="company" name="company" placeholder="Nombre de organizacion" value="<?php echo html_escape(set_value('company')); ?>" autocomplete="organization">
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="phone">Telefono <span class="form-meta">opcional</span></label>
                                <input type="text" class="form-control" id="phone" name="phone" placeholder="Telefono" value="<?php echo html_escape(set_value('phone')); ?>" autocomplete="tel">
                            </div>
                        </div>
                        <div class="form-grid">
                            <div class="form-group">
                                <label class="form-label" for="password">Contrasena</label>
                                <input type="password" class="form-control" id="password" name="password" placeholder="Minimo 8 caracteres" autocomplete="new-password" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="password_confirm">Confirmar contrasena</label>
                                <input type="password" class="form-control" id="password_confirm" name="password_confirm" placeholder="Repite la contrasena" autocomplete="new-password" required>
                            </div>
                        </div>
                        <button type="submit" class="auth-button" data-submit-button data-loading-label="<?php echo html_escape($view['loading']); ?>"><?php echo html_escape($view['submit']); ?></button>
                    </form>

                    <div class="auth-links">
                        <span>Ya tienes cuenta? <a href="<?php echo base_url('auth/login'); ?>">Iniciar sesion</a></span>
                    </div>
                <?php elseif ($auth_variant === 'forgot_password'): ?>
                    <form class="auth-form" id="forgotForm" action="<?php echo html_escape($view['endpoint']); ?>" method="post" data-ajax-form="true">
                        <input type="hidden" name="<?php echo $this->security->get_csrf_token_name(); ?>" value="<?php echo $this->security->get_csrf_hash(); ?>">
                        <div class="form-group">
                            <label class="form-label" for="identity"><?php echo html_escape($identityLabel); ?></label>
                            <input type="<?php echo html_escape($identityType); ?>" class="form-control" id="identity" name="identity" placeholder="tu@correo.com" value="<?php echo html_escape(set_value('identity')); ?>" autocomplete="email" required>
                        </div>
                        <button type="submit" class="auth-button" data-submit-button data-loading-label="<?php echo html_escape($view['loading']); ?>"><?php echo html_escape($view['submit']); ?></button>
                    </form>

                    <div class="auth-links">
                        <a href="<?php echo base_url('auth/login'); ?>">Volver a iniciar sesion</a>
                    </div>
                <?php elseif ($auth_variant === 'reset_password'): ?>
                    <form class="auth-form" id="resetPasswordForm" action="<?php echo html_escape($view['endpoint']); ?>" method="post" data-ajax-form="false">
                        <input type="hidden" name="<?php echo $this->security->get_csrf_token_name(); ?>" value="<?php echo $this->security->get_csrf_hash(); ?>">
                        <div class="form-grid">
                            <div class="form-group">
                                <label class="form-label" for="new_password"><?php echo sprintf(lang('reset_password_new_password_label'), $min_password_length); ?></label>
                                <input type="password" class="form-control" id="new_password" name="new" placeholder="Nueva contrasena" autocomplete="new-password" pattern="<?php echo html_escape('^.{' . $min_password_length . '}.*$'); ?>" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="new_password_confirm"><?php echo lang('reset_password_new_password_confirm_label'); ?></label>
                                <input type="password" class="form-control" id="new_password_confirm" name="new_confirm" placeholder="Repite la contrasena" autocomplete="new-password" pattern="<?php echo html_escape('^.{' . $min_password_length . '}.*$'); ?>" required>
                            </div>
                        </div>
                        <input type="hidden" name="user_id" value="<?php echo html_escape($user_id['value'] ?? ''); ?>">
                        <?php if (isset($csrf) && is_array($csrf)): ?>
                            <?php foreach ($csrf as $csrfName => $csrfValue): ?>
                                <input type="hidden" name="<?php echo html_escape($csrfName); ?>" value="<?php echo html_escape($csrfValue); ?>">
                            <?php endforeach; ?>
                        <?php endif; ?>
                        <button type="submit" class="auth-button" data-submit-button><?php echo html_escape($view['submit']); ?></button>
                    </form>

                    <div class="auth-links">
                        <a href="<?php echo base_url('auth/login'); ?>">Volver a iniciar sesion</a>
                    </div>
                <?php endif; ?>

            </section>
        </section>
    </main>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.js"></script>
    <script>
        function stripHtml(input) {
            return $('<div>').html(input || '').text().trim();
        }

        $(document).ready(function() {
            var form = $('#<?php echo $view['form_id']; ?>');

            if (form.attr('data-ajax-form') !== 'true') {
                return;
            }

            form.on('submit', function(e) {
                e.preventDefault();

                var currentForm = $(this);
                var submitButton = currentForm.find('[data-submit-button]').first();
                var originalLabel = submitButton.text();
                var loadingLabel = submitButton.data('loading-label') || 'Procesando...';

                submitButton.prop('disabled', true).text(loadingLabel);

                $.ajax({
                    url: currentForm.attr('action'),
                    type: 'POST',
                    data: currentForm.serialize(),
                    dataType: 'json',
                    complete: function() {
                        submitButton.prop('disabled', false).text(originalLabel);
                    },
                    success: function(response) {
                        var responseMessage = stripHtml(response.message) || 'Proceso completado correctamente.';

                        if (response.success) {
                            Swal.fire({
                                icon: 'success',
                                title: '<?php echo html_escape($view['success_title']); ?>',
                                text: responseMessage,
                                confirmButtonColor: '#4f46e5'
                            }).then(function() {
                                if (response.redirect) {
                                    window.location.href = response.redirect;
                                }
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'No fue posible continuar',
                                text: responseMessage || 'Revisa la informacion e intentalo nuevamente.',
                                confirmButtonColor: '#4f46e5'
                            });
                        }
                    },
                    error: function() {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error inesperado',
                            text: 'Ocurrio un problema al procesar la solicitud. Intentalo otra vez.',
                            confirmButtonColor: '#4f46e5'
                        });
                    }
                });
            });
        });
    </script>
</body>
</html>
