<?php
defined('BASEPATH') OR exit('No direct script access allowed');

$dashboard_settings = isset($settings) && is_array($settings) ? $settings : [];
$site_name = trim((string) ($dashboard_settings['site_name'] ?? 'CodeIgniter'));
$site_name = $site_name !== '' ? $site_name : 'CodeIgniter';
$brand_logo_url = !empty($dashboard_settings['brand_logo']) ? site_url('brand/logo') : null;
$brand_color = (string) ($dashboard_settings['brand_color'] ?? '#3d5afe');
$brand_color = preg_match('/^#[0-9a-fA-F]{6}$/', $brand_color) ? $brand_color : '#3d5afe';
$page_title = (string) ($page_title ?? 'Administración');
$active_nav = (string) ($active_nav ?? 'users');
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <title><?php echo html_escape($page_title . ' · ' . $site_name); ?></title>
    <link rel="stylesheet" href="<?php echo html_escape(base_url('assets/dashboard.css')); ?>">
    <script src="<?php echo html_escape(base_url('assets/dashboard.js')); ?>" defer></script>
</head>
<body style="--primary: <?php echo html_escape($brand_color); ?>; --primary-dark: color-mix(in srgb, <?php echo html_escape($brand_color); ?>, #000 17%);">
<a class="skip-link" href="#main-content">Saltar al contenido</a>
<div class="dashboard-shell">
    <aside class="sidebar" id="dashboard-sidebar" aria-label="Navegación principal">
        <a class="brand" href="<?php echo html_escape(site_url('auth')); ?>" aria-label="<?php echo html_escape($site_name); ?>, inicio">
            <span class="brand-mark" aria-hidden="true"><?php if ($brand_logo_url !== null): ?><img src="<?php echo html_escape($brand_logo_url); ?>" alt=""><?php else: ?>C<?php endif; ?></span>
            <span class="brand-copy"><strong><?php echo html_escape($site_name); ?></strong><small>Panel de administración</small></span>
        </a>
        <nav class="sidebar-nav" aria-label="Secciones">
            <a class="nav-link<?php echo $active_nav === 'users' ? ' is-active' : ''; ?>" <?php echo $active_nav === 'users' ? 'aria-current="page"' : ''; ?> href="<?php echo html_escape(site_url('auth')); ?>">
                <span class="nav-icon" aria-hidden="true">◫</span>Usuarios
            </a>
            <a class="nav-link<?php echo $active_nav === 'groups' ? ' is-active' : ''; ?>" <?php echo $active_nav === 'groups' ? 'aria-current="page"' : ''; ?> href="<?php echo html_escape(site_url('auth') . '#groups'); ?>">
                <span class="nav-icon" aria-hidden="true">◇</span>Grupos
            </a>
            <a class="nav-link<?php echo $active_nav === 'settings' ? ' is-active' : ''; ?>" <?php echo $active_nav === 'settings' ? 'aria-current="page"' : ''; ?> href="<?php echo html_escape(site_url('auth/settings')); ?>">
                <span class="nav-icon" aria-hidden="true">⚙</span>Configuración
            </a>
        </nav>
        <div class="sidebar-foot">
            <span class="sidebar-foot-label">Administración</span>
            <form method="post" action="<?php echo html_escape(site_url('auth/logout')); ?>">
                <input type="hidden" name="<?php echo html_escape($this->security->get_csrf_token_name()); ?>" value="<?php echo html_escape($this->security->get_csrf_hash()); ?>">
                <button type="submit" class="sidebar-logout">Cerrar sesión <span aria-hidden="true">↗</span></button>
            </form>
        </div>
    </aside>
    <div class="workspace">
        <header class="topbar">
            <button class="menu-toggle" type="button" aria-controls="dashboard-sidebar" aria-expanded="false" aria-label="Abrir navegación" data-menu-toggle>
                <span aria-hidden="true">☰</span>
            </button>
            <div class="topbar-location"><span>Panel</span><span aria-hidden="true">/</span><strong><?php echo html_escape($page_title); ?></strong></div>
            <span class="topbar-site"><?php echo html_escape($site_name); ?></span>
        </header>
        <main class="main-content" id="main-content" tabindex="-1">
