<?php defined('BASEPATH') OR exit('No direct script access allowed');

/** Append-only record of account and group administration. */
class Admin_audit_model extends CI_Model
{
    /** Details hold field names and ids only, never personal values or secrets. */
    public function record($actor_id, $action, $target_type, $target_id, array $details = [])
    {
        $recorded = $this->db->insert('admin_audit', [
            'actor_user_id' => (int) $actor_id,
            'action' => (string) $action,
            'target_type' => (string) $target_type,
            'target_id' => (int) $target_id,
            'details' => json_encode($details),
            'created_at' => time(),
        ]);
        if (!$recorded) {
            log_message('error', 'Admin audit entry could not be recorded: ' . $action);
        }
        return $recorded;
    }
}
