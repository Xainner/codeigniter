<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Rate_limiter_model extends CI_Model
{
    public function allow($scope, $subject, $max, $seconds)
    {
        $key = hash('sha256', $scope . ':' . $subject);
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
}
