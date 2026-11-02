<?php
defined('BASEPATH') OR exit('No direct script access allowed');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * MY_Email
 *
 * Extends CI_Email keeping the standard CI API (from/to/subject/message/attach/
 * clear/initialize) but delivers the message through PHPMailer.
 *
 * All SMTP/sendmail settings are configured exactly like CI_Email does, so
 * existing configs (application/config/email.php, IonAuth email_config, etc.)
 * keep working.
 *
 * @package		CodeIgniter
 * @subpackage	Libraries
 * @category	Libraries
 * @author		Xainner
 */
class MY_Email extends CI_Email
{
	/** @var array Raw From: ['email' => string, 'name' => string] */
	protected $_pm_from = array('email' => '', 'name' => '');

	/** @var array Raw To recipients */
	protected $_pm_to = array();

	/** @var array Raw CC recipients */
	protected $_pm_cc = array();

	/** @var array Raw BCC recipients */
	protected $_pm_bcc = array();

	/** @var string Raw subject (unencoded) */
	protected $_pm_subject = '';

	/** @var string Raw body */
	protected $_pm_body = '';

	/** @var string Raw plain-text alternative body */
	protected $_pm_alt = '';

	/** @var array Raw attachments */
	protected $_pm_attachments = array();

	/** @var PHPMailer|null */
	protected $_pm_mailer;

	/** @var bool Whether PHPMailer is available (composer vendor present) */
	protected $_pm_enabled;

	/**
	 * Constructor
	 *
	 * @param	array	$config = array()
	 */
	public function __construct(array $config = array())
	{
		parent::__construct($config);

		$this->_pm_enabled = class_exists('PHPMailer\PHPMailer\PHPMailer');
		$this->_pm_mailer  = $this->_pm_enabled ? $this->_create_mailer() : NULL;
	}

	/**
	 * Initialize preferences
	 *
	 * @param	array	$config
	 * @return	MY_Email
	 */
	public function initialize(array $config = array())
	{
		parent::initialize($config);

		$this->_pm_enabled = class_exists('PHPMailer\PHPMailer\PHPMailer');
		$this->_pm_mailer  = $this->_pm_enabled ? $this->_create_mailer() : NULL;

		return $this;
	}

	/**
	 * Reset the raw message state
	 *
	 * @param	bool	$clear_attachments
	 * @return	MY_Email
	 */
	public function clear($clear_attachments = FALSE)
	{
		parent::clear($clear_attachments);

		$this->_pm_from = array('email' => '', 'name' => '');
		$this->_pm_to = array();
		$this->_pm_cc = array();
		$this->_pm_bcc = array();
		$this->_pm_subject = '';
		$this->_pm_body = '';
		$this->_pm_alt = '';

		if ($clear_attachments !== FALSE)
		{
			$this->_pm_attachments = array();
		}

		return $this;
	}

	/**
	 * Set FROM
	 *
	 * @param	string	$from
	 * @param	string	$name
	 * @param	string	$return_path = NULL
	 * @return	MY_Email
	 */
	public function from($from, $name = '', $return_path = NULL)
	{
		if (preg_match('/\<(.*)\>/', $from, $match))
		{
			$from = $match[1];
		}

		$this->_pm_from = array('email' => trim($from), 'name' => trim($name));

		parent::from($from, $name, $return_path);

		return $this;
	}

	/**
	 * Set Recipients
	 *
	 * @param	string|array	$to
	 * @return	MY_Email
	 */
	public function to($to)
	{
		foreach ($this->_str_to_array($to) as $email)
		{
			$this->_pm_to[] = trim($email);
		}

		parent::to($to);

		return $this;
	}

	/**
	 * Set CC
	 *
	 * @param	string|array	$cc
	 * @return	MY_Email
	 */
	public function cc($cc)
	{
		foreach ($this->_str_to_array($cc) as $email)
		{
			$this->_pm_cc[] = trim($email);
		}

		parent::cc($cc);

		return $this;
	}

	/**
	 * Set BCC
	 *
	 * @param	string|array	$bcc
	 * @param	string	$limit
	 * @return	MY_Email
	 */
	public function bcc($bcc, $limit = '')
	{
		foreach ($this->_str_to_array($bcc) as $email)
		{
			$this->_pm_bcc[] = trim($email);
		}

		parent::bcc($bcc, $limit);

		return $this;
	}

