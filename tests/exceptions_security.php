<?php
// Run with: php tests/exceptions_security.php
define('BASEPATH', dirname(__DIR__).DIRECTORY_SEPARATOR.'system'.DIRECTORY_SEPARATOR);
define('VIEWPATH', dirname(__DIR__).DIRECTORY_SEPARATOR.'application'.DIRECTORY_SEPARATOR.'views'.DIRECTORY_SEPARATOR);

function is_cli()
{
	return FALSE;
}

function config_item($item)
{
	return $item === 'charset' ? 'UTF-8' : '';
}

function set_status_header($code = 200, $text = '')
{
}

require BASEPATH.'core/Common.php';
require BASEPATH.'core/Exceptions.php';

function assert_safe($html, $label)
{
	if (strpos($html, '<script>') !== FALSE || strpos($html, '&lt;script&gt;') === FALSE)
	{
		fwrite(STDERR, $label." failed\n");
		exit(1);
	}
}

$errors = new CI_Exceptions();
$payload = '<script>alert(1)</script>';

$html = $errors->show_error($payload, $payload);
assert_safe($html, 'Error heading and message');
if (strpos($html, '<p>&lt;script&gt;') === FALSE)
{
	fwrite(STDERR, "Error paragraph markup failed\n");
	exit(1);
}

$html = $errors->show_error('Error', array('Safe', $payload), 'error_db');
assert_safe($html, 'Array and database error message');

ob_start();
$errors->show_exception(new Exception($payload));
$html = ob_get_clean();
assert_safe($html, 'Exception message');

ob_start();
$errors->show_php_error(E_WARNING, $payload, $payload, 1);
$html = ob_get_clean();
assert_safe($html, 'PHP error message');

echo "Exception HTML escaping passed\n";
