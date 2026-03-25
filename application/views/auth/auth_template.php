<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo html_escape($title ?? 'Acceso'); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&family=Space+Grotesk:wght@500;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <?php
        $companyData = (isset($company) && is_array($company) && (isset($company['name']) || isset($company['logo']) || isset($company['description'])))
            ? $company
            : [];
        $companyName = trim($companyData['name'] ?? 'Mi Empresa');
        $companyDescription = trim($companyData['description'] ?? '');
        $companyLogo = trim($companyData['logo'] ?? '');
        $messageText = trim(strip_tags((string) ($message ?? '')));
        $brandInitials = '';

        foreach (preg_split('/\s+/', $companyName) as $word) {
            if ($word !== '') {
                $brandInitials .= strtoupper(substr($word, 0, 1));
            }
            if (strlen($brandInitials) >= 2) {
                break;
            }
        }

        if ($brandInitials === '') {
            $brandInitials = 'ME';
        }

        $config = [
            'login' => [
                'eyebrow' => 'Acceso seguro',
                'heading' => 'Iniciar sesion',
                'description' => 'Accede a tu cuenta para continuar con tu trabajo.',
                'form_id' => 'loginForm',
                'endpoint' => base_url('auth/login'),
                'success_title' => 'Bienvenido',
            ],
            'register' => [
                'eyebrow' => 'Nuevo acceso',
                'heading' => 'Crear cuenta',
                'description' => 'Registra tus datos para comenzar a usar la plataforma.',
                'form_id' => 'registerForm',
                'endpoint' => base_url('auth/register'),
                'success_title' => 'Registro exitoso',
            ],
            'forgot_password' => [
                'eyebrow' => 'Recuperacion',
                'heading' => 'Recuperar contrasena',
                'description' => 'Ingresa tu correo y te enviaremos un enlace para restablecer el acceso.',
                'form_id' => 'forgotForm',
                'endpoint' => base_url('auth/forgot_password'),
                'success_title' => 'Enlace enviado',
            ],
        ];

        $view = $config[$auth_variant] ?? $config['login'];
    ?>
    <style>
        :root {
            --brand-navy: #002166;
            --brand-orange: #dd4814;
            --brand-gold: #f6a623;
            --brand-cream: #fff8f2;
            --ink: #163153;
            --muted: #66758a;
            --line: rgba(0, 33, 102, 0.12);
            --shadow: 0 30px 80px rgba(0, 33, 102, 0.22);
        }
        * { box-sizing: border-box; }
        body {
            min-height: 100vh;
            margin: 0;
            font-family: 'Poppins', sans-serif;
            color: var(--ink);
            background: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }
        .auth-shell { position: relative; z-index: 1; width: 100%; max-width: 1080px; }
        .auth-card {
            background: #ffffff;
            border: 1px solid rgba(0, 33, 102, 0.08);
            border-radius: 32px;
            box-shadow: 0 24px 70px rgba(0, 33, 102, 0.08);
            overflow: hidden;
            max-width: 640px;
            margin: 0 auto;
        }
        .auth-showcase {
            padding: 48px 42px 24px;
            background: #ffffff;
            color: var(--brand-navy);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 18px;
            text-align: center;
            border-bottom: 1px solid rgba(0, 33, 102, 0.08);
        }
        .brand-lockup,
        .showcase-copy { width: 100%; }
        .brand-logo {
            width: 96px;
            height: 96px;
            border-radius: 24px;
            background: #ffffff;
            border: 1px solid rgba(0, 33, 102, 0.08);
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            margin: 0 auto 20px;
            box-shadow: 0 16px 35px rgba(0, 33, 102, 0.08);
        }
        .brand-logo img { width: 100%; height: 100%; object-fit: cover; }
        .brand-fallback {
            font-family: 'Space Grotesk', sans-serif;
            font-size: 1.65rem;
            font-weight: 700;
            letter-spacing: 0.08em;
        }
        .brand-name {
            margin: 0 0 8px;
            font-family: 'Space Grotesk', sans-serif;
            font-size: clamp(2rem, 4vw, 2.7rem);
            line-height: 1;
            text-align: center;
        }
        .brand-description,
        .showcase-copy p {
            margin: 0;
            color: var(--muted);
            line-height: 1.7;
            text-align: center;
        }
        .showcase-copy span {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 8px 14px;
            border-radius: 999px;
            background: rgba(221, 72, 20, 0.08);
            font-size: 0.9rem;
            font-weight: 600;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            margin-bottom: 16px;
            color: var(--brand-orange);
        }
        .showcase-copy h2 {
            font-family: 'Space Grotesk', sans-serif;
            font-size: clamp(1.4rem, 3vw, 1.8rem);
            margin-bottom: 12px;
            color: var(--brand-navy);
        }
        .auth-panel {
            padding: 44px 40px;
            background: #ffffff;
        }
        .panel-eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            font-size: 0.82rem;
            font-weight: 700;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: var(--brand-orange);
            margin-bottom: 12px;
        }
        .panel-title {
            font-family: 'Space Grotesk', sans-serif;
            font-size: clamp(1.8rem, 4vw, 2.35rem);
            margin: 0 0 10px;
            color: var(--brand-navy);
        }
        .panel-description {
            color: var(--muted);
            margin-bottom: 28px;
            line-height: 1.65;
        }
        .auth-alert {
            border: 1px solid rgba(221, 72, 20, 0.2);
            background: rgba(221, 72, 20, 0.08);
            color: #8d2c10;
            border-radius: 18px;
            padding: 14px 16px;
            margin-bottom: 20px;
            font-size: 0.95rem;
        }
        .form-group { margin-bottom: 18px; }
        .form-label {
            font-size: 0.92rem;
            font-weight: 600;
            margin-bottom: 8px;
            color: var(--brand-navy);
        }
        .form-control {
            border-radius: 18px;
            border: 1px solid var(--line);
            padding: 14px 16px;
            min-height: 54px;
            color: var(--ink);
            box-shadow: none;
            transition: border-color 0.2s ease, box-shadow 0.2s ease, transform 0.2s ease;
        }
        .form-control:focus {
            border-color: rgba(221, 72, 20, 0.55);
            box-shadow: 0 0 0 4px rgba(221, 72, 20, 0.12);
            transform: translateY(-1px);
        }
        .form-check {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 22px;
            color: var(--muted);
        }
        .form-check-input {
            width: 18px;
            height: 18px;
            margin-top: 0;
            border-color: rgba(0, 33, 102, 0.26);
        }
        .form-check-input:checked {
            background-color: var(--brand-orange);
            border-color: var(--brand-orange);
        }
        .btn-auth {
            width: 100%;
            min-height: 56px;
            border: 0;
            border-radius: 18px;
            font-weight: 700;
            letter-spacing: 0.02em;
            color: #fff;
            background: linear-gradient(135deg, var(--brand-orange) 0%, #f26b28 56%, var(--brand-gold) 100%);
            box-shadow: 0 16px 34px rgba(221, 72, 20, 0.28);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .btn-auth:hover {
            transform: translateY(-2px);
            box-shadow: 0 20px 38px rgba(221, 72, 20, 0.32);
        }
        .auth-links {
            margin-top: 22px;
            display: grid;
            gap: 10px;
            text-align: center;
        }
        .auth-links a {
            color: var(--brand-navy);
            font-weight: 600;
            text-decoration: none;
        }
        .auth-links a:hover { color: var(--brand-orange); }
        .auth-links span { color: var(--muted); }
        @media (max-width: 991px) {
            .auth-showcase { padding: 36px 28px; }
            .auth-panel { padding: 34px 26px; }
        }
        @media (max-width: 575px) {
            body { padding: 16px; }
            .auth-showcase,
            .auth-panel { padding: 28px 20px; }
            .brand-logo { width: 72px; height: 72px; border-radius: 20px; }
        }
    </style>
</head>
<body>
    <main class="auth-shell">
        <section class="auth-card">
            <aside class="auth-showcase">
                <div class="brand-lockup">
                    <div class="brand-logo">
                        <?php if ($companyLogo !== ''): ?>
                            <img src="<?php echo base_url($companyLogo); ?>" alt="<?php echo html_escape($companyName); ?>">
                        <?php else: ?>
                            <span class="brand-fallback"><?php echo html_escape($brandInitials); ?></span>
                        <?php endif; ?>
                    </div>
                    <h1 class="brand-name"><?php echo html_escape($companyName); ?></h1>
                    <?php if ($companyDescription !== ''): ?>
                        <p class="brand-description"><?php echo html_escape($companyDescription); ?></p>
                    <?php endif; ?>
                </div>

                <div class="showcase-copy">
                    <span>Plataforma oficial</span>
                    <h2>Una experiencia de acceso unificada, clara y con presencia visual.</h2>
                    <p>Las vistas de autenticacion comparten una misma base, con el logo y nombre de la empresa como protagonistas.</p>
                </div>
            </aside>

            <section class="auth-panel">
                <div class="panel-eyebrow"><?php echo html_escape($view['eyebrow']); ?></div>
                <h2 class="panel-title"><?php echo html_escape($view['heading']); ?></h2>
                <p class="panel-description"><?php echo html_escape($view['description']); ?></p>

                <?php if ($messageText !== ''): ?>
                    <div class="auth-alert"><?php echo html_escape($messageText); ?></div>
                <?php endif; ?>

                <?php if ($auth_variant === 'login'): ?>
                    <form id="loginForm">
                        <div class="form-group">
                            <label class="form-label" for="identity">Correo o usuario</label>
                            <input type="text" class="form-control" id="identity" name="identity" placeholder="tu@correo.com" value="<?php echo html_escape(set_value('identity')); ?>" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="password">Contrasena</label>
                            <input type="password" class="form-control" id="password" name="password" placeholder="Ingresa tu contrasena" required>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="remember" name="remember" value="1">
                            <label class="form-check-label" for="remember">Recordarme en este equipo</label>
                        </div>
                        <button type="submit" class="btn btn-auth">Iniciar sesion</button>
                    </form>

                    <div class="auth-links">
                        <a href="<?php echo base_url('auth/forgot_password'); ?>">Olvidaste tu contrasena</a>
                        <span>No tienes cuenta? <a href="<?php echo base_url('auth/register'); ?>">Registrate</a></span>
                    </div>
                <?php elseif ($auth_variant === 'register'): ?>
                    <form id="registerForm">
                        <div class="row">
                            <div class="col-md-6 form-group">
                                <label class="form-label" for="first_name">Nombre</label>
                                <input type="text" class="form-control" id="first_name" name="first_name" placeholder="Tu nombre" value="<?php echo html_escape(set_value('first_name')); ?>" required>
                            </div>
                            <div class="col-md-6 form-group">
                                <label class="form-label" for="last_name">Apellido</label>
                                <input type="text" class="form-control" id="last_name" name="last_name" placeholder="Tu apellido" value="<?php echo html_escape(set_value('last_name')); ?>" required>
                            </div>
                        </div>
                        <?php if (($identity_column ?? 'email') !== 'email'): ?>
                            <div class="form-group">
                                <label class="form-label" for="identity">Usuario</label>
                                <input type="text" class="form-control" id="identity" name="identity" placeholder="Elige un usuario" value="<?php echo html_escape(set_value('identity')); ?>" required>
                            </div>
                        <?php endif; ?>
                        <div class="form-group">
                            <label class="form-label" for="email">Correo electronico</label>
                            <input type="email" class="form-control" id="email" name="email" placeholder="tu@correo.com" value="<?php echo html_escape(set_value('email')); ?>" required>
                        </div>
                        <div class="row">
                            <div class="col-md-6 form-group">
                                <label class="form-label" for="company">Empresa</label>
                                <input type="text" class="form-control" id="company" name="company" placeholder="Nombre de empresa" value="<?php echo html_escape(set_value('company')); ?>">
                            </div>
                            <div class="col-md-6 form-group">
                                <label class="form-label" for="phone">Telefono</label>
                                <input type="text" class="form-control" id="phone" name="phone" placeholder="Tu telefono" value="<?php echo html_escape(set_value('phone')); ?>">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 form-group">
                                <label class="form-label" for="password">Contrasena</label>
                                <input type="password" class="form-control" id="password" name="password" placeholder="Crea una contrasena" required>
                            </div>
                            <div class="col-md-6 form-group">
                                <label class="form-label" for="password_confirm">Confirmar contrasena</label>
                                <input type="password" class="form-control" id="password_confirm" name="password_confirm" placeholder="Repite la contrasena" required>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-auth">Crear cuenta</button>
                    </form>

                    <div class="auth-links">
                        <span>Ya tienes cuenta? <a href="<?php echo base_url('auth/login'); ?>">Inicia sesion</a></span>
                    </div>
                <?php elseif ($auth_variant === 'forgot_password'): ?>
                    <form id="forgotForm">
                        <div class="form-group">
                            <label class="form-label" for="identity"><?php echo (($type ?? 'email') !== 'email') ? 'Usuario o correo' : 'Correo electronico'; ?></label>
                            <input type="<?php echo (($type ?? 'email') !== 'email') ? 'text' : 'email'; ?>" class="form-control" id="identity" name="identity" placeholder="tu@correo.com" value="<?php echo html_escape(set_value('identity')); ?>" required>
                        </div>
                        <button type="submit" class="btn btn-auth">Enviar enlace</button>
                    </form>

                    <div class="auth-links">
                        <a href="<?php echo base_url('auth/login'); ?>">Volver al inicio de sesion</a>
                    </div>
                <?php endif; ?>
            </section>
        </section>
    </main>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.js"></script>
    <script>
        function stripHtml(input) {
            return $('<div>').html(input || '').text().trim();
        }

        $(document).ready(function() {
            $('#<?php echo $view['form_id']; ?>').on('submit', function(e) {
                e.preventDefault();

                $.ajax({
                    url: '<?php echo $view['endpoint']; ?>',
                    type: 'POST',
                    data: $(this).serialize(),
                    dataType: 'json',
                    success: function(response) {
                        var responseMessage = stripHtml(response.message) || 'Proceso completado correctamente.';

                        if (response.success) {
                            Swal.fire({
                                icon: 'success',
                                title: '<?php echo $view['success_title']; ?>',
                                text: responseMessage,
                                confirmButtonColor: '#dd4814'
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
                                confirmButtonColor: '#002166'
                            });
                        }
                    },
                    error: function() {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error inesperado',
                            text: 'Ocurrio un problema al procesar la solicitud. Intentalo otra vez.',
                            confirmButtonColor: '#002166'
                        });
                    }
                });
            });
        });
    </script>
</body>
</html>
