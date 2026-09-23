<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Brand extends MY_Controller
{
    public function logo()
    {
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
        header('Content-Type: ' . $mime);
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: public, max-age=3600');
        readfile($path);
    }
}
