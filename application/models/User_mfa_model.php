<?php defined('BASEPATH') OR exit('No direct script access allowed');

/** Two-factor authentication state: sealed TOTP secrets and hashed recovery codes. */
class User_mfa_model extends CI_Model
{
	public const RECOVERY_CODES = 10;
	private const SECRET_PURPOSE = 'user_mfa.secret';
	private const RECOVERY_PURPOSE = 'user_mfa.recovery_code';

	public function __construct()
	{
		parent::__construct();
		$this->load->library(['secret_box', 'two_factor']);
	}

	/** The user's two-factor row, or NULL while it is disabled. */
	public function status($user_id)
	{
		return $this->db->select('enabled_at')->get_where('user_mfa', ['user_id' => (int) $user_id])->row();
	}

	public function enabled($user_id)
	{
		return $this->status($user_id) !== NULL;
	}

	public function enabled_for_identity($identity)
	{
		return $this->db->from('user_mfa')
			->join('users', 'users.id = user_mfa.user_id')
			->where('users.email', (string) $identity)
			->count_all_results() > 0;
	}

	/**
	 * Stores a confirmed secret, marking the step that confirmed it as used,
	 * and returns a fresh set of recovery codes to show once.
	 */
	public function enable($user_id, $secret, $confirmed_step)
	{
		$user_id = (int) $user_id;
		$sealed = $this->secret_box->seal($secret, self::SECRET_PURPOSE);
		$this->db->trans_begin();
		$this->db->replace('user_mfa', [
			'user_id' => $user_id, 'secret' => $sealed,
			'last_step' => (int) $confirmed_step, 'enabled_at' => time(),
		]);
		$codes = $this->store_recovery_codes($user_id);
		if ($this->db->trans_status() === FALSE) {
			$this->db->trans_rollback();
			return NULL;
		}
		$this->db->trans_commit();
		return $codes;
	}

	public function disable($user_id)
	{
		$this->db->trans_begin();
		$this->db->delete('user_mfa_recovery_codes', ['user_id' => (int) $user_id]);
		$this->db->delete('user_mfa', ['user_id' => (int) $user_id]);
		if ($this->db->trans_status() === FALSE) {
			$this->db->trans_rollback();
			return FALSE;
		}
		return $this->db->trans_commit();
	}

	/** Replaces every recovery code with a new set, returned once. */
	public function regenerate_recovery_codes($user_id)
	{
		$this->db->trans_begin();
		$codes = $this->store_recovery_codes((int) $user_id);
		if ($this->db->trans_status() === FALSE) {
			$this->db->trans_rollback();
			return NULL;
		}
		$this->db->trans_commit();
		return $codes;
	}

	public function remaining_recovery_codes($user_id)
	{
		return (int) $this->db->where('user_id', (int) $user_id)->where('used_at IS NULL', NULL, FALSE)
			->count_all_results('user_mfa_recovery_codes');
	}

	/**
	 * Accepts a current authenticator code that was not used before, or an
	 * unused recovery code. Either is consumed atomically, so concurrent
	 * requests cannot use the same code twice.
	 */
	public function verify($user_id, $code)
	{
		$user_id = (int) $user_id;
		$code = trim((string) $code);
		if (preg_match('/^\d{6}$/D', preg_replace('/\s+/', '', $code))) {
			$row = $this->db->select('secret')->get_where('user_mfa', ['user_id' => $user_id])->row();
			$secret = $row ? $this->secret_box->open($row->secret, self::SECRET_PURPOSE) : NULL;
			if ($secret === NULL) {
				if ($row) {
					log_message('error', 'A two-factor secret could not be decrypted');
				}
				return FALSE;
			}
			$step = $this->two_factor->matching_step($secret, $code);
			if ($step === NULL) {
				return FALSE;
			}
			$this->db->where('user_id', $user_id)->where('last_step <', $step)
				->update('user_mfa', ['last_step' => $step]);
			return $this->db->affected_rows() === 1;
		}
		$normalized = $this->two_factor->normalize_recovery_code($code);
		if ($normalized === '') {
			return FALSE;
		}
		$this->db->where('user_id', $user_id)->where('code_hash', $this->recovery_hash($normalized))
			->where('used_at IS NULL', NULL, FALSE)
			->update('user_mfa_recovery_codes', ['used_at' => time()]);
		return $this->db->affected_rows() === 1;
	}

	private function store_recovery_codes($user_id)
	{
		$codes = $this->two_factor->recovery_codes(self::RECOVERY_CODES);
		$this->db->delete('user_mfa_recovery_codes', ['user_id' => $user_id]);
		$rows = [];
		foreach ($codes as $code) {
			$rows[] = ['user_id' => $user_id, 'code_hash' => $this->recovery_hash($this->two_factor->normalize_recovery_code($code))];
		}
		$this->db->insert_batch('user_mfa_recovery_codes', $rows);
		return $codes;
	}

	/** Recovery codes are random, so a keyed hash is enough; no slow password hash is needed. */
	private function recovery_hash($normalized)
	{
		return hash_hmac('sha256', $normalized, $this->secret_box->key(self::RECOVERY_PURPOSE));
	}
}
