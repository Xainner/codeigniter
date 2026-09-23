<?php defined('BASEPATH') OR exit('No direct script access allowed');

/** One-time browser setup. The secret is supplied by the operator, never in a URL. */
class Setup extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->library(['ion_auth', 'form_validation']);
        $this->load->helper(['url', 'form']);
    }

    public function index()
    {
        $token = getenv('APP_SETUP_TOKEN');
        if (!is_string($token) || strlen($token) < 32 || $this->App_settings_model->setup_completed()
            || $this->db->count_all('users') !== 0) {
            show_404();
            return;
        }

        $error = '';
        if ($this->input->method(TRUE) === 'POST') {
            $this->form_validation->set_rules('setup_token', 'Clave de instalación', 'required');
            $this->form_validation->set_rules('first_name', 'Nombre', 'trim|required|max_length[50]');
            $this->form_validation->set_rules('last_name', 'Apellido', 'trim|required|max_length[50]');
            $this->form_validation->set_rules('email', 'Correo', 'trim|required|valid_email|max_length[254]');
            $this->form_validation->set_rules('password', 'Contraseña', 'required|min_length[12]|matches[password_confirm]');
            $this->form_validation->set_rules('password_confirm', 'Confirmación', 'required');

            if ($this->form_validation->run() && hash_equals($token, (string) $this->input->post('setup_token'))) {
                if ($this->create_admin()) {
                    $this->session->set_flashdata('message', 'Administrador creado. Inicia sesión.');
                    redirect('auth/login');
                    return;
                }
                $error = 'No se pudo crear el administrador. Revisa la configuración e inténtalo otra vez.';
            } else {
                $error = 'Revisa los datos y la clave de instalación.';
            }
        }

        $this->load->view('auth/setup', [
            'error' => $error,
            'csrf_name' => $this->security->get_csrf_token_name(),
            'csrf_hash' => $this->security->get_csrf_hash(),
        ]);
    }

    private function create_admin()
    {
        $this->db->trans_begin();
        $state = $this->db->query("SELECT `value` FROM `app_settings` WHERE `name` = 'setup_completed' FOR UPDATE")->row();
        if (!$state || $state->value !== '0' || $this->db->count_all('users') !== 0) {
            $this->db->trans_rollback();
            return FALSE;
        }
        $admin = $this->db->get_where('groups', ['name' => 'admin'])->row();
        if (!$admin) {
            $this->db->trans_rollback();
            return FALSE;
        }

        // The first admin must be active without depending on SMTP setup.
        $ion = $this->config->item('ion_auth');
        $initial = $ion;
        $ion['email_activation'] = FALSE;
        $this->config->set_item('ion_auth', $ion);
        $email = strtolower(trim((string) $this->input->post('email')));
        $id = $this->ion_auth->register($email, (string) $this->input->post('password'), $email, [
            'first_name' => $this->input->post('first_name'),
            'last_name' => $this->input->post('last_name'),
        ], [(int) $admin->id]);
        $this->config->set_item('ion_auth', $initial);

        if (!$id || !$this->App_settings_model->complete_setup() || $this->db->trans_status() === FALSE) {
            $this->db->trans_rollback();
            return FALSE;
        }
        $this->db->trans_commit();
        return TRUE;
    }
}
