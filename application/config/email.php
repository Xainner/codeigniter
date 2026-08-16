<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
| -------------------------------------------------------------------------
| Email Settings (MY_Email -> PHPMailer)
| -------------------------------------------------------------------------
| This config is loaded by CI_Email when the library is instantiated and
| consumed by MY_Email to configure the PHPMailer transport.
|
| Available protocols: 'mail', 'sendmail', 'smtp'
| If you use 'smtp', fill in the smtp_* settings below.
|
| You can also override these per-call with $this->email->initialize(array(...))
| (IonAuth does this via $config['email_config'] in ion_auth.php when
| $config['use_ci_email'] is TRUE).
|
*/
$config['protocol']    = 'smtp';       // mail | sendmail | smtp
$config['mailpath']    = '/usr/sbin/sendmail';
$config['smtp_host']   = 'smtp.example.com';
$config['smtp_port']   = 587;
$config['smtp_user']   = '';
$config['smtp_pass']   = '';
$config['smtp_timeout'] = 5;
$config['smtp_keepalive'] = FALSE;
$config['smtp_crypto'] = 'tls';        // '', 'tls' or 'ssl'
$config['mailtype']    = 'html';       // text | html
$config['charset']     = 'utf-8';
$config['wordwrap']    = TRUE;
$config['newline']     = "\r\n";
$config['crlf']        = "\r\n";
$config['validate']    = TRUE;
$config['priority']    = 3;
