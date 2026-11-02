<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Rate_limiter_model extends CI_Model
{
	public function allow($scope, $subject, $max, $seconds)
	{
		if (random_int(1, 100) === 1) {
			$this->db->where('expires_at <', time())->delete('request_limits');
		}
		$this->load->library('secret_box');
		$key = hash_hmac('sha256', $scope . ':' . $subject, $this->secret_box->key('rate_limiter'));
		$expires = time() + $seconds;
		$this->db->query(
			'INSERT INTO request_limits (key_hash, hits, expires_at) VALUES (?, 1, ?) '
			. 'ON DUPLICATE KEY UPDATE hits = IF(expires_at <= ?, 1, hits + 1), '
			. 'expires_at = IF(expires_at <= ?, ?, expires_at)',
			[$key, $expires, time(), time(), $expires]
		);
		$row = $this->db->get_where('request_limits', ['key_hash' => $key])->row();
		return $row && (int) $row->hits <= $max;
	}

	/**
	 * Failed logins Ion Auth recorded from one address across every identity.
	 * Its own lockout counts identity and address together, so this is what
	 * stops one address from trying a password against many accounts.
	 */
	public function recent_login_failures($ip, $seconds)
	{
		return (int) $this->db->where('ip_address', (string) $ip)
			->where('time >', time() - (int) $seconds)
			->count_all_results('login_attempts');
	}
}
