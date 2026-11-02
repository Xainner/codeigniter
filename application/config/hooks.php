<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
| -------------------------------------------------------------------------
| Hooks
| -------------------------------------------------------------------------
| This file lets you define "hooks" to extend CI without hacking the core
| files.  Please see the user guide for info:
|
|	https://codeigniter.com/userguide3/general/hooks.html
|
*/

// Error reporting to Sentry; does nothing unless SENTRY_DSN is set.
$hook['pre_system'][] = array(
	'class'    => 'Error_tracking',
	'function' => 'start',
	'filename' => 'Error_tracking.php',
	'filepath' => 'hooks',
);
