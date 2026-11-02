<?php defined('BASEPATH') OR exit('No direct script access allowed');

/** Read-only view of the administration and settings audit trail. */
class Admin_audit extends MY_Controller
{
    private const PER_PAGE = 25;

    public function __construct()
    {
        parent::__construct();
        $this->load_auth();
        $this->load->model('Admin_audit_model');
    }

    public function index()
    {
        $this->require_admin();
        $this->load->library('pagination');
        $total = $this->Admin_audit_model->count_entries();
        $page = max(1, (int) $this->input->get('page'));
        $page = min($page, max(1, (int) ceil($total / self::PER_PAGE)));
        $this->pagination->initialize([
            'base_url' => site_url('auth/audit'),
            'total_rows' => $total,
            'per_page' => self::PER_PAGE,
            'use_page_numbers' => TRUE,
            'page_query_string' => TRUE,
            'query_string_segment' => 'page',
        ]);
        $this->load->view('dashboard/audit', [
            'entries' => $this->Admin_audit_model->page(self::PER_PAGE, ($page - 1) * self::PER_PAGE),
            'total' => $total,
            'pagination' => $this->pagination->create_links(),
            'settings' => $this->app_settings,
        ]);
    }
}
