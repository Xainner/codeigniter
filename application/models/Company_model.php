<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Company_model extends CI_Model {

    public function __construct() {
        parent::__construct();
    }

    public function get_company() {
        try {
            $query = $this->db->get('company');
            $result = $query->row_array();
            if (!$result) {
                $result = [
                    'id' => 1,
                    'name' => 'Mi Empresa',
                    'logo' => '',
                    'description' => 'Descripción de la empresa',
                    'email' => 'info@miempresa.com',
                    'phone' => '+1234567890'
                ];
            }
        } catch (Exception $e) {
            $result = [
                'id' => 1,
                'name' => 'Mi Empresa',
                'logo' => '',
                'description' => 'Descripción de la empresa',
                'email' => 'info@miempresa.com',
                'phone' => '+1234567890'
            ];
        }
        return $result;
    }
}