	/**
	 * Set Email Subject
	 *
	 * @param	string	$subject
	 * @return	MY_Email
	 */
	public function subject($subject)
	{
		$this->_pm_subject = $subject;

		parent::subject($subject);

		return $this;
	}

	/**
	 * Set Body
	 *
	 * @param	string	$body
	 * @return	MY_Email
	 */
	public function message($body)
	{
		$this->_pm_body = $body;

		parent::message($body);

		return $this;
	}

	/**
	 * Assign file attachments
	 *
	 * @param	string	$file	Can be local path, URL or buffered content
	 * @param	string	$disposition = ''
	 * @param	string	$newname = NULL
	 * @param	string	$mime = ''
	 * @return	bool|MY_Email
	 */
	public function attach($file, $disposition = '', $newname = NULL, $mime = '')
	{
		// CI_Email rejects missing files; PHPMailer would otherwise fail at send().
		if (parent::attach($file, $disposition, $newname, $mime) !== $this)
		{
			return FALSE;
		}

		$this->_pm_attachments[] = array(
			'file'       => $file,
			'newname'    => $newname,
			'disposition'=> empty($disposition) ? 'attachment' : $disposition,
			'mime'       => $mime,
			'buffered'   => ($mime !== ''),
		);

		return $this;
	}

	/**
	 * Set plain-text alternative body (for HTML messages)
	 *
	 * @param	string	$str
	 * @return	MY_Email
	 */
	public function set_alt_message($str = '')
	{
		$this->alt_message = $str;
		$this->_pm_alt = $str;

		return $this;
	}

	/**
	 * Send Email
	 *
	 * @param	bool	$auto_clear = TRUE
	 * @return	bool
	 */
	public function send($auto_clear = TRUE)
	{
		// Fall back to the native CI_Email transport when PHPMailer is not installed
		if ( ! $this->_pm_enabled || $this->_pm_mailer === NULL)
		{
			return parent::send($auto_clear);
		}

		$mailer = $this->_pm_mailer;

		// Always start from a clean slate for the message itself
		$mailer->clearAllRecipients();
		$mailer->clearAttachments();
		$mailer->clearReplyTos();
		$mailer->clearCustomHeaders();

		if ($this->_pm_from['email'] === '')
		{
			$this->_set_error_message('lang:email_no_from');
			log_message('error', 'PHPMailer: no From address set');
			return FALSE;
		}

		try
		{
			$mailer->setFrom($this->_pm_from['email'], $this->_pm_from['name']);

			foreach ($this->_pm_to as $email)
			{
				$mailer->addAddress($email);
			}

			if (empty($this->_pm_to))
			{
				$this->_set_error_message('lang:email_no_recipients');
				return FALSE;
			}

			foreach ($this->_pm_cc as $email)
			{
				$mailer->addCC($email);
			}

			foreach ($this->_pm_bcc as $email)
			{
				$mailer->addBCC($email);
			}

			// Reply-To from CI headers
			if (isset($this->_headers['Reply-To']))
			{
				if (preg_match('/^(.*?)\s*<([^>]+)>$/', $this->_headers['Reply-To'], $m))
				{
					$mailer->addReplyTo(trim($m[2]), trim($m[1], " \""));
				}
				else
				{
					$mailer->addReplyTo(trim($this->_headers['Reply-To']));
				}
			}

			$mailer->Subject = $this->_pm_subject;

			if ($this->mailtype === 'html')
			{
				$mailer->isHTML(TRUE);
				$mailer->Body = $this->_pm_body;
				// HTML-only mail scores worse with spam filters and is unreadable in text clients.
				$mailer->AltBody = $this->_pm_alt !== '' ? $this->_pm_alt : $this->_pm_text_from_html($this->_pm_body);
			}
			else
			{
				$mailer->isHTML(FALSE);
				$mailer->Body = $this->_pm_body;
			}

			if ((int) $this->priority >= 1 && (int) $this->priority <= 5)
			{
				$mailer->Priority = (int) $this->priority;
			}

			foreach ($this->_pm_attachments as $att)
			{
				if ($att['buffered'])
				{
					$mailer->addStringAttachment($att['file'], $att['newname'], 'base64', $att['mime'], $att['disposition']);
				}
				else
				{
					$mailer->addAttachment($att['file'], $att['newname'], 'base64', $att['mime'], $att['disposition']);
				}
			}

			$sent = $mailer->send();
		}
		catch (Exception $e)
		{
			$this->_set_error_message('lang:email_send_failure_smtp', $e->getMessage());
			log_message('error', 'PHPMailer transport failed');
			return FALSE;
		}

		log_message('info', 'PHPMailer message accepted by transport');

		if ($auto_clear)
		{
			$this->clear();
		}

		return $sent;
	}

