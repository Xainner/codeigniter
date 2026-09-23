<?php
// Run with PHP 8.0 or newer: php tests/core_345_smoke.php
define('BASEPATH', dirname(__DIR__).DIRECTORY_SEPARATOR.'system'.DIRECTORY_SEPARATOR);

require BASEPATH.'libraries/Session/CI_Session_driver_interface.php';
require BASEPATH.'libraries/Session/PHP8SessionWrapper.php';
require BASEPATH.'helpers/text_helper.php';

$driver = new class implements CI_Session_driver_interface {
	public $sessions = array();

	public function open($save_path, $name) { return TRUE; }
	public function close() { return TRUE; }
	public function read($session_id) { return isset($this->sessions[$session_id]) ? $this->sessions[$session_id] : ''; }
	public function write($session_id, $session_data) { $this->sessions[$session_id] = $session_data; return TRUE; }
	public function destroy($session_id) { unset($this->sessions[$session_id]); return TRUE; }
	public function gc($maxlifetime) { return 0; }
	public function updateTimestamp($session_id, $data) { return $this->write($session_id, $data); }
	public function validateId($session_id) { return isset($this->sessions[$session_id]); }
};

$wrapper = new CI_SessionWrapper($driver);
if ($wrapper->create_sid() === '')
{
	throw new RuntimeException('Session ID creation failed');
}

ini_set('session.use_strict_mode', '1');
session_set_save_handler($wrapper, TRUE);
if ( ! session_start())
{
	throw new RuntimeException('Session start failed');
}
$_SESSION['check'] = 'kept';
session_write_close();

if ( ! session_start() || $_SESSION['check'] !== 'kept')
{
	throw new RuntimeException('Session resumption failed');
}
if ( ! session_regenerate_id(TRUE) || $_SESSION['check'] !== 'kept')
{
	throw new RuntimeException('Session regeneration failed');
}
session_write_close();

$highlighted = highlight_code('SELECT * FROM users;');
if (strpos($highlighted, 'SELECT') === FALSE || strpos($highlighted, '?&gt;') !== FALSE)
{
	throw new RuntimeException('Profiler query highlighting contains a closing PHP tag');
}

echo "CI3 3.4.5 session and highlighting smoke test passed\n";
