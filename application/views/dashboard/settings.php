<?php
defined('BASEPATH') OR exit('No direct script access allowed');

$settings = isset($settings) && is_array($settings) ? $settings : [];
$value = static function ($key, $default = '') use ($settings) {
    return (string) ($settings[$key] ?? $default);
};
$enabled = static function ($key) use ($settings) {
    return in_array($settings[$key] ?? false, [true, 1, '1'], true);
};
$brand_color = $value('brand_color', '#4f46e5');
$brand_color = preg_match('/^#[0-9a-fA-F]{6}$/', $brand_color) ? $brand_color : '#4f46e5';
$this->load->view('dashboard/_header', [
    'page_title' => 'Configuración',
    'active_nav' => 'settings',
    'settings' => $settings,
]);
?>
<div class="page-heading page-heading-narrow">
    <div><p class="eyebrow">Preferencias</p><h1>Configuración</h1><p class="page-description">Ajusta el acceso, el correo y la identidad visual de tu aplicación.</p></div>
</div>
<div class="form-container form-container-wide">
    <?php $this->load->view('dashboard/_feedback', ['errors' => $errors ?? [], 'message' => $message ?? '']); ?>
    <form class="stack-form" action="<?php echo html_escape(site_url('auth/settings')); ?>" method="post" enctype="multipart/form-data">
        <input type="hidden" name="<?php echo html_escape((string) ($csrf_name ?? '')); ?>" value="<?php echo html_escape((string) ($csrf_hash ?? '')); ?>">
        <section class="form-section" aria-labelledby="access-heading">
            <div class="section-heading"><span class="section-number">01</span><div><h2 id="access-heading">Acceso y registro</h2><p>Controla cómo se crean las cuentas y se inician las sesiones.</p></div></div>
            <div class="switch-list">
                <label class="switch-row"><span><strong>Registro público</strong><small>Permite que los visitantes creen una cuenta.</small></span><span class="switch-control"><input type="hidden" name="public_registration" value="0"><input type="checkbox" name="public_registration" value="1" <?php echo $enabled('public_registration') ? 'checked' : ''; ?>><span class="switch-track" aria-hidden="true"></span></span></label>
                <label class="switch-row"><span><strong>Activación por correo</strong><small>Solicita confirmar el correo antes de iniciar sesión.</small></span><span class="switch-control"><input type="hidden" name="email_activation" value="0"><input type="checkbox" name="email_activation" value="1" <?php echo $enabled('email_activation') ? 'checked' : ''; ?>><span class="switch-track" aria-hidden="true"></span></span></label>
                <label class="switch-row"><span><strong>Recordar sesión</strong><small>Permite mantener la sesión iniciada en el dispositivo.</small></span><span class="switch-control"><input type="hidden" name="remember_users" value="0"><input type="checkbox" name="remember_users" value="1" <?php echo $enabled('remember_users') ? 'checked' : ''; ?>><span class="switch-track" aria-hidden="true"></span></span></label>
            </div>
            <div class="form-grid form-grid-spaced">
                <div class="field"><label for="min_password_length">Longitud mínima de contraseña</label><input id="min_password_length" name="min_password_length" type="number" value="<?php echo html_escape($value('min_password_length', '12')); ?>" min="12" max="128" step="1" inputmode="numeric" required><small>Entre 12 y 128 caracteres.</small></div>
                <div class="field"><label for="maximum_login_attempts">Intentos de acceso permitidos</label><input id="maximum_login_attempts" name="maximum_login_attempts" type="number" value="<?php echo html_escape($value('maximum_login_attempts', '5')); ?>" min="3" max="10" step="1" inputmode="numeric" required><small>Antes de bloquear temporalmente el acceso.</small></div>
                <div class="field"><label for="lockout_time">Bloqueo temporal (segundos)</label><input id="lockout_time" name="lockout_time" type="number" value="<?php echo html_escape($value('lockout_time', '900')); ?>" min="60" max="3600" step="1" inputmode="numeric" required></div>
            </div>
        </section>
        <section class="form-section" aria-labelledby="smtp-heading">
            <div class="section-heading"><span class="section-number">02</span><div><h2 id="smtp-heading">Correo saliente</h2><p>Configura SMTP para activar cuentas y recuperar contraseñas.</p></div><?php if ($enabled('smtp_verified')): ?><span class="status status-active"><span aria-hidden="true">●</span>Envío probado</span><?php else: ?><span class="status status-inactive"><span aria-hidden="true">●</span>Pendiente de prueba</span><?php endif; ?></div>
            <div class="form-grid">
                <div class="field"><label for="smtp_host">Servidor SMTP</label><input id="smtp_host" name="smtp_host" type="text" value="<?php echo html_escape($value('smtp_host')); ?>" maxlength="253" autocomplete="off" placeholder="smtp.ejemplo.com"></div>
                <div class="field"><label for="smtp_port">Puerto</label><input id="smtp_port" name="smtp_port" type="number" value="<?php echo html_escape($value('smtp_port', '587')); ?>" min="1" max="65535" step="1" inputmode="numeric"></div>
                <div class="field"><label for="smtp_crypto">Seguridad</label><select id="smtp_crypto" name="smtp_crypto"><option value="tls" <?php echo $value('smtp_crypto', 'tls') === 'tls' ? 'selected' : ''; ?>>STARTTLS</option><option value="ssl" <?php echo $value('smtp_crypto') === 'ssl' ? 'selected' : ''; ?>>SSL/TLS</option></select></div>
                <div class="field"><label for="smtp_user">Usuario SMTP</label><input id="smtp_user" name="smtp_user" type="text" value="<?php echo html_escape($value('smtp_user')); ?>" maxlength="255" autocomplete="off"></div>
                <div class="field field-full"><label for="smtp_password">Contraseña SMTP</label><input id="smtp_password" name="smtp_password" type="password" value="" autocomplete="new-password"><small>Déjala en blanco para conservar la contraseña guardada. El valor actual nunca se muestra.</small></div>
                <div class="field"><label for="smtp_from_email">Correo remitente</label><input id="smtp_from_email" name="smtp_from_email" type="email" value="<?php echo html_escape($value('smtp_from_email')); ?>" maxlength="254" autocomplete="off"></div>
                <div class="field"><label for="smtp_from_name">Nombre remitente</label><input id="smtp_from_name" name="smtp_from_name" type="text" value="<?php echo html_escape($value('smtp_from_name')); ?>" maxlength="80"></div>
            </div>
        </section>
        <section class="form-section" aria-labelledby="branding-heading">
            <div class="section-heading"><span class="section-number">03</span><div><h2 id="branding-heading">Identidad visual</h2><p>Personaliza el nombre y los detalles que verá tu equipo.</p></div></div>
            <div class="form-grid">
                <div class="field"><label for="site_name">Nombre de la aplicación</label><input id="site_name" name="site_name" type="text" value="<?php echo html_escape($value('site_name', 'CodeIgniter')); ?>" maxlength="80" required></div>
                <div class="field"><label for="brand_color">Color principal</label><div class="color-field"><input id="brand_color" name="brand_color" type="color" value="<?php echo html_escape($brand_color); ?>"><output for="brand_color" data-color-output><?php echo html_escape(strtoupper($brand_color)); ?></output></div></div>
                <div class="field field-full"><label for="brand_logo_file">Logo</label><input id="brand_logo_file" name="brand_logo_file" type="file" accept="image/jpeg,image/png,image/webp"><small>JPEG, PNG o WebP. Máximo 1 MB y 2048 × 2048 píxeles.</small></div>
            </div>
        </section>
        <div class="form-actions"><a class="button button-secondary" href="<?php echo html_escape(site_url('auth')); ?>">Cancelar</a><button class="button button-primary" type="submit">Guardar configuración</button></div>
    </form>
    <section class="test-panel" aria-labelledby="test-email-heading">
        <div><h2 id="test-email-heading">Probar correo</h2><p>Guarda los ajustes y envía un mensaje de prueba a tu correo administrador.</p></div>
        <form action="<?php echo html_escape(site_url('auth/settings/test-email')); ?>" method="post" class="test-email-form">
            <input type="hidden" name="<?php echo html_escape((string) ($csrf_name ?? '')); ?>" value="<?php echo html_escape((string) ($csrf_hash ?? '')); ?>">
            <button class="button button-secondary" type="submit">Enviar prueba</button>
        </form>
    </section>
</div>
<?php $this->load->view('dashboard/_footer'); ?>
