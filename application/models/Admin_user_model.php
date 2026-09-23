<?php defined('BASEPATH') OR exit('No direct script access allowed');

/** Read-only queries for the admin user directory. */
class Admin_user_model extends CI_Model
{
    private function search($query)
    {
        if ($query !== '') {
            $this->db->group_start()
                ->like('email', $query)
                ->or_like('first_name', $query)
                ->or_like('last_name', $query)
                ->group_end();
        }
    }

    public function count($query)
    {
        $this->search($query);
        return (int) $this->db->count_all_results('users');
    }

    public function page($query, $limit, $offset)
    {
        $this->search($query);
        $users = $this->db->order_by('id', 'DESC')->limit($limit, $offset)->get('users')->result();
        if (!$users) {
            return [];
        }
        $ids = [];
        foreach ($users as $user) {
            $user->groups = [];
            $ids[] = (int) $user->id;
        }
        $membership = $this->db->select('users_groups.user_id, groups.id, groups.name')
            ->from('users_groups')
            ->join('groups', 'groups.id = users_groups.group_id')
            ->where_in('users_groups.user_id', $ids)
            ->get()->result();
        $by_user = [];
        foreach ($users as $user) {
            $by_user[(int) $user->id] = $user;
        }
        foreach ($membership as $group) {
            $by_user[(int) $group->user_id]->groups[] = $group;
        }
        return $users;
    }
}
