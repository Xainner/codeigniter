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

	/** Number of administration and settings entries together. */
	public function count_entries()
	{
		return (int) $this->db->count_all('admin_audit') + (int) $this->db->count_all('settings_audit');
	}

	/**
	 * Administration and settings entries, newest first, with the names of the
	 * people and groups involved resolved in two extra queries.
	 */
	public function page($limit, $offset)
	{
		$entries = $this->db->query(
			"SELECT 'admin' AS source, id, actor_user_id, action, target_type, target_id, details, created_at FROM admin_audit"
			. " UNION ALL SELECT 'settings', id, actor_user_id, 'settings.update', 'settings', 0, changed_keys, created_at FROM settings_audit"
			. ' ORDER BY created_at DESC, id DESC LIMIT ? OFFSET ?',
			[(int) $limit, (int) $offset]
		)->result();

		$user_ids = [];
		$group_ids = [];
		$key_ids = [];
		foreach ($entries as $entry) {
			$details = json_decode((string) $entry->details, TRUE);
			$details = is_array($details) ? $details : [];
			$entry->fields = $entry->source === 'admin' ? array_values(array_filter((array) ($details['fields'] ?? []), 'is_string')) : [];
			$entry->setting_keys = $entry->source === 'settings' ? array_values(array_filter($details, 'is_string')) : [];
			$entry->group_ids = $entry->source === 'admin' ? array_map('intval', (array) ($details['groups'] ?? [])) : [];
			$user_ids[] = (int) $entry->actor_user_id;
			if ($entry->target_type === 'user') {
				$user_ids[] = (int) $entry->target_id;
			} elseif ($entry->target_type === 'group') {
				$group_ids[] = (int) $entry->target_id;
			} elseif ($entry->target_type === 'api_key') {
				$key_ids[] = (int) $entry->target_id;
			}
			array_push($group_ids, ...$entry->group_ids);
		}
		$emails = $this->names('users', 'email', $user_ids);
		$groups = $this->names('groups', 'name', $group_ids);
		$keys = $this->names('api_keys', 'name', $key_ids);

		foreach ($entries as $entry) {
			$entry->actor_email = $emails[(int) $entry->actor_user_id] ?? NULL;
			$labels = ['user' => $emails, 'group' => $groups, 'api_key' => $keys];
			$entry->target_label = $labels[$entry->target_type][(int) $entry->target_id] ?? NULL;
			$entry->group_names = array_map(static function ($id) use ($groups) {
				return $groups[$id] ?? '#' . $id;
			}, $entry->group_ids);
		}
		return $entries;
	}

	private function names($table, $column, array $ids)
	{
		$ids = array_values(array_unique(array_filter($ids)));
		if (!$ids) {
			return [];
		}
		$rows = $this->db->select('id, ' . $column)->where_in('id', $ids)->get($table)->result_array();
		return array_column($rows, $column, 'id');
	}
}
