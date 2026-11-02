<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Class Auth
 * @property Ion_auth|Ion_auth_model $ion_auth        The ION Auth spark
 * @property CI_Form_validation      $form_validation The form validation library
 */
class Auth extends MY_Controller
{
	public $data = [];

	public function __construct()
	{
		parent::__construct();
		$this->load_auth();
		$this->load->library('form_validation');
		$this->load->helper(['url', 'language', 'form']);

		$this->form_validation->set_error_delimiters($this->config->item('error_start_delimiter', 'ion_auth'), $this->config->item('error_end_delimiter', 'ion_auth'));

		$this->lang->load('auth');
	}

	/**
	 * Redirect if needed, otherwise display the user list
	 */
	public function index()
	{
		$this->require_admin();
		$listing_queries_start = count($this->db->queries);
		$this->load->model('Admin_user_model');
		$this->load->library('pagination');
		$query = trim((string) $this->input->get('q', TRUE));
		$query = mb_substr($query, 0, 100);
		$total = $this->Admin_user_model->count($query);
		$page = max(1, (int) $this->input->get('page'));
		$page = min($page, max(1, (int) ceil($total / 20)));
		$this->pagination->initialize([
			'base_url' => site_url('auth'),
			'total_rows' => $total,
			'per_page' => 20,
			'use_page_numbers' => TRUE,
			'page_query_string' => TRUE,
			'query_string_segment' => 'page',
			'reuse_query_string' => TRUE,
		]);
		$this->data = [
			'title' => 'Usuarios',
			'users' => $this->Admin_user_model->page($query, 20, ($page - 1) * 20),
			'groups' => $this->ion_auth->groups()->result(),
			'pagination' => $this->pagination->create_links(),
			'query' => $query,
			'message' => strip_tags((string) $this->session->flashdata('message')),
			'settings' => $this->app_settings,
			'nonce_name' => 'auth_nonce',
			'nonce_hash' => $this->_get_csrf_nonce()['auth_nonce'],
		] + $this->csrf_data();
		if (ENVIRONMENT === 'testing') {
			$this->output->set_header('X-Query-Count: ' . (count($this->db->queries) - $listing_queries_start));
		}
		$this->_render_page('dashboard/index', $this->data);
	}

