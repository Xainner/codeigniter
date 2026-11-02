<?php
defined('BASEPATH') OR exit('No direct script access allowed');

// Email clients ignore stylesheets and most layout CSS: tables and inline styles only.
$site_name = trim((string) ($this->app_settings['site_name'] ?? '')) ?: 'CodeIgniter';
$brand = (string) ($this->app_settings['brand_color'] ?? '');
$brand = preg_match('/^#[0-9a-fA-F]{6}$/D', $brand) ? $brand : '#4f46e5';
?><!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?php echo html_escape($email_title); ?></title>
</head>
<body style="margin:0;padding:0;background:#f5f7fb;color:#17243a;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f5f7fb;">
<tr><td align="center" style="padding:32px 16px;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:520px;background:#ffffff;border:1px solid #e4e9f0;border-radius:14px;">
<tr><td style="padding:28px 32px 0;font-size:14px;font-weight:700;color:<?php echo $brand; ?>;"><?php echo html_escape($site_name); ?></td></tr>
<tr><td style="padding:14px 32px 0;"><h1 style="margin:0;font-size:22px;line-height:1.3;color:#17243a;"><?php echo html_escape($email_title); ?></h1></td></tr>
<tr><td style="padding:12px 32px 0;font-size:15px;line-height:1.6;color:#44546a;"><?php echo html_escape($email_intro); ?></td></tr>
<tr><td style="padding:24px 32px;"><a href="<?php echo html_escape($email_url); ?>" style="display:inline-block;padding:12px 20px;border-radius:8px;background:<?php echo $brand; ?>;color:#ffffff;font-size:15px;font-weight:700;text-decoration:none;"><?php echo html_escape($email_button); ?></a></td></tr>
<tr><td style="padding:0 32px 28px;font-size:13px;line-height:1.6;color:#66758b;">Si el botón no funciona, copia este enlace en tu navegador:<br><span style="color:#17243a;word-break:break-all;"><?php echo html_escape($email_url); ?></span></td></tr>
<tr><td style="padding:16px 32px 24px;border-top:1px solid #e4e9f0;font-size:12px;line-height:1.6;color:#8290a1;"><?php echo html_escape($email_note); ?></td></tr>
</table>
</td></tr>
</table>
</body>
</html>
