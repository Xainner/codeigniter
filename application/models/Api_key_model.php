<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * API keys for the REST endpoints. Only a keyed hash of each key is stored,
 * so the database alone never reveals a usable key.
 */
class Api_key_model extends CI_Model
{
    private const PURPOSE = 'api_keys.token';
    private const PATTERN = '/^ak_[0-9a-f]{48}$/D';

    public function __construct()
    {
        parent::__construct();
        $this->load->library('secret_box');
    }

    /** Creates a key for the user and returns its id and plaintext token, which is shown once. */
    public function create($user_id, $name)
    {
        $token = 'ak_' . bin2hex(random_bytes(24));
        $inserted = $this->db->insert('api_keys', [
            'user_id' => (int) $user_id,
            'name' => (string) $name,
            'token_hash' => $this->hash($token),
            'token_hint' => substr($token, -4),
            'created_at' => time(),
        ]);
        return $inserted ? ['id' => (int) $this->db->insert_id(), 'token' => $token] : NULL;
    }

    /** The active key matching the token, owned by an active user, or NULL. */
    public function authenticate($token)
    {
        if (!preg_match(self::PATTERN, (string) $token)) {
            return NULL;
        }
        $key = $this->db->select('api_keys.id, api_keys.user_id')
            ->join('users', 'users.id = api_keys.user_id')
            ->where('api_keys.token_hash', $this->hash($token))
            ->where('api_keys.revoked_at IS NULL', NULL, FALSE)
            ->where('users.active', 1)
            ->get('api_keys')->row();
        if ($key) {
            // At most one write a minute per key.
            $this->db->where('id', (int) $key->id)
                ->group_start()->where('last_used_at IS NULL', NULL, FALSE)->or_where('last_used_at <', time() - 60)->group_end()
                ->update('api_keys', ['last_used_at' => time()]);
        }
        return $key ?: NULL;
    }

    public function revoke($id)
    {
        $this->db->where('id', (int) $id)->where('revoked_at IS NULL', NULL, FALSE)
            ->update('api_keys', ['revoked_at' => time()]);
        return $this->db->affected_rows() === 1;
    }

    /** Every key with its owner's email, newest first. */
    public function all()
    {
        return $this->db->select('api_keys.id, api_keys.name, api_keys.token_hint, api_keys.created_at,'
            . ' api_keys.last_used_at, api_keys.revoked_at, users.email AS owner_email')
            ->join('users', 'users.id = api_keys.user_id')
            ->order_by('api_keys.id', 'DESC')
            ->get('api_keys')->result();
    }

    private function hash($token)
    {
        return hash_hmac('sha256', (string) $token, $this->secret_box->key(self::PURPOSE));
    }
}
