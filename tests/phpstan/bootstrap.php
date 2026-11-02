<?php
// Constants CodeIgniter defines at runtime, so PHPStan can analyse the application on its own.
$root = dirname(__DIR__, 2);
define('BASEPATH', $root . '/vendor/pocketarc/codeigniter/system/');
define('APPPATH', $root . '/application/');
define('FCPATH', $root . '/public/');
define('VIEWPATH', APPPATH . 'views/');
define('ENVIRONMENT', 'development');
