<?php defined('BASEPATH') OR exit('No direct script access allowed');

/** Loads administrator settings and the shared authentication helpers. */
class MY_Controller extends CI_Controller
{
    public $app_settings;

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->load->model('App_settings_model');
        $this->app_settings = $this->App_settings_model->all();
    }

    /**
     * Applies database settings to Ion Auth before constructing it. The SMTP
     * secret is decrypted only for controllers that may send mail.
     */
    protected function load_auth()
    {
        $this->config->load('ion_auth', TRUE);
        $ion = $this->config->item('ion_auth');
        $ion['email_activation'] = $this->app_settings['email_activation'];
        $ion['remember_users'] = $this->app_settings['remember_users'];
        $ion['min_password_length'] = $this->app_settings['min_password_length'];
        $ion['maximum_login_attempts'] = $this->app_settings['maximum_login_attempts'];
        $ion['lockout_time'] = $this->app_settings['lockout_time'];
        // Revalidate active sessions so deactivation takes effect promptly.
        $ion['recheck_timer'] = 1;
        $ion['site_title'] = $this->app_settings['site_name'];
        $ion['admin_email'] = $this->app_settings['smtp_from_email'];
        $ion['use_ci_email'] = TRUE;
        $ion['email_config'] = [
            'protocol' => 'smtp',
            'smtp_host' => $this->app_settings['smtp_host'],
            'smtp_port' => $this->app_settings['smtp_port'],
            'smtp_crypto' => $this->app_settings['smtp_crypto'],
            'smtp_user' => $this->app_settings['smtp_user'],
            'smtp_pass' => $this->App_settings_model->smtp_password(),
            'mailtype' => 'html',
            'charset' => 'utf-8',
            'newline' => "\r\n",
            'crlf' => "\r\n",
            'validate' => TRUE,
        ];
        $this->config->set_item('ion_auth', $ion);
        $this->load->library('ion_auth');
    }

    protected function require_admin()
    {
        if (!$this->ion_auth->logged_in() || !$this->ion_auth->is_admin()) {
            show_error('Acceso denegado', 403);
        }
    }

    /** Registers an account that is active immediately, whatever the public activation setting. */
    protected function register_active_user($email, $password, array $profile, array $group_ids)
    {
        $ion = $this->config->item('ion_auth');
        $this->config->set_item('ion_auth', ['email_activation' => FALSE] + $ion);
        try {
            return $this->ion_auth->register($email, $password, $email, $profile, $group_ids);
        } finally {
            $this->config->set_item('ion_auth', $ion);
        }
    }

    /** Session nonce that complements the CSRF cookie on sensitive forms. */
    protected function _get_csrf_nonce()
    {
        $value = bin2hex(random_bytes(20));
        $this->session->set_flashdata('auth_nonce', $value);
        return ['auth_nonce' => $value];
    }

    protected function _valid_csrf_nonce()
    {
        $expected = $this->session->flashdata('auth_nonce');
        $given = $this->input->post('auth_nonce');
        return is_string($expected) && is_string($given) && hash_equals($expected, $given);
    }

    protected function csrf_data()
    {
        return [
            'csrf_name' => $this->security->get_csrf_token_name(),
            'csrf_hash' => $this->security->get_csrf_hash(),
        ];
    }
}
