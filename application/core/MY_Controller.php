<?php defined('BASEPATH') OR exit('No direct script access allowed');

/** Loads administrator settings before Ion Auth is constructed. */
class MY_Controller extends CI_Controller
{
    public $app_settings;

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->load->model('App_settings_model');
        $this->app_settings = $this->App_settings_model->all();

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
    }
}
