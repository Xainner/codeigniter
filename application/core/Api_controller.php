<?php defined('BASEPATH') OR exit('No direct script access allowed');

use chriskacerguis\RestServer\RestController;

/**
 * Base class for API controllers. Every method requires an X-API-Key header
 * unless it is listed in $public_methods. Requests never use the browser
 * session, which is why routes under api/ are excluded from CSRF checks.
 */
abstract class Api_controller extends RestController
{
    /** Methods such as 'ping_get' that answer without an API key. */
    protected $public_methods = [];

    /** Owner of the API key that authenticated the current request. */
    protected $api_user_id = NULL;

    public function _remap($object_called, $arguments = [])
    {
        $method = preg_replace('/\.[a-z0-9]+$/i', '', (string) $object_called) . '_' . $this->request->method;
        if (!in_array($method, $this->public_methods, TRUE)) {
            $this->load->database();
            $this->load->model('Api_key_model');
            $key = $this->Api_key_model->authenticate((string) $this->input->get_request_header('X-API-Key'));
            if ($key === NULL) {
                $this->response([
                    $this->config->item('rest_status_field_name') => FALSE,
                    $this->config->item('rest_message_field_name') => 'Clave de API ausente o no válida.',
                ], self::HTTP_UNAUTHORIZED);
            }
            $this->api_user_id = (int) $key->user_id;
        }
        parent::_remap($object_called, $arguments);
    }
}
