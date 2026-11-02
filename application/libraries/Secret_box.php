<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Purpose-bound encryption with keys derived from APP_KEY.
 *
 * Every purpose gets its own HKDF-SHA256 subkey and is also bound as AES-GCM
 * associated data, so a value sealed for one purpose never opens for another
 * and APP_KEY itself never encrypts or signs anything directly.
 */
class Secret_box
{
	private const PREFIX = 'v2:';
	private const NONCE_BYTES = 12;
	private const TAG_BYTES = 16;

	/** 32-byte key reserved for one purpose. */
	public function key($purpose)
	{
		return hash_hkdf('sha256', $this->master_key(), 32, 'codeigniter-app:' . $purpose);
	}

	public function seal($plaintext, $purpose)
	{
		$nonce = random_bytes(self::NONCE_BYTES);
		$tag = '';
		$cipher = openssl_encrypt((string) $plaintext, 'aes-256-gcm', $this->key($purpose),
			OPENSSL_RAW_DATA, $nonce, $tag, (string) $purpose, self::TAG_BYTES);
		if ($cipher === FALSE) {
			throw new RuntimeException('Cannot encrypt secret');
		}
		return self::PREFIX . base64_encode($nonce . $tag . $cipher);
	}

	/** The plaintext, or NULL when the value is damaged or was sealed for another purpose or key. */
	public function open($sealed, $purpose)
	{
		if (!$this->is_sealed($sealed)) {
			return NULL;
		}
		$raw = base64_decode(substr($sealed, strlen(self::PREFIX)), TRUE);
		if ($raw === FALSE || strlen($raw) < self::NONCE_BYTES + self::TAG_BYTES) {
			return NULL;
		}
		$plain = openssl_decrypt(substr($raw, self::NONCE_BYTES + self::TAG_BYTES), 'aes-256-gcm', $this->key($purpose),
			OPENSSL_RAW_DATA, substr($raw, 0, self::NONCE_BYTES), substr($raw, self::NONCE_BYTES, self::TAG_BYTES), (string) $purpose);
		return $plain === FALSE ? NULL : $plain;
	}

	public function is_sealed($value)
	{
		return is_string($value) && strncmp($value, self::PREFIX, strlen(self::PREFIX)) === 0;
	}

	private function master_key()
	{
		$hex = getenv('APP_KEY');
		if (!is_string($hex) || !preg_match('/^[0-9a-fA-F]{64}$/D', $hex)) {
			throw new RuntimeException('APP_KEY must be a 32-byte hexadecimal key');
		}
		return hex2bin($hex);
	}
}
