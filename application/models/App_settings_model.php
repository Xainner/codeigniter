<?php defined('BASEPATH') OR exit('No direct script access allowed');

/** Settings that an administrator may change without deploying code. */
class App_settings_model extends CI_Model
{
    private $smtp_encrypted = NULL;
    private $smtp_plain = NULL;
    private const DEFAULTS = [
        'public_registration' => '0',
        'email_activation' => '1',
        'remember_users' => '0',
        'min_password_length' => '12',
        'maximum_login_attempts' => '5',
        'lockout_time' => '900',
        'site_name' => 'CodeIgniter',
        'brand_color' => '#4f46e5',
        'brand_logo' => '',
        'smtp_host' => '',
        'smtp_port' => '587',
        'smtp_crypto' => 'tls',
        'smtp_user' => '',
        'smtp_password' => '',
        'smtp_from_email' => '',
        'smtp_from_name' => '',
        'smtp_verified' => '0',
    ];

    public function all()
    {
        $settings = self::DEFAULTS;
        foreach ($this->db->get('app_settings')->result() as $row) {
            if (array_key_exists($row->name, $settings)) {
                $settings[$row->name] = $row->value;
                if ($row->name === 'smtp_password') {
                    $this->smtp_encrypted = $row->value;
                }
            }
        }
        foreach (['public_registration', 'email_activation', 'remember_users', 'smtp_verified'] as $name) {
            $settings[$name] = $settings[$name] === '1';
        }
        if ($settings['smtp_verified'] && $settings['smtp_password'] !== '' && $this->smtp_password() === '') {
            // A changed APP_KEY or damaged secret invalidates the old SMTP test.
            // Keep the stored value so it can be replaced from the settings page.
            $settings['smtp_verified'] = FALSE;
        }
        foreach (['min_password_length', 'maximum_login_attempts', 'lockout_time', 'smtp_port'] as $name) {
            $settings[$name] = (int) $settings[$name];
        }
        $settings['smtp_configured'] = $settings['smtp_host'] !== ''
            && $settings['smtp_from_email'] !== '' && $settings['smtp_verified'];
        unset($settings['smtp_password']);
        return $settings;
    }

    public function smtp_password()
    {
        if ($this->smtp_plain !== NULL) {
            return $this->smtp_plain;
        }
        if ($this->smtp_encrypted === NULL) {
            $row = $this->db->get_where('app_settings', ['name' => 'smtp_password'])->row();
            $this->smtp_encrypted = $row ? $row->value : '';
        }
        if ($this->smtp_encrypted === '') {
            $this->smtp_plain = '';
            return '';
        }
        try {
            $key = $this->secret_key();
            $encoded = base64_decode($this->smtp_encrypted, TRUE);
            if ($encoded === FALSE || strlen($encoded) <= 28) {
                throw new RuntimeException('Invalid SMTP secret');
            }
            $nonce = substr($encoded, 0, 12);
            $tag = substr($encoded, 12, 16);
            $plain = openssl_decrypt(substr($encoded, 28), 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $nonce, $tag);
            if ($plain === FALSE) {
                throw new RuntimeException('Cannot decrypt SMTP secret');
            }
            $this->smtp_plain = $plain;
            return $plain;
        } catch (RuntimeException $exception) {
            // Keep the settings page available so an administrator can replace
            // the password after a key change or damaged ciphertext.
            log_message('error', 'Stored SMTP password could not be decrypted');
            $this->smtp_plain = '';
            return '';
        }
    }

    public function save(array $values, $actor_id)
    {
        $before = $this->all();
        $changed = [];
        $prepared = [];
        foreach ($values as $name => $value) {
            if (!array_key_exists($name, self::DEFAULTS)) {
                throw new InvalidArgumentException('Unknown setting');
            }
            if ($name === 'smtp_password') {
                if ($value === '') {
                    continue;
                }
                $nonce = random_bytes(12);
                $tag = '';
                $cipher = openssl_encrypt((string) $value, 'aes-256-gcm', $this->secret_key(), OPENSSL_RAW_DATA, $nonce, $tag);
                if ($cipher === FALSE) {
                    throw new RuntimeException('Cannot encrypt SMTP secret');
                }
                $value = base64_encode($nonce . $tag . $cipher);
                $changed[] = 'smtp_password';
            } elseif (isset($before[$name]) && ($before[$name] === TRUE ? '1' : ($before[$name] === FALSE ? '0' : (string) $before[$name])) !== (string) $value) {
                $changed[] = $name;
            }
            $prepared[$name] = (string) $value;
        }
        // Validate and encrypt before opening a transaction. A missing key must
        // not leave the settings connection in an unfinished transaction.
        $this->db->trans_begin();
        foreach ($prepared as $name => $value) {
            if (!$this->db->replace('app_settings', ['name' => $name, 'value' => $value])) {
                $this->db->trans_rollback();
                return FALSE;
            }
        }
        if ($changed) {
            if (!$this->db->insert('settings_audit', [
                'actor_user_id' => (int) $actor_id,
                'changed_keys' => json_encode(array_values(array_unique($changed))),
                'created_at' => time(),
            ])) {
                $this->db->trans_rollback();
                return FALSE;
            }
        }
        if ($this->db->trans_status() === FALSE) {
            $this->db->trans_rollback();
            return FALSE;
        }
        $ok = $this->db->trans_commit();
        if ($ok) {
            $this->smtp_encrypted = NULL;
            $this->smtp_plain = NULL;
        }
        return $ok;
    }

    public function setup_completed()
    {
        $row = $this->db->get_where('app_settings', ['name' => 'setup_completed'])->row();
        return $row && $row->value === '1';
    }

    public function complete_setup()
    {
        return $this->db->replace('app_settings', ['name' => 'setup_completed', 'value' => '1']);
    }

    private function secret_key()
    {
        $hex = getenv('APP_KEY');
        if (!is_string($hex) || !preg_match('/^[0-9a-fA-F]{64}$/D', $hex)) {
            throw new RuntimeException('APP_KEY must be a 32-byte hexadecimal key');
        }
        return hex2bin($hex);
    }
}