	/**
	 * Log the user in
	 */
	public function login()
	{
		$this->data['title'] = $this->lang->line('login_heading');

		// Check if request is AJAX
		$is_ajax = $this->input->is_ajax_request();

		// validate form input
		$this->form_validation->set_rules('identity', str_replace(':', '', $this->lang->line('login_identity_label')), 'required|max_length[254]');
		$this->form_validation->set_rules('password', str_replace(':', '', $this->lang->line('login_password_label')), 'required');

		if ($this->form_validation->run() === TRUE)
		{
			$identity = $this->input->post('identity');
			$identity = is_string($identity) ? $identity : '';
			$password = $this->input->post('password');
			$password = is_string($password) ? $password : '';

			if ($this->login_address_throttled())
			{
				$throttled = 'Demasiados intentos fallidos desde tu conexión. Espera unos minutos.';
				if ($is_ajax) {
					$this->output->set_status_header(429)->set_content_type('application/json', 'utf-8')
						->set_output(json_encode(['success' => false, 'message' => $throttled], JSON_UNESCAPED_UNICODE));
					return;
				}
				$this->session->set_flashdata('message', $throttled);
				redirect('auth/login');
			}
			$this->equalize_login_work($identity, $password);

			// check to see if the user is logging in
			// check for "remember me"
			$remember = $this->app_settings['remember_users'] && (bool) $this->input->post('remember');
			// With two-factor authentication the remember-me cookie waits for the second step.
			$this->load->model('User_mfa_model');
			$two_factor = $this->User_mfa_model->enabled_for_identity($identity);

			if ($this->ion_auth->login($identity, $password, $two_factor ? FALSE : $remember))
			{
				if ($two_factor)
				{
					$this->begin_two_factor($remember);
					$this->two_factor_response(TRUE, 'Escribe el código de tu aplicación de autenticación.', 200, 'auth/two_factor');
					return;
				}
				//if the login is successful
				if ($is_ajax) {
					$this->output->set_content_type('application/json', 'utf-8')->set_output(json_encode([
						'success' => true, 'message' => strip_tags($this->ion_auth->messages()),
						'redirect' => site_url($this->post_login_target()),
					]));
					return;
				} else {
					$this->session->set_flashdata('message', $this->ion_auth->messages());
					redirect($this->post_login_target());
				}
			}
			else
			{
				// if the login was un-successful
				if ($is_ajax) {
					$this->output->set_status_header(401)->set_content_type('application/json', 'utf-8')
						->set_output(json_encode(['success' => false, 'message' => strip_tags($this->ion_auth->errors())]));
					return;
				} else {
					$this->session->set_flashdata('message', $this->ion_auth->errors());
					redirect('auth/login');
				}
			}
		}
		else
		{
			// the user is not logging in so display the login page
			// set the flash data error message if there is one
			$this->data['message'] = (validation_errors()) ? validation_errors() : $this->session->flashdata('message');

			$this->data['identity'] = [
				'name' => 'identity',
				'id' => 'identity',
				'type' => 'text',
				'value' => $this->form_validation->set_value('identity'),
			];

			$this->data['password'] = [
				'name' => 'password',
				'id' => 'password',
				'type' => 'password',
			];

			if ($is_ajax) {
				$this->output->set_status_header(422)->set_content_type('application/json', 'utf-8')
					->set_output(json_encode(['success' => false, 'message' => strip_tags((string) $this->data['message'])]));
				return;
			} else {
				$this->_render_page('auth' . DIRECTORY_SEPARATOR . 'login', $this->data);
			}
		}
	}

	/**
	 * Second login step for accounts with two-factor authentication. The
	 * password step leaves the session signed out with a short-lived marker.
	 */
	public function two_factor()
	{
		$pending = $this->session->userdata('mfa_pending');
		if (!is_array($pending) || (int) ($pending['expires'] ?? 0) < time())
		{
			$this->session->unset_userdata('mfa_pending');
			if (is_array($pending))
			{
				$this->session->set_flashdata('message', 'La verificación caducó. Inicia sesión de nuevo.');
			}
			redirect('auth/login');
		}
		if ($this->input->method(TRUE) !== 'POST')
		{
			$this->data['title'] = 'Verificación en dos pasos';
			$this->data['message'] = $this->session->flashdata('message');
			$this->_render_page('auth/two_factor', $this->data);
			return;
		}

		$user_id = (int) $pending['user_id'];
		$this->load->model(['User_mfa_model', 'Rate_limiter_model']);
		if (!$this->Rate_limiter_model->allow('mfa-user', (string) $user_id, 5, 900))
		{
			$this->session->unset_userdata('mfa_pending');
			$this->two_factor_response(FALSE, 'Demasiados códigos incorrectos. Espera unos minutos e inicia sesión de nuevo.', 429, 'auth/login');
			return;
		}
		if (!$this->User_mfa_model->verify($user_id, (string) $this->input->post('code')))
		{
			$this->two_factor_response(FALSE, 'El código no es válido. Usa el código actual de tu aplicación o un código de recuperación.', 422, 'auth/two_factor');
			return;
		}
		$user = $this->db->select('id, email, last_login')->get_where('users', ['id' => $user_id, 'active' => 1])->row();
		$this->session->unset_userdata('mfa_pending');
		if (!$user)
		{
			$this->two_factor_response(FALSE, 'No se ha podido iniciar sesión', 401, 'auth/login');
			return;
		}
		$this->ion_auth->set_session($user);
		if (!empty($pending['remember']) && $this->app_settings['remember_users'])
		{
			$this->ion_auth->remember_user($user->email);
		}
		$this->two_factor_response(TRUE, 'Sesión iniciada.', 200, $this->post_login_target());
	}

