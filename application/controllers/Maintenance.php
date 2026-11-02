<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Housekeeping for cron, command line only:
 *   php public/index.php maintenance cleanup
 */
class Maintenance extends CI_Controller
{
	public function __construct()
	{
		parent::__construct();
		if (!is_cli()) {
			show_404();
		}
		$this->load->database();
	}

	/** Deletes expired rate-limit counters, day-old login attempts and expired database sessions. */
	public function cleanup()
	{
		$now = time();
		$this->db->where('expires_at <', $now)->delete('request_limits');
		$limits = $this->db->affected_rows();

		// Ion Auth purges old attempts only when someone signs in successfully.
		// Lockouts and the per-address cap look back at most an hour.
		$this->db->where('time <', $now - 86400)->delete('login_attempts');
		$attempts = $this->db->affected_rows();

		$sessions = 0;
		if ($this->config->item('sess_driver') === 'database') {
			$lifetime = (int) $this->config->item('sess_expiration') ?: 7200;
			$this->db->where('timestamp <', $now - $lifetime)->delete($this->config->item('sess_save_path'));
			$sessions = $this->db->affected_rows();
		}

		echo "Deleted {$limits} expired rate limits, {$attempts} old login attempts and {$sessions} expired sessions.\n";
	}
}
