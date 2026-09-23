<?php
defined('BASEPATH') OR exit('No direct script access allowed');

$mode = ($mode ?? 'create') === 'edit' ? 'edit' : 'create';
$editing = $mode === 'edit';
$user = $user ?? null;
$groups = isset($groups) && is_array($groups) ? $groups : [];
$selected_group_ids = isset($selected_group_ids) && is_array($selected_group_ids) ? array_map('intval', $selected_group_ids) : [];
$read = static function ($row, $key, $default = '') {
    if (is_array($row)) {
        return $row[$key] ?? $default;
    }
    return is_object($row) ? ($row->{$key} ?? $default) : $default;
};
$user_id = (int) $read($user, 'id', 0);
$action = $editing ? site_url('auth/edit_user/' . $user_id) : site_url('auth/create_user');
$this->load->view('dashboard/_header', [
    'page_title' => $editing ? 'Editar usuario' : 'Crear usuario',
    'active_nav' => 'users',
    'settings' => $settings ?? [],
]);
?>
<div class="page-heading page-heading-narrow">
    <div><a class="back-link" href="<?php echo html_escape(site_url('auth')); ?>"><span aria-hidden="true">←</span> Volver a usuarios</a><p class="eyebrow">Control de acceso</p><h1><?php echo $editing ? 'Editar usuario' : 'Crear usuario'; ?></h1><p class="page-description"><?php echo $editing ? 'Actualiza los datos y grupos de esta cuenta.' : 'Crea una cuenta y asígnale los grupos adecuados.'; ?></p></div>
</div>
<div class="form-container">
    <?php $this->load->view('dashboard/_feedback', ['errors' => $errors ?? [], 'message' => $message ?? '']); ?>
    <form class="stack-form" action="<?php echo html_escape($action); ?>" method="post">
        <input type="hidden" name="<?php echo html_escape((string) ($csrf_name ?? '')); ?>" value="<?php echo html_escape((string) ($csrf_hash ?? '')); ?>">
        <?php if ($editing): ?><input type="hidden" name="id" value="<?php echo $user_id; ?>"><?php endif; ?>
        <?php if ($editing && !empty($nonce_name)): ?><input type="hidden" name="<?php echo html_escape((string) $nonce_name); ?>" value="<?php echo html_escape((string) ($nonce_hash ?? '')); ?>"><?php endif; ?>
        <section class="form-section" aria-labelledby="user-profile-heading">
            <div class="section-heading"><span class="section-number">01</span><div><h2 id="user-profile-heading">Perfil</h2><p>Información básica para identificar a la persona.</p></div></div>
            <div class="form-grid">
                <div class="field"><label for="first_name">Nombre <span aria-hidden="true">*</span></label><input id="first_name" name="first_name" type="text" value="<?php echo html_escape((string) $read($user, 'first_name')); ?>" maxlength="50" autocomplete="given-name" required></div>
                <div class="field"><label for="last_name">Apellido <span aria-hidden="true">*</span></label><input id="last_name" name="last_name" type="text" value="<?php echo html_escape((string) $read($user, 'last_name')); ?>" maxlength="50" autocomplete="family-name" required></div>
                <div class="field"><label for="email">Correo electrónico <span aria-hidden="true">*</span></label><input id="email" name="email" type="email" value="<?php echo html_escape((string) $read($user, 'email')); ?>" maxlength="254" autocomplete="email" required></div>
                <div class="field"><label for="phone">Teléfono</label><input id="phone" name="phone" type="tel" value="<?php echo html_escape((string) $read($user, 'phone')); ?>" maxlength="20" autocomplete="tel"></div>
                <div class="field field-full"><label for="company">Empresa</label><input id="company" name="company" type="text" value="<?php echo html_escape((string) $read($user, 'company')); ?>" maxlength="100" autocomplete="organization"></div>
            </div>
        </section>
        <section class="form-section" aria-labelledby="user-password-heading">
            <div class="section-heading"><span class="section-number">02</span><div><h2 id="user-password-heading">Contraseña</h2><p><?php echo $editing ? 'Déjala en blanco si no deseas cambiarla.' : 'La persona podrá cambiarla después de iniciar sesión.'; ?></p></div></div>
            <div class="form-grid">
                <div class="field"><label for="password">Contraseña<?php if (!$editing): ?> <span aria-hidden="true">*</span><?php endif; ?></label><input id="password" name="password" type="password" autocomplete="new-password" <?php echo $editing ? '' : 'required'; ?>></div>
                <div class="field"><label for="password_confirm">Confirmar contraseña<?php if (!$editing): ?> <span aria-hidden="true">*</span><?php endif; ?></label><input id="password_confirm" name="password_confirm" type="password" autocomplete="new-password" <?php echo $editing ? '' : 'required'; ?>></div>
            </div>
        </section>
        <section class="form-section" aria-labelledby="user-groups-heading">
            <div class="section-heading"><span class="section-number">03</span><div><h2 id="user-groups-heading">Grupos</h2><p>Elige los grupos que determinan el acceso de esta cuenta.</p></div></div>
            <?php if ($groups === []): ?><p class="muted">Aún no hay grupos disponibles.</p><?php else: ?>
            <div class="choice-grid">
            <?php foreach ($groups as $group):
                $group_id = (int) $read($group, 'id', 0);
                $group_name = (string) $read($group, 'name');
            ?>
                <label class="choice-card"><input type="checkbox" name="groups[]" value="<?php echo $group_id; ?>" <?php echo in_array($group_id, $selected_group_ids, true) ? 'checked' : ''; ?>><span><strong><?php echo html_escape($group_name); ?></strong><small><?php echo html_escape((string) $read($group, 'description')); ?></small></span></label>
            <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </section>
        <div class="form-actions"><a class="button button-secondary" href="<?php echo html_escape(site_url('auth')); ?>">Cancelar</a><button class="button button-primary" type="submit"><?php echo $editing ? 'Guardar cambios' : 'Crear usuario'; ?></button></div>
    </form>
</div>
<?php $this->load->view('dashboard/_footer'); ?>