	/** The password was right: sign the session out again until the second step succeeds. */
	private function begin_two_factor($remember)
	{
		$user_id = (int) $this->session->userdata('user_id');
		$this->session->unset_userdata([
			'identity', $this->config->item('identity', 'ion_auth'), 'email', 'user_id',
			'old_last_login', 'last_check', 'ion_auth_session_hash', 'auth_password_fp',
		]);
		$this->session->set_userdata('mfa_pending', ['user_id' => $user_id, 'remember' => (bool) $remember, 'expires' => time() + 300]);
	}

	private function two_factor_response($success, $message, $status, $target)
	{
		if ($this->input->is_ajax_request())
		{
			$this->output->set_status_header($status)->set_content_type('application/json', 'utf-8')
				->set_output(json_encode([
					'success' => $success, 'message' => $message,
					'redirect' => $success ? site_url($target) : NULL,
				], JSON_UNESCAPED_UNICODE));
			return;
		}
		$this->session->set_flashdata('message', $message);
		redirect($target);
	}

	/**
	 * Ion Auth locks an identity per address, so a single address could still
	 * try one password against every account. Cap its failures as well.
	 */
	private function login_address_throttled()
	{
		$this->load->model('Rate_limiter_model');
		$failures = $this->Rate_limiter_model->recent_login_failures($this->input->ip_address(), $this->app_settings['lockout_time']);
		return $failures >= 4 * $this->app_settings['maximum_login_attempts'];
	}

	/**
	 * Ion Auth verifies and then hashes the password when the account exists
	 * but only hashes it when it does not, so failed logins on real accounts
	 * took twice as long. Spend the missing hash so timing reveals nothing.
	 */
	private function equalize_login_work($identity, $password)
	{
		if (!$this->ion_auth->is_max_login_attempts_exceeded($identity) && !$this->ion_auth->identity_check($identity))
		{
			$this->ion_auth->hash_password($password);
		}
	}

	/**
	 * Log the user out
	 */
	public function logout()
	{
		if ($this->input->method(TRUE) !== 'POST') {
			show_404();
			return;
		}
		// Ion Auth's logout leaves the remember-me token valid in the database.
		$user_id = (int) $this->session->userdata('user_id');
		if ($user_id !== 0) {
			$this->revoke_remember_token($user_id);
		}
		$this->ion_auth->logout();

		// redirect them to the login page
		redirect('auth/login');
	}

	/**
	 * Change password
	 */
	public function change_password()
	{
		$this->require_login();

		$this->form_validation->set_rules('old', $this->lang->line('change_password_validation_old_password_label'), 'required');
		$account = $this->ion_auth->user()->row();
		$this->form_validation->set_rules('new', $this->lang->line('change_password_validation_new_password_label'), $this->new_password_rules(
			$this->config->item('min_password_length', 'ion_auth'), 'new_confirm', [$account->email, $account->first_name, $account->last_name]));
		$this->form_validation->set_rules('new_confirm', $this->lang->line('change_password_validation_new_password_confirm_label'), 'required');

		if ($this->form_validation->run() === FALSE)
		{
			$this->data['title'] = $this->lang->line('change_password_heading');
			$this->data['message'] = (validation_errors()) ? validation_errors() : $this->session->flashdata('message');
			$this->data['min_password_length'] = $this->config->item('min_password_length', 'ion_auth');
			$this->_render_page('auth' . DIRECTORY_SEPARATOR . 'change_password', $this->data);
		}
		else
		{
			$identity = $this->session->userdata('identity');

			// Shares the budget of the current-password check on the profile form.
			$this->load->model('Rate_limiter_model');
			if (!$this->Rate_limiter_model->allow('reauth-user', (string) $this->ion_auth->get_user_id(), 5, 900))
			{
				$this->session->set_flashdata('message', 'Demasiados intentos. Espera unos minutos antes de volver a intentarlo.');
				redirect('auth/change_password');
			}

			$change = $this->ion_auth->change_password($identity, $this->input->post('old'), $this->input->post('new'));

			if ($change)
			{
				//if the password was successfully changed
				$this->session->set_flashdata('message', $this->ion_auth->messages());
				$this->ion_auth->logout();
				redirect('auth/login');
			}
			else
			{
				$this->session->set_flashdata('message', $this->ion_auth->errors());
				redirect('auth/change_password');
			}
		}
	}

