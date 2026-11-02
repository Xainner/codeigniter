<?php
defined('BASEPATH') OR exit('No direct script access allowed');

$keys = isset($keys) && is_array($keys) ? $keys : [];
$new_token = (string) ($new_token ?? '');
$hidden_fields = '<input type="hidden" name="' . html_escape((string) ($csrf_name ?? '')) . '" value="' . html_escape((string) ($csrf_hash ?? '')) . '">'
    . '<input type="hidden" name="' . html_escape((string) ($nonce_name ?? '')) . '" value="' . html_escape((string) ($nonce_hash ?? '')) . '">';
$this->load->view('dashboard/_header', [
    'page_title' => 'Claves de API',
    'active_nav' => 'api_keys',
    'settings' => $settings ?? [],
]);
?>
<div class="page-heading">
    <div>
        <p class="eyebrow">Integraciones</p>
        <h1>Claves de API</h1>
        <p class="page-description">Las aplicaciones envían la clave en la cabecera <code>X-API-Key</code>. Solo se guarda una huella de cada clave: si la pierdes, revócala y crea otra.</p>
    </div>
</div>

<?php $this->load->view('dashboard/_feedback', ['errors' => $errors ?? [], 'message' => $message ?? '']); ?>

<?php if ($new_token !== ''): ?>
<section class="panel new-token" aria-labelledby="new-token-heading">
    <div class="panel-heading"><div><p class="eyebrow">Nueva clave</p><h2 id="new-token-heading">Cópiala ahora</h2></div></div>
    <div class="token-box"><code data-api-token><?php echo html_escape($new_token); ?></code></div>
</section>
<?php endif; ?>

<section class="panel" aria-labelledby="create-key-heading">
    <div class="panel-heading">
        <div><p class="eyebrow">Crear</p><h2 id="create-key-heading">Nueva clave</h2></div>
        <form class="search-form" action="<?php echo html_escape(site_url('auth/api-keys/create')); ?>" method="post">
            <?php echo $hidden_fields; ?>
            <label class="sr-only" for="key-name">Nombre de la clave</label>
            <input id="key-name" name="name" type="text" maxlength="80" placeholder="Ej.: integración de facturación" required>
            <button class="button button-primary" type="submit">Crear clave</button>
        </form>
    </div>
</section>

<section class="panel" aria-labelledby="keys-heading">
    <div class="panel-heading"><div><p class="eyebrow">Registro</p><h2 id="keys-heading">Claves</h2></div></div>
    <?php if ($keys === []): ?>
    <div class="empty-state"><span class="empty-icon" aria-hidden="true">⚿</span><h3>Todavía no hay claves</h3><p>Crea una para conectar otra aplicación con la API.</p></div>
    <?php else: ?>
    <div class="table-scroll">
        <table class="data-table">
            <thead><tr><th scope="col">Nombre</th><th scope="col">Propietario</th><th scope="col">Creada</th><th scope="col">Último uso</th><th scope="col">Estado</th><th scope="col"><span class="sr-only">Acciones</span></th></tr></thead>
            <tbody>
            <?php foreach ($keys as $key): $revoked = $key->revoked_at !== NULL; ?>
            <tr>
                <td data-label="Nombre"><strong><?php echo html_escape($key->name); ?></strong> <span class="muted">…<?php echo html_escape($key->token_hint); ?></span></td>
                <td data-label="Propietario"><?php echo html_escape($key->owner_email); ?></td>
                <td data-label="Creada"><?php echo html_escape(date('d/m/Y H:i', (int) $key->created_at)); ?></td>
                <td data-label="Último uso"><?php echo $key->last_used_at ? html_escape(date('d/m/Y H:i', (int) $key->last_used_at)) : '<span class="muted">Nunca</span>'; ?></td>
                <td data-label="Estado"><span class="status <?php echo $revoked ? 'status-inactive' : 'status-active'; ?>"><span aria-hidden="true">●</span><?php echo $revoked ? 'Revocada' : 'Activa'; ?></span></td>
                <td class="row-actions" data-label="Acciones">
                    <?php if (!$revoked): ?>
                    <form action="<?php echo html_escape(site_url('auth/api-keys/revoke/' . (int) $key->id)); ?>" method="post" data-confirm="¿Revocar esta clave? Las aplicaciones que la usan dejarán de funcionar.">
                        <?php echo $hidden_fields; ?>
                        <button class="text-link text-danger" type="submit">Revocar<span class="sr-only"> <?php echo html_escape($key->name); ?></span></button>
                    </form>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</section>
<?php $this->load->view('dashboard/_footer'); ?>
