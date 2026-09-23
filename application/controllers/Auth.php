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
		$this->load->library(['ion_auth', 'form_validation']);
		$this->load->helper(['url', 'language']);

		$this->form_validation->set_error_delimiters($this->config->item('error_start_delimiter', 'ion_auth'), $this->config->item('error_end_delimiter', 'ion_auth'));

		$this->lang->load('auth');
	}

	/**
	 * Redirect if needed, otherwise display the user list
	 */
	public function index()
	{
		if (!$this->ion_auth->logged_in())
		{
			// redirect them to the login page
			redirect('auth/login', 'refresh');
		}
		else if (!$this->ion_auth->is_admin()) // remove this elseif if you want to enable this for non-admins
		{
			// redirect them to the home page because they must be an administrator to view this
			show_error('You must be an administrator to view this page.', 403);
		}
		else
		{
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
				'csrf_name' => $this->security->get_csrf_token_name(),
				'csrf_hash' => $this->security->get_csrf_hash(),
				'nonce_name' => 'auth_nonce',
				'nonce_hash' => bin2hex(random_bytes(20)),
			];
			$this->session->set_flashdata('admin_nonce', $this->data['nonce_hash']);
			if (ENVIRONMENT === 'testing') {
				$this->output->set_header('X-Query-Count: ' . count($this->db->queries));
			}
			$this->_render_page('dashboard/index', $this->data);
		}
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
		$this->form_validation->set_rules('identity', str_replace(':', '', $this->lang->line('login_identity_label')), 'required');
		$this->form_validation->set_rules('password', str_replace(':', '', $this->lang->line('login_password_label')), 'required');

		if ($this->form_validation->run() === TRUE)
		{
			// check to see if the user is logging in
			// check for "remember me"
			$remember = $this->app_settings['remember_users'] && (bool) $this->input->post('remember');

			if ($this->ion_auth->login($this->input->post('identity'), $this->input->post('password'), $remember))
			{
				//if the login is successful
				if ($is_ajax) {
					$this->output->set_content_type('application/json', 'utf-8')->set_output(json_encode([
						'success' => true, 'message' => strip_tags($this->ion_auth->messages()),
						'redirect' => site_url($this->ion_auth->is_admin() ? 'auth' : ''),
					]));
					return;
				} else {
					$this->session->set_flashdata('message', $this->ion_auth->messages());
					redirect($this->ion_auth->is_admin() ? 'auth' : '/', 'refresh');
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
					redirect('auth/login', 'refresh'); // use redirects instead of loading views for compatibility with MY_Controller libraries
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
	 * Log the user out
	 */
	public function logout()
	{
		if ($this->input->method(TRUE) !== 'POST') {
			show_404();
			return;
		}
		$this->data['title'] = "Logout";

		// log the user out
		$this->ion_auth->logout();

		// redirect them to the login page
		redirect('auth/login');
	}

	/**
	 * Change password
	 */
	public function change_password()
	{
		$this->form_validation->set_rules('old', $this->lang->line('change_password_validation_old_password_label'), 'required');
		$this->form_validation->set_rules('new', $this->lang->line('change_password_validation_new_password_label'), 'required|min_length[' . $this->config->item('min_password_length', 'ion_auth') . ']|matches[new_confirm]');
		$this->form_validation->set_rules('new_confirm', $this->lang->line('change_password_validation_new_password_confirm_label'), 'required');

		if (!$this->ion_auth->logged_in())
		{
			redirect('auth/login', 'refresh');
		}

		$user = $this->ion_auth->user()->row();

		if ($this->form_validation->run() === FALSE)
		{
			// display the form
			// set the flash data error message if there is one
			$this->data['message'] = (validation_errors()) ? validation_errors() : $this->session->flashdata('message');

			$this->data['min_password_length'] = $this->config->item('min_password_length', 'ion_auth');
			$this->data['old_password'] = [
				'name' => 'old',
				'id' => 'old',
				'type' => 'password',
			];
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

			// render
			$this->_render_page('auth' . DIRECTORY_SEPARATOR . 'change_password', $this->data);
		}
		else
		{
			$identity = $this->session->userdata('identity');

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
				redirect('auth/change_password', 'refresh');
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
		$sent = $user ? $this->ion_auth->forgotten_password($identity) : $this->recovery_transport_probe();
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
		// Match the password-token work, then exercise the same SMTP transport
		// without sending mail to an address that is not a registered account.
		password_hash(bin2hex(random_bytes(20)), PASSWORD_BCRYPT, ['cost' => 12]);
		$this->email->clear(TRUE);
		$this->email->initialize($this->config->item('email_config', 'ion_auth'));
		$this->email->from($this->app_settings['smtp_from_email'], $this->app_settings['smtp_from_name']);
		$this->email->to($this->app_settings['smtp_from_email']);
		$this->email->subject('Comprobación de correo');
		$this->email->message('Comprobación del servicio de recuperación.');
		return $this->email->send();
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

			$this->form_validation->set_rules('new', $this->lang->line('reset_password_validation_new_password_label'), 'required|min_length[' . $this->config->item('min_password_length', 'ion_auth') . ']|matches[new_confirm]');
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
			redirect("auth/forgot_password", 'refresh');
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
		if ($this->ion_auth->activate((int) $id, $code)) {
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
		$this->form_validation->set_rules('password', 'Contraseña', 'required|min_length[' . $this->app_settings['min_password_length'] . ']|matches[password_confirm]');
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
	 * @return array A CSRF key-value pair
	 */
	public function _get_csrf_nonce()
	{
		$key = 'auth_nonce';
		$value = bin2hex(random_bytes(20));
		$this->session->set_flashdata('csrfkey', $key);
		$this->session->set_flashdata('csrfvalue', $value);

		return [$key => $value];
	}

	/**
	 * @return bool Whether the posted CSRF token matches
	 */
	public function _valid_csrf_nonce(){
		$key = $this->session->flashdata('csrfkey');
		$expected = $this->session->flashdata('csrfvalue');
		$given = is_string($key) ? $this->input->post($key) : NULL;
		if (is_string($expected) && is_string($given) && hash_equals($expected, $given))
		{
			return TRUE;
		}
			return FALSE;
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