	/**
	 * Forgot password
	 */
	public function forgot_password()
	{
		$this->data['title'] = $this->lang->line('forgot_password_heading');
		$this->data['type'] = 'email';
		$this->data['identity_label'] = $this->lang->line('forgot_password_email_identity_label');
		$this->data['identity'] = ['name' => 'identity', 'id' => 'identity'];
		$this->data['message'] = $this->session->flashdata('message');

		if ($this->input->method(TRUE) === 'GET') {
			$this->_render_page('auth/forgot_password', $this->data);
			return;
		}
		if ($this->input->method(TRUE) !== 'POST') {
			show_404();
			return;
		}

		$this->form_validation->set_rules('identity', 'Correo', 'required|valid_email|max_length[254]');
		if (!$this->form_validation->run()) {
			$this->recovery_response(FALSE, 'Escribe un correo válido.', 422);
			return;
		}
		if (!$this->app_settings['smtp_configured']) {
			$this->recovery_response(FALSE, 'La recuperación no está disponible temporalmente.', 503);
			return;
		}

		$this->load->model('Rate_limiter_model');
		$identity = strtolower(trim((string) $this->input->post('identity')));
		$ip = $this->input->ip_address();
		if (!$this->Rate_limiter_model->allow('recovery-ip', $ip, 20, 3600)
			|| !$this->Rate_limiter_model->allow('recovery-target', $identity, 5, 3600)) {
			$this->recovery_response(FALSE, 'Espera antes de volver a solicitar instrucciones.', 429);
			return;
		}

		$user = $this->ion_auth->where('email', $identity)->users()->row();
		// Ion Auth refuses inactive accounts; answer them like unknown addresses.
		$sent = $user && (int) $user->active === 1
			? $this->ion_auth->forgotten_password($identity) : $this->recovery_transport_probe();
		if (!$sent) {
			log_message('error', 'Password recovery transport failed');
			$this->recovery_response(FALSE, 'La recuperación no está disponible temporalmente.', 503);
			return;
		}
		// Never disclose whether the address exists.
		$this->recovery_response(TRUE, 'Si existe una cuenta, recibirá instrucciones por correo.', 200);
	}

	private function recovery_transport_probe()
	{
		// Match the password-token work and open the same authenticated SMTP
		// session, but send nothing: unknown addresses must not become a way
		// to flood the sender mailbox or spend the SMTP quota.
		password_hash(bin2hex(random_bytes(20)), PASSWORD_BCRYPT, ['cost' => 12]);
		$this->email->clear(TRUE);
		$this->email->initialize($this->config->item('email_config', 'ion_auth'));
		return $this->email->verify_transport();
	}

	private function recovery_response($success, $message, $status)
	{
		if ($this->input->is_ajax_request()) {
			$this->output->set_status_header($status)
				->set_content_type('application/json', 'utf-8')
				->set_output(json_encode([
					'success' => $success,
					'message' => $message,
					'redirect' => $success ? site_url('auth/login') : NULL,
				], JSON_UNESCAPED_UNICODE));
			return;
		}
		$this->session->set_flashdata('message', $message);
		redirect($success ? 'auth/login' : 'auth/forgot_password');
	}

