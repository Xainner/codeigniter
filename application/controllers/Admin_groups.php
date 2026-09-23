<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Admin_groups extends MY_Controller
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
        $group = NULL;
        $errors = [];
        if ($this->input->method(TRUE) === 'POST') {
            $group = $this->posted_group(FALSE);
            $this->rules();
            if (!$this->form_validation->run()) {
                $errors[] = strip_tags(validation_errors());
            }
            if ($this->db->get_where('groups', ['name' => $group->name])->row()) {
                $errors[] = 'Ya existe un grupo con ese nombre.';
            }
            if (!$errors) {
                if ($this->ion_auth->create_group($group->name, $group->description)) {
                    $this->session->set_flashdata('message', 'Grupo creado.');
                    redirect('auth#groups');
                    return;
                }
                $errors[] = strip_tags($this->ion_auth->errors()) ?: 'No se pudo crear el grupo.';
            }
        }
        $this->render_form('create', $group, $errors);
    }

    public function edit($id)
    {
        $this->require_admin();
        $id = (int) $id;
        $group = $this->ion_auth->group($id)->row();
        if (!$group) {
            show_404();
            return;
        }
        $errors = [];
        if ($this->input->method(TRUE) === 'POST') {
            $posted = $this->posted_group(TRUE);
            $this->rules();
            if (!$this->form_validation->run()) {
                $errors[] = strip_tags(validation_errors());
            }
            if (in_array($group->name, ['admin', 'members'], TRUE) && $posted->name !== $group->name) {
                $errors[] = 'No se puede cambiar el nombre de un grupo principal.';
            }
            $existing = $this->db->get_where('groups', ['name' => $posted->name])->row();
            if ($existing && (int) $existing->id !== $id) {
                $errors[] = 'Ya existe un grupo con ese nombre.';
            }
            if (!$errors) {
                if ($this->ion_auth->update_group($id, $posted->name, ['description' => $posted->description])) {
                    $this->session->set_flashdata('message', 'Grupo actualizado.');
                    redirect('auth#groups');
                    return;
                }
                $errors[] = strip_tags($this->ion_auth->errors()) ?: 'No se pudo actualizar el grupo.';
            }
            $group = (object) array_merge((array) $group, (array) $posted);
        }
        $this->render_form('edit', $group, $errors);
    }

    private function posted_group($editing)
    {
        return (object) [
            'name' => trim((string) $this->input->post('group_name')),
            'description' => trim((string) $this->input->post($editing ? 'group_description' : 'description')),
        ];
    }

    private function rules()
    {
        $this->form_validation->set_rules('group_name', 'Nombre', 'trim|required|alpha_dash|max_length[20]');
        $description = $this->input->method(TRUE) === 'POST' && $this->input->post('group_description') !== NULL
            ? 'group_description' : 'description';
        $this->form_validation->set_rules($description, 'Descripción', 'trim|max_length[100]');
    }

    private function render_form($mode, $group, array $errors)
    {
        $this->load->view('dashboard/group_form', [
            'mode' => $mode, 'group' => $group, 'errors' => $errors,
            'message' => '', 'settings' => $this->app_settings,
            'csrf_name' => $this->security->get_csrf_token_name(),
            'csrf_hash' => $this->security->get_csrf_hash(),
        ]);
    }

    private function require_admin()
    {
        if (!$this->ion_auth->logged_in() || !$this->ion_auth->is_admin()) {
            show_error('Acceso denegado', 403);
            exit;
        }
    }
}
