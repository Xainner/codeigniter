<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo html_escape(($title ?? 'Acceso') . ' · ' . ($this->app_settings['site_name'] ?? 'CodeIgniter')); ?></title>
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
        $brandColor = (string) ($this->app_settings['brand_color'] ?? '#4f46e5');
        $brandColor = preg_match('/^#[0-9a-fA-F]{6}$/D', $brandColor) ? $brandColor : '#4f46e5';
        $siteName = (string) ($this->app_settings['site_name'] ?? 'CodeIgniter');
    ?>
    <link rel="stylesheet" href="<?php echo html_escape(base_url('assets/auth.css')); ?>">
</head>
<body class="auth-page auth-<?php echo html_escape($auth_variant); ?>" style="--primary: <?php echo html_escape($brandColor); ?>">
    <main class="auth-shell">
        <section class="auth-card" aria-labelledby="auth-title">
            <section class="auth-panel">
                <header class="auth-header">
                    <div class="auth-brand">
                        <?php if (!empty($this->app_settings['brand_logo'])): ?><img src="<?php echo html_escape(site_url('brand/logo')); ?>" alt="" width="36" height="36"><?php endif; ?>
                        <span><?php echo html_escape($siteName); ?></span>
                    </div>
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
                            <?php if (!empty($this->app_settings['remember_users'])): ?><label class="form-check" for="remember">
                                <input class="form-check-input" type="checkbox" id="remember" name="remember" value="1">
                                <span class="form-check-label">Recordarme</span>
                            </label><?php endif; ?>
                            <a href="<?php echo base_url('auth/forgot_password'); ?>">Olvide mi contrasena</a>
                        </div>
                        <button type="submit" class="auth-button" data-submit-button data-loading-label="<?php echo html_escape($view['loading']); ?>"><?php echo html_escape($view['submit']); ?></button>
                    </form>

                    <?php if (!empty($this->app_settings['public_registration'])): ?><div class="auth-links">
                        <span>No tienes cuenta? <a href="<?php echo base_url('auth/register'); ?>">Crear cuenta</a></span>
                    </div><?php endif; ?>
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
                                <input type="password" class="form-control" id="password" name="password" placeholder="Mínimo <?php echo (int) ($this->app_settings['min_password_length'] ?? 12); ?> caracteres" minlength="<?php echo (int) ($this->app_settings['min_password_length'] ?? 12); ?>" autocomplete="new-password" required>
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

    <script src="<?php echo html_escape(base_url('assets/auth.js')); ?>" defer></script>
</body>
</html>