	/**
	 * Reset password - final step for forgotten password
	 *
	 * @param string|null $code The reset code
	 */
	public function reset_password($code = NULL)
	{
		if (!$code)
		{
			show_404();
		}

		$this->data['title'] = $this->lang->line('reset_password_heading');
		
		$user = $this->ion_auth->forgotten_password_check($code);

		if ($user)
		{
			// if the code is valid then display the password reset form

			$this->form_validation->set_rules('new', $this->lang->line('reset_password_validation_new_password_label'), $this->new_password_rules(
				$this->config->item('min_password_length', 'ion_auth'), 'new_confirm', [$user->email, $user->first_name, $user->last_name]));
			$this->form_validation->set_rules('new_confirm', $this->lang->line('reset_password_validation_new_password_confirm_label'), 'required');

			if ($this->form_validation->run() === FALSE)
			{
				// display the form

				// set the flash data error message if there is one
				$this->data['message'] = (validation_errors()) ? validation_errors() : $this->session->flashdata('message');

				$this->data['min_password_length'] = $this->config->item('min_password_length', 'ion_auth');
				$this->data['new_password'] = [
					'name' => 'new',
					'id' => 'new',
					'type' => 'password',
					'pattern' => '^.{' . $this->data['min_password_length'] . '}.*$',
				];
				$this->data['new_password_confirm'] = [
					'name' => 'new_confirm',
					'id' => 'new_confirm',
					'type' => 'password',
					'pattern' => '^.{' . $this->data['min_password_length'] . '}.*$',
				];
				$this->data['user_id'] = [
					'name' => 'user_id',
					'id' => 'user_id',
					'type' => 'hidden',
					'value' => $user->id,
				];
				$this->data['csrf'] = $this->_get_csrf_nonce();
				$this->data['code'] = $code;

				// render
				$this->_render_page('auth' . DIRECTORY_SEPARATOR . 'reset_password', $this->data);
			}
			else
			{
				$identity = $user->{$this->config->item('identity', 'ion_auth')};

				// do we have a valid request?
				if ($this->_valid_csrf_nonce() === FALSE || $user->id != $this->input->post('user_id'))
				{

					// something fishy might be up
					$this->ion_auth->clear_forgotten_password_code($identity);

					show_error($this->lang->line('error_csrf'));

				}
				else
				{
					// finally change the password
					$change = $this->ion_auth->reset_password($identity, $this->input->post('new'));

					if ($change)
					{
						// if the password was successfully changed
						$this->session->set_flashdata('message', $this->ion_auth->messages());
						redirect('auth/login');
					}
					else
					{
						$this->session->set_flashdata('message', $this->ion_auth->errors());
						redirect('auth/reset_password/' . $code);
					}
				}
			}
		}
		else
		{
			// if the code is invalid then send them back to the forgot password page
			$this->session->set_flashdata('message', $this->ion_auth->errors());
			redirect('auth/forgot_password');
		}
	}

	/**
	 * Activate the user
	 *
	 * @param int         $id   The user ID
	 * @param string|bool $code The activation code
	 */
	public function activate($id, $code = FALSE)
	{
		if ($code === FALSE) {
			show_404();
			return;
		}
		if ($this->input->method(TRUE) === 'GET') {
			$this->load->view('auth/activate_confirm', [
				'id' => (int) $id, 'code' => (string) $code,
				'csrf_name' => $this->security->get_csrf_token_name(),
				'csrf_hash' => $this->security->get_csrf_hash(),
			]);
			return;
		}
		if ($this->input->method(TRUE) !== 'POST') {
			show_404();
			return;
		}
		// Ion Auth's activate() compares the id strictly with the string mysqli
		// returns, so an integer id never matched. Check the code here and
		// activate the account it belongs to.
		$user = $this->ion_auth->get_user_by_activation_code((string) $code);
		if ($user && (int) $user->id === (int) $id && $this->ion_auth->activate($user->id)) {
			$this->session->set_flashdata('message', 'Cuenta activada. Ya puedes iniciar sesión.');
			redirect('auth/login');
			return;
		}
		$this->session->set_flashdata('message', 'El enlace de activación no es válido o expiró.');
		redirect('auth/login');
	}

