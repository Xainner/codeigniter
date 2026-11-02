<?php defined('BASEPATH') OR exit('No direct script access allowed');

/** Self-service two-factor authentication for the signed-in user. */
class Account_security extends MY_Controller
{
	/** Pending enrollment secret, kept sealed in the session until a code confirms it. */
	private const ENROLLMENT_KEY = 'mfa_enrollment';
	private const ENROLLMENT_PURPOSE = 'user_mfa.enrollment';

	public function __construct()
	{
		parent::__construct();
		$this->load_auth();
		$this->load->model(['User_mfa_model', 'Admin_audit_model', 'Rate_limiter_model']);
	}

	public function index()
	{
		$this->require_login();
		$this->render();
	}

	public function enable()
	{
		$user = $this->post_request_user();
		if ($this->User_mfa_model->enabled($user->id)) {
			redirect('auth/security');
			return;
		}
		$secret = $this->enrollment_secret(FALSE);
		$step = $secret === NULL ? NULL : $this->two_factor->matching_step($secret, (string) $this->input->post('code'));
		$error = $this->password_error($user);
		if ($error === NULL && $step === NULL) {
			$error = 'El código no coincide. Revisa la hora de tu teléfono y escribe el código actual.';
		}
		if ($error !== NULL) {
			$this->render([$error]);
			return;
		}
		$codes = $this->User_mfa_model->enable($user->id, $secret, $step);
		if ($codes === NULL) {
			$this->render(['No se pudo activar la verificación en dos pasos.']);
			return;
		}
		$this->session->unset_userdata(self::ENROLLMENT_KEY);
		$this->Admin_audit_model->record($user->id, 'user.mfa_enable', 'user', $user->id);
		$this->render([], 'Verificación en dos pasos activada. Guarda tus códigos de recuperación.', $codes);
	}

	public function disable()
	{
		$user = $this->post_request_user();
		$error = $this->password_error($user);
		if ($error === NULL && !$this->User_mfa_model->verify($user->id, (string) $this->input->post('code'))) {
			$error = 'Escribe un código de tu aplicación o un código de recuperación sin usar.';
		}
		if ($error !== NULL) {
			$this->render([$error]);
			return;
		}
		if ($this->User_mfa_model->disable($user->id)) {
			$this->Admin_audit_model->record($user->id, 'user.mfa_disable', 'user', $user->id);
			$this->session->set_flashdata('message', 'Verificación en dos pasos desactivada.');
		}
		redirect('auth/security');
	}

	public function recovery_codes()
	{
		$user = $this->post_request_user();
		$error = $this->password_error($user);
		if ($error === NULL && !$this->User_mfa_model->enabled($user->id)) {
			$error = 'La verificación en dos pasos no está activada.';
		}
		if ($error !== NULL) {
			$this->render([$error]);
			return;
		}
		$codes = $this->User_mfa_model->regenerate_recovery_codes($user->id);
		$this->render($codes === NULL ? ['No se pudieron generar los códigos.'] : [],
			$codes === NULL ? '' : 'Códigos nuevos generados. Los anteriores ya no sirven.', $codes ?? []);
	}

	private function render(array $errors = [], $message = '', array $recovery_codes = [])
	{
		$user = $this->ion_auth->user()->row();
		$status = $this->User_mfa_model->status($user->id);
		$data = [
			'user' => $user,
			'status' => $status,
			'remaining_codes' => $status ? $this->User_mfa_model->remaining_recovery_codes($user->id) : 0,
			'recovery_codes' => $recovery_codes,
			'errors' => $errors,
			'message' => $message !== '' ? $message : (string) $this->session->flashdata('message'),
			'is_admin' => $this->ion_auth->is_admin(),
			'settings' => $this->app_settings,
			'nonce_name' => 'auth_nonce',
			'nonce_hash' => $this->_get_csrf_nonce()['auth_nonce'],
		] + $this->csrf_data();
		if (!$status) {
			$secret = $this->enrollment_secret(TRUE);
			$uri = $this->two_factor->provisioning_uri($secret, $user->email, $this->app_settings['site_name']);
			$data['secret'] = $secret;
			$data['qr_code'] = $this->two_factor->qr_data_uri($uri);
		}
		$this->load->view('dashboard/security', $data);
	}

	/** The signed-in user for a state-changing request with a valid form nonce. */
	private function post_request_user()
	{
		$this->require_login();
		if ($this->input->method(TRUE) !== 'POST' || !$this->_valid_csrf_nonce()) {
			show_error('Solicitud inválida', 403);
		}
		return $this->ion_auth->user()->row();
	}

	/** NULL when the current password is right; otherwise the message to show. */
	private function password_error($user)
	{
		// Shares the budget of the current-password checks on the profile and password forms.
		if (!$this->Rate_limiter_model->allow('reauth-user', (string) $user->id, 5, 900)) {
			return 'Demasiados intentos. Espera unos minutos antes de volver a intentarlo.';
		}
		$password = (string) $this->input->post('current_password');
		$hash = $this->db->select('password')->get_where('users', ['id' => (int) $user->id])->row();
		return $hash && $this->ion_auth->verify_password($password, $hash->password, $user->email)
			? NULL : 'La contraseña actual no es correcta.';
	}

	private function enrollment_secret($create)
	{
		$this->load->library('secret_box');
		$sealed = $this->session->userdata(self::ENROLLMENT_KEY);
		$secret = is_string($sealed) ? $this->secret_box->open($sealed, self::ENROLLMENT_PURPOSE) : NULL;
		if ($secret === NULL && $create) {
			$secret = $this->two_factor->new_secret();
			$this->session->set_userdata(self::ENROLLMENT_KEY, $this->secret_box->seal($secret, self::ENROLLMENT_PURPOSE));
		}
		return $secret;
	}
}
