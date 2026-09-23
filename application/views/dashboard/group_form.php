<?php
defined('BASEPATH') OR exit('No direct script access allowed');

$mode = ($mode ?? 'create') === 'edit' ? 'edit' : 'create';
$editing = $mode === 'edit';
$group = $group ?? null;
$read = static function ($row, $key, $default = '') {
    if (is_array($row)) {
        return $row[$key] ?? $default;
    }
    return is_object($row) ? ($row->{$key} ?? $default) : $default;
};
$group_id = (int) $read($group, 'id', 0);
$action = $editing ? site_url('auth/edit_group/' . $group_id) : site_url('auth/create_group');
$this->load->view('dashboard/_header', [
    'page_title' => $editing ? 'Editar grupo' : 'Crear grupo',
    'active_nav' => 'groups',
    'settings' => $settings ?? [],
]);
?>
<div class="page-heading page-heading-narrow">
    <div><a class="back-link" href="<?php echo html_escape(site_url('auth') . '#groups'); ?>"><span aria-hidden="true">←</span> Volver a grupos</a><p class="eyebrow">Permisos</p><h1><?php echo $editing ? 'Editar grupo' : 'Crear grupo'; ?></h1><p class="page-description">Organiza las cuentas con un nombre y una descripción claros.</p></div>
</div>
<div class="form-container">
    <?php $this->load->view('dashboard/_feedback', ['errors' => $errors ?? [], 'message' => $message ?? '']); ?>
    <form class="stack-form" action="<?php echo html_escape($action); ?>" method="post">
        <input type="hidden" name="<?php echo html_escape((string) ($csrf_name ?? '')); ?>" value="<?php echo html_escape((string) ($csrf_hash ?? '')); ?>">
        <section class="form-section" aria-labelledby="group-details-heading">
            <div class="section-heading"><span class="section-number">01</span><div><h2 id="group-details-heading">Detalles del grupo</h2><p>El nombre debe ser único y reconocible.</p></div></div>
            <div class="form-grid">
                <div class="field field-full"><label for="group_name">Nombre <span aria-hidden="true">*</span></label><input id="group_name" name="group_name" type="text" value="<?php echo html_escape((string) $read($group, 'name')); ?>" maxlength="20" required></div>
                <div class="field field-full"><label for="description">Descripción</label><textarea id="description" name="<?php echo $editing ? 'group_description' : 'description'; ?>" rows="4" maxlength="100"><?php echo html_escape((string) $read($group, 'description')); ?></textarea><small>Máximo 100 caracteres.</small></div>
            </div>
        </section>
        <div class="form-actions"><a class="button button-secondary" href="<?php echo html_escape(site_url('auth') . '#groups'); ?>">Cancelar</a><button class="button button-primary" type="submit"><?php echo $editing ? 'Guardar cambios' : 'Crear grupo'; ?></button></div>
    </form>
</div>
<?php $this->load->view('dashboard/_footer'); ?>
