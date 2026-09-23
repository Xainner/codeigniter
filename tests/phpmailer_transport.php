<?php
// Run after composer install: php tests/phpmailer_transport.php
define('BASEPATH', dirname(__DIR__).DIRECTORY_SEPARATOR.'system'.DIRECTORY_SEPARATOR);
define('ICONV_ENABLED', extension_loaded('iconv'));
define('MB_ENABLED', extension_loaded('mbstring'));

require dirname(__DIR__).'/vendor/autoload.php';

function config_item($item)
{
	return $item === 'charset' ? 'UTF-8' : NULL;
}

function is_php($version)
{
	return version_compare(PHP_VERSION, $version, '>=');
}

function log_message($level, $message)
{
}

require BASEPATH.'libraries/Email.php';
require dirname(__DIR__).'/application/libraries/MY_Email.php';

class CaptureMailer extends PHPMailer\PHPMailer\PHPMailer
{
	public static $last;
	public static $mime;

	public function send()
	{
		if ( ! $this->preSend())
		{
			return FALSE;
		}
		self::$last = $this;
		self::$mime = $this->getSentMIMEMessage();
		return TRUE;
	}
}

class Test_Email extends MY_Email
{
	protected function _create_mailer()
	{
		$configured = parent::_create_mailer();
		$mailer = new CaptureMailer(TRUE);
		foreach (get_object_vars($configured) as $property => $value)
		{
			$mailer->$property = $value;
		}
		return $mailer;
	}
}

function check($condition, $message)
{
	if ( ! $condition)
	{
		throw new RuntimeException($message);
	}
}

$email = new Test_Email(array(
	'protocol' => 'smtp',
	'smtp_host' => 'smtp.example.test',
	'smtp_port' => 2525,
	'smtp_user' => 'test-user',
	'smtp_pass' => 'test-password',
	'smtp_crypto' => 'tls',
	'mailtype' => 'html',
	'charset' => 'utf-8',
));
$email->from('sender@example.test', 'Sender');
$email->to('recipient@example.test');
$email->cc('copy@example.test');
$email->bcc('hidden@example.test');
$email->reply_to('reply@example.test');
$email->subject('Transport check');
$email->message('<p>Hello HTML</p>');
$email->set_alt_message('Hello text');
$email->attach('attachment contents', 'attachment', 'check.txt', 'text/plain');

check($email->send(), 'MY_Email failed to build the message');
$mailer = CaptureMailer::$last;
$mime = CaptureMailer::$mime;
check($mailer->Host === 'smtp.example.test' && $mailer->Port === 2525, 'SMTP settings were not forwarded');
check($mailer->SMTPAuth === TRUE && $mailer->Username === 'test-user' && $mailer->SMTPSecure === 'tls', 'SMTP authentication settings were not forwarded');
check(count($mailer->getToAddresses()) === 1, 'To address was not forwarded');
check(count($mailer->getCcAddresses()) === 1, 'CC address was not forwarded');
check(count($mailer->getBccAddresses()) === 1, 'BCC address was not forwarded');
check(count($mailer->getReplyToAddresses()) === 1, 'Reply-To address was not forwarded');
check(strpos($mime, 'Hello HTML') !== FALSE && strpos($mime, 'Hello text') !== FALSE, 'HTML or text body is missing');
check(strpos($mime, 'check.txt') !== FALSE, 'Attachment is missing');

$plain = new Test_Email(array('protocol' => 'smtp', 'mailtype' => 'text'));
$plain->from('sender@example.test');
$plain->to('recipient@example.test');
$plain->subject('Plain text check');
$plain->message('Hello plain text');
check($plain->send(), 'Plain text message failed');
check(strpos(CaptureMailer::$mime, 'Hello plain text') !== FALSE, 'Plain text body is missing');

echo "PHPMailer transport smoke test passed\n";
