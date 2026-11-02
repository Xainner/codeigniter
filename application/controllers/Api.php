<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . 'core/Api_controller.php';

/**
 * Example REST controller. Methods are named <resource>_<verb>; every method
 * needs an X-API-Key header (created at /auth/api-keys) unless it is listed
 * in $public_methods.
 *
 *   GET  /api/ping  -> public health check
 *   GET  /api/me    -> owner of the API key
 */
class Api extends Api_controller
{
	protected $public_methods = ['ping_get'];

	/**
	 * Health check endpoint
	 *
	 * @return void
	 */
	public function ping_get()
	{
		$this->response([
			'status' => 'ok',
		], self::HTTP_OK);
	}

	/**
	 * The user who owns the API key of this request
	 *
	 * @return void
	 */
	public function me_get()
	{
		$user = $this->db->select('id, email, first_name, last_name')
			->get_where('users', ['id' => $this->api_user_id])->row();
		$this->response([
			'id' => (int) $user->id,
			'email' => $user->email,
			'first_name' => $user->first_name,
			'last_name' => $user->last_name,
		], self::HTTP_OK);
	}
}
