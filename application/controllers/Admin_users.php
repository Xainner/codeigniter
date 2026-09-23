<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Admin_users extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->library(['ion_auth', 'form_validation']);
        $this->load->helper(['url', 'form']);
    }

    public function create()
    {
        $this->require_admin();
        $errors = [];
        $user = NULL;
        $selected = [];
        if ($this->input->method(TRUE) === 'POST') {
            $user = $this->posted_user();
            $selected = $this->group_ids($errors);
            $this->validation_rules(TRUE);
            if (!$this->form_validation->run()) {
                $errors[] = strip_tags(validation_errors());
            }
            if (!$errors) {
                $email = strtolower(trim($user->email));
                $ion = $this->config->item('ion_auth');
                $original = $ion;
                $ion['email_activation'] = FALSE;
                $this->config->set_item('ion_auth', $ion);
                $this->db->trans_begin();
                $id = $this->ion_auth->register($email, (string) $this->input->post('password'), $email, [
                    'first_name' => $user->first_name, 'last_name' => $user->last_name,
                    'phone' => $user->phone, 'company' => $user->company,
                ], $selected);
                $this->config->set_item('ion_auth', $original);
                if ($id && $this->db->trans_status()) {
                    $this->db->trans_commit();
                    $this->session->set_flashdata('message', 'Usuario creado.');
                    redirect('auth');
                    return;
                }
                $this->db->trans_rollback();
                $errors[] = strip_tags($this->ion_auth->errors()) ?: 'No se pudo crear el usuario.';
            }
        }
        $this->render_form('create', $user, $selected, $errors);
    }

    public function edit($id)
    {
        $id = (int) $id;
        $current = $this->ion_auth->user()->row();
        if (!$this->ion_auth->logged_in() || !$current || (!$this->ion_auth->is_admin() && (int) $current->id !== $id)) {
            show_error('Acceso denegado', 403);
            return;
        }
        $user = $this->ion_auth->user($id)->row();
        if (!$user) {
            show_404();
            return;
        }
        $is_admin = $this->ion_auth->is_admin();
        $selected = array_map('intval', array_column($this->ion_auth->get_users_groups($id)->result_array(), 'id'));
        $errors = [];
        if ($this->input->method(TRUE) === 'POST') {
            if (!$this->valid_nonce() || $id !== (int) $this->input->post('id')) {
                show_error('Solicitud inválida', 403);
                return;
            }
            $posted = $this->posted_user();
            $this->validation_rules(FALSE, $id);
            if (!$this->form_validation->run()) {
                $errors[] = strip_tags(validation_errors());
            }
            if ($is_admin) {
                $selected = $this->group_ids($errors);
                $admin_group = $this->db->get_where('groups', ['name' => 'admin'])->row();
                $had_admin = $admin_group && in_array((int) $admin_group->id,
                    array_map('intval', array_column($this->ion_auth->get_users_groups($id)->result_array(), 'id')), TRUE);
                if ($had_admin && !in_array((int) $admin_group->id, $selected, TRUE)
                    && ((int) $current->id === $id || $this->active_admin_count() <= 1)) {
                    $errors[] = 'No puedes retirar el último administrador activo ni tu propio acceso administrativo.';
                }
            }
            if (!$errors) {
                $data = [
                    'first_name' => $posted->first_name, 'last_name' => $posted->last_name,
                    'email' => strtolower(trim($posted->email)), 'phone' => $posted->phone,
                    'company' => $posted->company,
                ];
                if ($this->input->post('password') !== '') {
                    $data['password'] = (string) $this->input->post('password');
                }
                $this->db->trans_begin();
                $updated = $this->ion_auth->update($id, $data);
                if ($updated && $is_admin) {
                    $updated = $this->ion_auth->remove_from_group('', $id);
                    foreach ($selected as $group_id) {
                        $updated = $this->ion_auth->add_to_group($group_id, $id) && $updated;
                    }
                }
                if ($updated && $this->db->trans_status()) {
                    $this->db->trans_commit();
                    $this->session->set_flashdata('message', 'Usuario actualizado.');
                    redirect($is_admin ? 'auth' : '/');
                    return;
                }
                $this->db->trans_rollback();
                $errors[] = strip_tags($this->ion_auth->errors()) ?: 'No se pudo actualizar el usuario.';
            }
            $user = (object) array_merge((array) $user, (array) $posted);
        }
        $this->render_form('edit', $user, $selected, $errors, $is_admin);
    }

    public function activate($id)
    {
        $this->require_admin();
        $this->require_post_nonce();
        $user = $this->ion_auth->user((int) $id)->row();
        if (!$user) {
            show_404();
            return;
        }
        $this->ion_auth->activate((int) $id);
        $this->session->set_flashdata('message', 'Usuario activado.');
        redirect('auth');
    }

    public function deactivate($id)
    {
        $this->require_admin();
        $this->require_post_nonce();
        $id = (int) $id;
        $user = $this->ion_auth->user($id)->row();
        if (!$user) {
            show_404();
            return;
        }
        $current = $this->ion_auth->user()->row();
        if ((int) $current->id === $id || ($this->ion_auth->in_group('admin', $id) && $this->active_admin_count() <= 1)) {
            show_error('No puedes desactivar tu cuenta ni al último administrador activo.', 403);
            return;
        }
        $this->ion_auth->deactivate($id);
        $this->session->set_flashdata('message', 'Usuario desactivado.');
        redirect('auth');
    }

    private function validation_rules($creating, $id = 0)
    {
        $this->form_validation->set_rules('first_name', 'Nombre', 'trim|required|max_length[50]');
        $this->form_validation->set_rules('last_name', 'Apellido', 'trim|required|max_length[50]');
        $this->form_validation->set_rules('phone', 'Teléfono', 'trim|max_length[20]');
        $this->form_validation->set_rules('company', 'Empresa', 'trim|max_length[100]');
        $email = strtolower(trim((string) $this->input->post('email')));
        $existing = $this->db->get_where('users', ['email' => $email])->row();
        $email_rules = 'trim|required|valid_email|max_length[254]';
        if ($existing && ($creating || (int) $existing->id !== $id)) {
            $email_rules .= '|is_unique[users.email]';
        }
        $this->form_validation->set_rules('email', 'Correo', $email_rules);
        $password = (string) $this->input->post('password');
        if ($creating || $password !== '') {
            $this->form_validation->set_rules('password', 'Contraseña', 'required|min_length['
                . $this->app_settings['min_password_length'] . ']|matches[password_confirm]');
            $this->form_validation->set_rules('password_confirm', 'Confirmación', 'required');
        }
    }

    private function posted_user()
    {
        return (object) [
            'first_name' => trim((string) $this->input->post('first_name')),
            'last_name' => trim((string) $this->input->post('last_name')),
            'email' => trim((string) $this->input->post('email')),
            'phone' => trim((string) $this->input->post('phone')),
            'company' => trim((string) $this->input->post('company')),
        ];
    }

    private function group_ids(array &$errors)
    {
        $posted = $this->input->post('groups');
        if ($posted === NULL) {
            return [];
        }
        if (!is_array($posted)) {
            $errors[] = 'Selección de grupos inválida.';
            return [];
        }
        $ids = [];
        foreach ($posted as $value) {
            if (!is_scalar($value) || !ctype_digit((string) $value)) {
                $errors[] = 'Selección de grupos inválida.';
                return [];
            }
            $ids[] = (int) $value;
        }
        $ids = array_values(array_unique($ids));
        if ($ids) {
            $valid = $this->db->where_in('id', $ids)->count_all_results('groups');
            if ($valid !== count($ids)) {
                $errors[] = 'Hay grupos desconocidos.';
            }
        }
        return $ids;
    }

    private function render_form($mode, $user, array $selected, array $errors, $is_admin = TRUE)
    {
        $nonce = $this->nonce();
        $this->load->view('dashboard/user_form', [
            'mode' => $mode, 'user' => $user,
            'groups' => $is_admin ? $this->ion_auth->groups()->result() : [],
            'selected_group_ids' => $selected,
            'errors' => $errors, 'message' => '', 'settings' => $this->app_settings,
            'csrf_name' => $this->security->get_csrf_token_name(),
            'csrf_hash' => $this->security->get_csrf_hash(),
            'nonce_name' => $nonce['name'], 'nonce_hash' => $nonce['value'],
        ]);
    }

    private function nonce()
    {
        $name = 'auth_nonce';
        $value = bin2hex(random_bytes(20));
        $this->session->set_flashdata('admin_nonce', $value);
        return ['name' => $name, 'value' => $value];
    }

    private function valid_nonce()
    {
        $expected = $this->session->flashdata('admin_nonce');
        $given = $this->input->post('auth_nonce');
        return is_string($expected) && is_string($given) && hash_equals($expected, $given);
    }

    private function require_post_nonce()
    {
        if ($this->input->method(TRUE) !== 'POST' || !$this->valid_nonce()) {
            show_error('Solicitud inválida', 403);
            exit;
        }
    }

    private function require_admin()
    {
        if (!$this->ion_auth->logged_in() || !$this->ion_auth->is_admin()) {
            show_error('Acceso denegado', 403);
            exit;
        }
    }

    private function active_admin_count()
    {
        return (int) $this->db->from('users')
            ->join('users_groups', 'users_groups.user_id = users.id')
            ->join('groups', 'groups.id = users_groups.group_id')
            ->where('users.active', 1)->where('groups.name', 'admin')
            ->count_all_results();
    }
}