	/**
	 * Register a new user
	 */
	public function register()
	{
		if (!$this->app_settings['public_registration']) {
			show_404();
			return;
		}
		$this->data['title'] = $this->lang->line('create_user_heading');
		$this->data['identity_column'] = 'email';
		$this->data['min_password_length'] = $this->app_settings['min_password_length'];
		$this->data['message'] = $this->session->flashdata('message');
		if ($this->input->method(TRUE) === 'GET') {
			$this->_render_page('auth/register', $this->data);
			return;
		}
		if ($this->input->method(TRUE) !== 'POST') {
			show_404();
			return;
		}
		if ($this->app_settings['email_activation'] && !$this->app_settings['smtp_configured']) {
			$this->register_response(FALSE, 'El registro no está disponible temporalmente.', 503);
			return;
		}
		$this->load->model('Rate_limiter_model');
		if (!$this->Rate_limiter_model->allow('register-ip', $this->input->ip_address(), 10, 3600)) {
			$this->register_response(FALSE, 'Espera antes de crear otra cuenta.', 429);
			return;
		}
		$this->form_validation->set_rules('first_name', 'Nombre', 'trim|required|max_length[50]');
		$this->form_validation->set_rules('last_name', 'Apellido', 'trim|required|max_length[50]');
		$this->form_validation->set_rules('email', 'Correo', 'trim|required|valid_email|max_length[254]|is_unique[users.email]');
		$this->form_validation->set_rules('phone', 'Teléfono', 'trim|max_length[20]');
		$this->form_validation->set_rules('company', 'Empresa', 'trim|max_length[100]');
		$this->form_validation->set_rules('password', 'Contraseña', $this->new_password_rules(
			$this->app_settings['min_password_length'], 'password_confirm', $this->input->post(['email', 'first_name', 'last_name', 'company'])));
		$this->form_validation->set_rules('password_confirm', 'Confirmación', 'required');
		if (!$this->form_validation->run()) {
			$this->register_response(FALSE, strip_tags(validation_errors()), 422);
			return;
		}
		$email = strtolower(trim((string) $this->input->post('email')));
		$data = [
			'first_name' => trim((string) $this->input->post('first_name')),
			'last_name' => trim((string) $this->input->post('last_name')),
			'company' => trim((string) $this->input->post('company')),
			'phone' => trim((string) $this->input->post('phone')),
		];
		$this->db->trans_begin();
		$id = $this->ion_auth->register($email, (string) $this->input->post('password'), $email, $data);
		if (!$id || !$this->db->trans_status()) {
			$this->db->trans_rollback();
			$this->register_response(FALSE, 'No se pudo crear la cuenta.', 503);
			return;
		}
		$this->db->trans_commit();
		$this->register_response(TRUE, $this->app_settings['email_activation']
			? 'Revisa tu correo para activar la cuenta.' : 'Cuenta creada.', 200);
	}

	private function register_response($success, $message, $status)
	{
		if ($this->input->is_ajax_request()) {
			$this->output->set_status_header($status)
				->set_content_type('application/json', 'utf-8')
				->set_output(json_encode([
					'success' => $success, 'message' => $message,
					'redirect' => $success ? site_url('auth/login') : NULL,
				], JSON_UNESCAPED_UNICODE));
			return;
		}
		$this->session->set_flashdata('message', $message);
		redirect($success ? 'auth/login' : 'auth/register');
	}

	/**
	 * @param string     $view
	 * @param array|null $data
	 * @param bool       $returnhtml
	 *
	 * @return mixed
	 */
	public function _render_page($view, $data = NULL, $returnhtml = FALSE)//I think this makes more sense
	{

		$viewdata = (empty($data)) ? $this->data : $data;

		$view_html = $this->load->view($view, $viewdata, $returnhtml);

		// This will return html on 3rd argument being true
		if ($returnhtml)
		{
			return $view_html;
		}
	}

}