	/**
	 * Open and close an SMTP session (connect, STARTTLS and AUTH) without
	 * sending a message. Other transports have no session to verify.
	 *
	 * @return	bool
	 */
	public function verify_transport()
	{
		if ( ! $this->_pm_enabled || $this->_pm_mailer === NULL || $this->protocol !== 'smtp')
		{
			return TRUE;
		}

		try
		{
			$connected = $this->_pm_mailer->smtpConnect();
		}
		catch (Exception $e)
		{
			$connected = FALSE;
		}
		$this->_pm_mailer->smtpClose();

		if ( ! $connected)
		{
			log_message('error', 'PHPMailer SMTP verification failed');
		}

		return $connected;
	}

	// --------------------------------------------------------------------

	/**
	 * Plain-text version of an HTML body. Unlike PHPMailer::html2text() it
	 * keeps link targets, which carry the activation and recovery URLs.
	 *
	 * @param	string	$html
	 * @return	string
	 */
	protected function _pm_text_from_html($html)
	{
		$html = preg_replace('#<(head|style|script|title)\b[^>]*>.*?</\1>#is', '', (string) $html);
		$html = preg_replace_callback('#<a\b[^>]*\bhref\s*=\s*(["\'])(.*?)\1[^>]*>(.*?)</a>#is', function ($match)
		{
			$text = trim(html_entity_decode(strip_tags($match[3]), ENT_QUOTES, 'UTF-8'));
			$url = html_entity_decode($match[2], ENT_QUOTES, 'UTF-8');
			return ($text === '' OR $text === $url) ? $url : $text.': '.$url;
		}, $html);
		$html = preg_replace('#<br\s*/?>#i', "\n", $html);
		$html = preg_replace('#</(p|div|h[1-6]|li|tr|table)>#i', "\n\n", $html);
		$text = html_entity_decode(strip_tags($html), ENT_QUOTES, 'UTF-8');
		$text = preg_replace("/[ \t]+/", ' ', $text);
		$text = preg_replace("/ *\n */", "\n", $text);

		return trim(preg_replace("/\n{3,}/", "\n\n", $text));
	}

	/**
	 * Build and configure a PHPMailer transport from the CI_Email settings
	 *
	 * @return	PHPMailer
	 */
	protected function _create_mailer()
	{
		$mailer = new PHPMailer(TRUE); // exceptions enabled

		$mailer->CharSet = $this->charset !== '' ? $this->charset : 'utf-8';
		$mailer->Timeout = (int) $this->smtp_timeout;
		$mailer->Priority = (int) $this->priority;

		if ($this->protocol === 'smtp')
		{
			$mailer->isSMTP();
			$mailer->Host       = $this->smtp_host;
			$mailer->Port       = (int) $this->smtp_port;
			$mailer->SMTPAuth   = (bool) $this->_smtp_auth;
			$mailer->Username   = $this->smtp_user;
			$mailer->Password   = $this->smtp_pass;
			$mailer->SMTPKeepAlive = (bool) $this->smtp_keepalive;
			$mailer->SMTPSecure = $this->smtp_crypto;

			log_message('debug', 'PHPMailer: SMTP transport configured (host='.$this->smtp_host.', port='.$this->smtp_port.')');
		}
		elseif ($this->protocol === 'sendmail')
		{
			$mailer->isSendmail();
		}
		else
		{
			$mailer->isMail();
		}

		return $mailer;
	}
}
