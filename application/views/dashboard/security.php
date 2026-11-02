<?php
defined('BASEPATH') OR exit('No direct script access allowed');

$status = $status ?? null;
$recovery_codes = isset($recovery_codes) && is_array($recovery_codes) ? $recovery_codes : [];
$hidden_fields = '<input type="hidden" name="' . html_escape((string) ($csrf_name ?? '')) . '" value="' . html_escape((string) ($csrf_hash ?? '')) . '">'
    . '<input type="hidden" name="' . html_escape((string) ($nonce_name ?? '')) . '" value="' . html_escape((string) ($nonce_hash ?? '')) . '">';
$this->load->view('dashboard/_header', [
    'page_title' => 'Seguridad',
    'active_nav' => 'security',
    'settings' => $settings ?? [],
    'is_admin' => !empty($is_admin),
]);
?>
<div class="page-heading page-heading-narrow">
    <div><p class="eyebrow">Cuenta</p><h1>Verificación en dos pasos</h1><p class="page-description">Además de la contraseña, pide un código de tu teléfono cada vez que inicias sesión.</p></div>
</div>
<div class="form-container">
    <?php $this->load->view('dashboard/_feedback', ['errors' => $errors ?? [], 'message' => $message ?? '']); ?>

    <?php if ($recovery_codes !== []): ?>
    <section class="stack-form recovery-panel" aria-labelledby="recovery-heading">
        <div class="form-section">
            <div class="section-heading"><span class="section-number">!</span><div><h2 id="recovery-heading">Códigos de recuperación</h2><p>Guárdalos en un lugar seguro: no volverán a mostrarse. Cada código sirve una sola vez si pierdes tu teléfono.</p></div></div>
            <ol class="recovery-codes">
                <?php foreach ($recovery_codes as $code): ?><li><code><?php echo html_escape($code); ?></code></li><?php endforeach; ?>
            </ol>
        </div>
    </section>
    <?php endif; ?>

    <?php if ($status): ?>
    <form class="stack-form" action="<?php echo html_escape(site_url('auth/security/recovery-codes')); ?>" method="post">
        <?php echo $hidden_fields; ?>
        <section class="form-section" aria-labelledby="mfa-status-heading">
            <div class="section-heading"><span class="section-number">01</span><div><h2 id="mfa-status-heading">Estado</h2><p>Activada el <?php echo html_escape(date('d/m/Y', (int) $status->enabled_at)); ?>. Quedan <?php echo (int) ($remaining_codes ?? 0); ?> códigos de recuperación sin usar.</p></div><span class="status status-active"><span aria-hidden="true">●</span>Activada</span></div>
        </section>
        <section class="form-section" aria-labelledby="mfa-codes-heading">
            <div class="section-heading"><span class="section-number">02</span><div><h2 id="mfa-codes-heading">Códigos de recuperación</h2><p>Genera un juego nuevo si los perdiste o ya usaste varios. Los anteriores dejarán de funcionar.</p></div></div>
            <div class="form-grid">
                <div class="field"><label for="codes_password">Contraseña actual</label><input id="codes_password" name="current_password" type="password" autocomplete="current-password" required></div>
            </div>
        </section>
        <div class="form-actions"><button class="button button-secondary" type="submit">Generar códigos nuevos</button></div>
    </form>
    <form class="stack-form" action="<?php echo html_escape(site_url('auth/security/disable')); ?>" method="post" data-confirm="¿Desactivar la verificación en dos pasos?">
        <?php echo $hidden_fields; ?>
        <section class="form-section" aria-labelledby="mfa-disable-heading">
            <div class="section-heading"><span class="section-number">03</span><div><h2 id="mfa-disable-heading">Desactivar</h2><p>Tu cuenta volverá a depender solo de la contraseña.</p></div></div>
            <div class="form-grid">
                <div class="field"><label for="disable_code">Código de la aplicación o de recuperación</label><input id="disable_code" name="code" type="text" autocomplete="one-time-code" maxlength="16" required></div>
                <div class="field"><label for="disable_password">Contraseña actual</label><input id="disable_password" name="current_password" type="password" autocomplete="current-password" required></div>
            </div>
        </section>
        <div class="form-actions"><button class="button button-secondary text-danger" type="submit">Desactivar</button></div>
    </form>
    <?php else: ?>
    <form class="stack-form" action="<?php echo html_escape(site_url('auth/security/enable')); ?>" method="post">
        <?php echo $hidden_fields; ?>
        <section class="form-section" aria-labelledby="mfa-scan-heading">
            <div class="section-heading"><span class="section-number">01</span><div><h2 id="mfa-scan-heading">Escanea el código</h2><p>Usa una aplicación de autenticación como Google Authenticator, Microsoft Authenticator, 1Password o Aegis.</p></div><span class="status status-inactive"><span aria-hidden="true">●</span>Desactivada</span></div>
            <div class="mfa-setup">
                <img class="qr-code" src="<?php echo html_escape((string) ($qr_code ?? '')); ?>" width="220" height="220" alt="Código QR para configurar la verificación en dos pasos">
                <div>
                    <p>¿No puedes escanearlo? Escribe esta clave en la aplicación:</p>
                    <code class="secret-key" data-mfa-secret><?php echo html_escape(trim(chunk_split((string) ($secret ?? ''), 4, ' '))); ?></code>
                </div>
            </div>
        </section>
        <section class="form-section" aria-labelledby="mfa-confirm-heading">
            <div class="section-heading"><span class="section-number">02</span><div><h2 id="mfa-confirm-heading">Confirma la configuración</h2><p>Escribe el código de seis dígitos que muestra la aplicación y tu contraseña actual.</p></div></div>
            <div class="form-grid">
                <div class="field"><label for="code">Código de la aplicación</label><input id="code" name="code" type="text" inputmode="numeric" autocomplete="one-time-code" maxlength="7" required></div>
                <div class="field"><label for="current_password">Contraseña actual</label><input id="current_password" name="current_password" type="password" autocomplete="current-password" required></div>
            </div>
        </section>
        <div class="form-actions"><button class="button button-primary" type="submit">Activar verificación</button></div>
    </form>
    <?php endif; ?>
</div>
<?php $this->load->view('dashboard/_footer'); ?>
