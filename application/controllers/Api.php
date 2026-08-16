<?php
defined('BASEPATH') OR exit('No direct script access allowed');

use chriskacerguis\RestServer\RestController;

/**
 * Class Api
 *
 * Example REST controller. Requires application/config/rest.php (copied from
 * vendor/chriskacerguis/codeigniter-restserver/src/rest.php).
 *
 *   GET  /api/ping    -> health check (JSON)
 *
 * Extend this controller (or copy it) to build your own API endpoints.
 */
class Api extends RestController
{
	public function __construct()
	{
		parent::__construct();
	}

	/**
	 * Health check endpoint
	 *
	 * @return void
	 */
	public function ping_get()
	{
		$this->response([
			'status'  => TRUE,
			'message' => 'API online',
			'ci'      => CI_VERSION,
			'php'     => PHP_VERSION,
			'time'    => date('c'),
		], RestController::HTTP_OK);
	}
}
