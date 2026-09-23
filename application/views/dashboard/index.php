<?php
defined('BASEPATH') OR exit('No direct script access allowed');

$users = isset($users) && is_array($users) ? $users : [];
$groups = isset($groups) && is_array($groups) ? $groups : [];
$query = trim((string) ($query ?? ''));
$message = trim((string) ($message ?? ''));
$read = static function ($row, $key, $default = '') {
    if (is_array($row)) {
        return $row[$key] ?? $default;
    }
    return is_object($row) ? ($row->{$key} ?? $default) : $default;
};
$this->load->view('dashboard/_header', [
    'page_title' => 'Usuarios',
    'active_nav' => 'users',
    'settings' => $settings ?? [],
]);
?>
<div class="page-heading">
    <div>
        <p class="eyebrow">Control de acceso</p>
        <h1>Usuarios y grupos</h1>
        <p class="page-description">Administra las cuentas y los permisos de tu aplicación desde un solo lugar.</p>
    </div>
    <a class="button button-primary" href="<?php echo html_escape(site_url('auth/create_user')); ?>"><span aria-hidden="true">＋</span> Crear usuario</a>
</div>

<?php if ($message !== ''): ?>
<div class="notice" role="status"><?php echo html_escape($message); ?></div>
<?php endif; ?>

<section class="summary-grid" aria-label="Resumen">
    <div class="summary-card"><span>Usuarios en esta página</span><strong><?php echo count($users); ?></strong><small>Cuentas visibles con el filtro actual</small></div>
    <div class="summary-card"><span>Grupos disponibles</span><strong><?php echo count($groups); ?></strong><small>Roles para organizar el acceso</small></div>
</section>

<section class="panel" aria-labelledby="users-heading">
    <div class="panel-heading">
        <div><p class="eyebrow">Directorio</p><h2 id="users-heading">Usuarios</h2></div>
        <form class="search-form" action="<?php echo html_escape(site_url('auth')); ?>" method="get" role="search">
            <label class="sr-only" for="user-search">Buscar usuarios</label>
            <input id="user-search" name="q" type="search" value="<?php echo html_escape($query); ?>" placeholder="Buscar por nombre o correo" maxlength="254">
            <button class="button button-secondary" type="submit">Buscar</button>
        </form>
    </div>
    <?php if ($users === []): ?>
    <div class="empty-state">
        <span class="empty-icon" aria-hidden="true">⌕</span>
        <h3><?php echo $query === '' ? 'Todavía no hay usuarios' : 'No se encontraron usuarios'; ?></h3>
        <p><?php echo $query === '' ? 'Crea la primera cuenta para empezar.' : 'Prueba con otro nombre o correo.'; ?></p>
    </div>
    <?php else: ?>
    <div class="table-scroll">
        <table class="data-table">
            <thead><tr><th scope="col">Usuario</th><th scope="col">Grupos</th><th scope="col">Estado</th><th scope="col">Último acceso</th><th scope="col"><span class="sr-only">Acciones</span></th></tr></thead>
            <tbody>
            <?php foreach ($users as $user):
                $id = (int) $read($user, 'id', 0);
                $first_name = trim((string) $read($user, 'first_name'));
                $last_name = trim((string) $read($user, 'last_name'));
                $name = trim($first_name . ' ' . $last_name);
                $email = (string) $read($user, 'email');
                $active = (bool) $read($user, 'active', false);
                $user_groups = $read($user, 'groups', []);
                $user_groups = is_array($user_groups) ? $user_groups : [];
                $last_login = (int) $read($user, 'last_login', 0);
            ?>
            <tr>
                <td data-label="Usuario"><div class="person"><span class="person-avatar" aria-hidden="true"><?php echo html_escape(strtoupper(substr($name !== '' ? $name : $email, 0, 1))); ?></span><span><strong><?php echo html_escape($name !== '' ? $name : 'Sin nombre'); ?></strong><small><?php echo html_escape($email); ?></small></span></div></td>
                <td data-label="Grupos"><div class="tag-list"><?php if ($user_groups === []): ?><span class="muted">Sin grupos</span><?php else: ?><?php foreach ($user_groups as $user_group): ?><span class="tag"><?php echo html_escape((string) $read($user_group, 'name')); ?></span><?php endforeach; ?><?php endif; ?></div></td>
                <td data-label="Estado"><span class="status <?php echo $active ? 'status-active' : 'status-inactive'; ?>"><span aria-hidden="true">●</span><?php echo $active ? 'Activo' : 'Inactivo'; ?></span></td>
                <td data-label="Último acceso"><?php echo $last_login > 0 ? html_escape(date('d/m/Y H:i', $last_login)) : '<span class="muted">Nunca</span>'; ?></td>
                <td class="row-actions" data-label="Acciones">
                    <a class="text-link" href="<?php echo html_escape(site_url('auth/edit_user/' . $id)); ?>">Editar<span class="sr-only"> a <?php echo html_escape($name !== '' ? $name : $email); ?></span></a>
                    <form action="<?php echo html_escape(site_url('auth/' . ($active ? 'deactivate' : 'activate') . '/' . $id)); ?>" method="post" data-confirm="<?php echo html_escape(($active ? '¿Desactivar' : '¿Activar') . ' esta cuenta?'); ?>">
                        <input type="hidden" name="<?php echo html_escape((string) ($csrf_name ?? '')); ?>" value="<?php echo html_escape((string) ($csrf_hash ?? '')); ?>">
                        <button class="text-link <?php echo $active ? 'text-danger' : ''; ?>" type="submit"><?php echo $active ? 'Desactivar' : 'Activar'; ?><span class="sr-only"> a <?php echo html_escape($name !== '' ? $name : $email); ?></span></button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php if (!empty($pagination)): ?>
    <nav class="pagination" aria-label="Páginas de usuarios"><?php echo $pagination; // Generated by CI Pagination with server-controlled URL configuration. ?></nav>
    <?php endif; ?>
    <?php endif; ?>
</section>

<section class="panel" id="groups" aria-labelledby="groups-heading">
    <div class="panel-heading">
        <div><p class="eyebrow">Permisos</p><h2 id="groups-heading">Grupos</h2></div>
        <a class="button button-secondary" href="<?php echo html_escape(site_url('auth/create_group')); ?>">Crear grupo</a>
    </div>
    <?php if ($groups === []): ?>
    <div class="empty-state"><h3>No hay grupos</h3><p>Crea un grupo para organizar los permisos.</p></div>
    <?php else: ?>
    <div class="group-grid">
        <?php foreach ($groups as $group): $group_id = (int) $read($group, 'id', 0); ?>
        <article class="group-card">
            <span class="group-symbol" aria-hidden="true">◇</span>
            <h3><?php echo html_escape((string) $read($group, 'name')); ?></h3>
            <p><?php echo html_escape((string) $read($group, 'description')); ?></p>
            <a class="text-link" href="<?php echo html_escape(site_url('auth/edit_group/' . $group_id)); ?>">Editar grupo <span aria-hidden="true">→</span></a>
        </article>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</section>
<?php $this->load->view('dashboard/_footer'); ?>
