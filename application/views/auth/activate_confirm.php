<!doctype html>
<html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Activar cuenta</title><link rel="stylesheet" href="<?php echo html_escape(base_url('assets/auth.css')); ?>"></head>
<body class="auth-page"><main class="auth-shell"><section class="auth-card"><section class="auth-panel">
<h1>Activar cuenta</h1><p>Confirma que deseas activar esta cuenta.</p>
<form action="<?php echo html_escape(site_url('auth/activate/' . $id . '/' . rawurlencode($code))); ?>" method="post">
<input type="hidden" name="<?php echo html_escape($csrf_name); ?>" value="<?php echo html_escape($csrf_hash); ?>">
<button class="auth-button" type="submit">Activar cuenta</button>
</form>
</section></section></main></body></html>
