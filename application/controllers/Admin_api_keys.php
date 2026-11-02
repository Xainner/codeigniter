<?php defined('BASEPATH') OR exit('No direct script access allowed');

/** API keys for the REST endpoints, owned by the administrator who creates them. */
class Admin_api_keys extends MY_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load_auth();
		$this->load->library('form_validation');
		$this->load->model(['Api_key_model', 'Admin_audit_model']);
	}

	public function index()
	{
		$this->require_admin();
		$this->render();
	}

	public function create()
	{
		$this->require_post();
		$this->form_validation->set_rules('name', 'Nombre', 'trim|required|max_length[80]');
		if (!$this->form_validation->run()) {
			$this->render([strip_tags(validation_errors())]);
			return;
		}
		$actor = (int) $this->ion_auth->get_user_id();
		$key = $this->Api_key_model->create($actor, (string) $this->input->post('name'));
		if ($key === NULL) {
			$this->render(['No se pudo crear la clave.']);
			return;
		}
		$this->Admin_audit_model->record($actor, 'api_key.create', 'api_key', $key['id']);
		$this->render([], 'Clave creada. Cópiala ahora: no volverá a mostrarse.', $key['token']);
	}

	public function revoke($id)
	{
		$this->require_post();
		if ($this->Api_key_model->revoke((int) $id)) {
			$this->Admin_audit_model->record($this->ion_auth->get_user_id(), 'api_key.revoke', 'api_key', (int) $id);
			$this->session->set_flashdata('message', 'Clave revocada.');
		}
		redirect('auth/api-keys');
	}

	private function require_post()
	{
		$this->require_admin();
		if ($this->input->method(TRUE) !== 'POST' || !$this->_valid_csrf_nonce()) {
			show_error('Solicitud inválida', 403);
		}
	}

	private function render(array $errors = [], $message = '', $new_token = '')
	{
		$this->load->view('dashboard/api_keys', [
			'keys' => $this->Api_key_model->all(),
			'errors' => $errors,
			'message' => $message !== '' ? $message : (string) $this->session->flashdata('message'),
			'new_token' => $new_token,
			'settings' => $this->app_settings,
			'nonce_name' => 'auth_nonce',
			'nonce_hash' => $this->_get_csrf_nonce()['auth_nonce'],
		] + $this->csrf_data());
	}
}
