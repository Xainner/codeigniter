<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Instalación inicial</title>
    <style>body{font:16px system-ui,sans-serif;background:#f4f6fb;color:#1f2937;margin:0;padding:2rem}main{max-width:32rem;margin:auto;background:#fff;padding:2rem;border-radius:1rem;box-shadow:0 1rem 3rem #18213a18}label{display:block;margin-top:1rem;font-weight:600}input{box-sizing:border-box;width:100%;padding:.7rem;margin-top:.3rem;border:1px solid #aab2c0;border-radius:.4rem}button{margin-top:1.5rem;padding:.8rem 1.2rem;background:#4f46e5;color:#fff;border:0;border-radius:.4rem;font:inherit}.error{color:#9f1239}</style>
</head>
<body><main>
    <h1>Crear administrador</h1>
    <p>Introduce la clave de instalación definida en el servidor. Esta página se cerrará después de crear la cuenta.</p>
    <?php if ($error): ?><p class="error" role="alert"><?php echo html_escape($error); ?></p><?php endif; ?>
    <form method="post" action="<?php echo site_url('auth/setup'); ?>">
        <input type="hidden" name="<?php echo html_escape($csrf_name); ?>" value="<?php echo html_escape($csrf_hash); ?>">
        <label>Clave de instalación<input type="password" name="setup_token" required autocomplete="off"></label>
        <label>Nombre<input name="first_name" maxlength="50" required autocomplete="given-name"></label>
        <label>Apellido<input name="last_name" maxlength="50" required autocomplete="family-name"></label>
        <label>Correo electrónico<input type="email" name="email" maxlength="254" required autocomplete="email"></label>
        <label>Contraseña<input type="password" name="password" minlength="12" required autocomplete="new-password"></label>
        <label>Confirmar contraseña<input type="password" name="password_confirm" minlength="12" required autocomplete="new-password"></label>
        <button type="submit">Crear administrador</button>
    </form>
</main></body></html>
