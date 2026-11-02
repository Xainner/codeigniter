<?php
/** Unit tests load application classes directly, without booting CodeIgniter. */
declare(strict_types=1);

$root = dirname(__DIR__, 2);
require $root . '/vendor/autoload.php';

define('BASEPATH', $root . '/vendor/pocketarc/codeigniter/system/');
define('APPPATH', $root . '/application/');
define('FCPATH', $root . '/public/');

putenv('APP_KEY=' . str_repeat('ab', 32));

