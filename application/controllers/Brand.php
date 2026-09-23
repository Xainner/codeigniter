<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Brand extends MY_Controller
{
    public function logo()
    {
        if (!in_array($this->input->method(TRUE), ['GET', 'HEAD'], TRUE)) {
            show_404();
            return;
        }
        $filename = $this->app_settings['brand_logo'];
        if ($filename === '' || basename($filename) !== $filename
            || !preg_match('/^[a-f0-9]{32}\.(jpg|png|webp)$/D', $filename)) {
            show_404();
            return;
        }
        $path = APPPATH . '../storage/brand/' . $filename;
        if (!is_file($path)) {
            show_404();
            return;
        }
        $type = pathinfo($filename, PATHINFO_EXTENSION);
        $mime = ['jpg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp'][$type];
        $this->output->set_content_type($mime)->set_header('X-Content-Type-Options: nosniff');
        // The public URL stays constant when the logo changes. Revalidation
        // prevents browsers from showing the previous image after replacement.
        $this->output->set_header('Cache-Control: private, no-cache');
        $this->output->set_header('ETag: "' . $filename . '"');
        if (isset($_SERVER['HTTP_IF_NONE_MATCH']) && $_SERVER['HTTP_IF_NONE_MATCH'] === '"' . $filename . '"') {
            $this->output->set_status_header(304);
            return;
        }
        if ($this->input->method(TRUE) === 'GET') {
            $this->output->set_output(file_get_contents($path));
        }
    }
}
