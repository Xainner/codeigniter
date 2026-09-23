<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Admin_settings extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->library('ion_auth');
        $this->load->helper(['url', 'form']);
    }

    public function index()
    {
        $this->require_admin();
        $errors = [];
        if ($this->input->method(TRUE) === 'POST') {
            $values = $this->validated_input($errors);
            $new_logo = '';
            $previous_logo = (string) $this->app_settings['brand_logo'];
            if (!$errors && !empty($_FILES['brand_logo_file']['name'])) {
                try {
                    $new_logo = $this->save_logo($_FILES['brand_logo_file']);
                    $values['brand_logo'] = $new_logo;
                } catch (RuntimeException $exception) {
                    $errors[] = $exception->getMessage();
                }
            }
            if (!$errors) {
                $actor = $this->ion_auth->user()->row();
                try {
                    $saved = $this->App_settings_model->save($values, $actor->id);
                } catch (Throwable $exception) {
                    log_message('error', 'Could not save application settings');
                    $saved = FALSE;
                }
                if ($saved) {
                    if ($new_logo !== '' && $previous_logo !== $new_logo) {
                        $this->delete_logo($previous_logo);
                    }
                    $this->session->set_flashdata('message', 'Ajustes guardados.');
                    redirect('auth/settings');
                    return;
                }
                $this->delete_logo($new_logo);
                $errors[] = 'No se pudieron guardar los ajustes.';
            }
        }
        $this->load->view('dashboard/settings', [
            'title' => 'Ajustes',
            'settings' => $this->App_settings_model->all(),
            'errors' => $errors,
            'message' => (string) $this->session->flashdata('message'),
            'csrf_name' => $this->security->get_csrf_token_name(),
            'csrf_hash' => $this->security->get_csrf_hash(),
        ]);
    }

    public function test_email()
    {
        $this->require_admin();
        if ($this->input->method(TRUE) !== 'POST') {
            show_404();
            return;
        }
        $settings = $this->App_settings_model->all();
        if ($settings['smtp_host'] === '' || $settings['smtp_from_email'] === '') {
            $this->session->set_flashdata('message', 'Configura y guarda SMTP antes de probarlo.');
            redirect('auth/settings');
            return;
        }
        $this->load->library('email');
        $email_config = $this->config->item('email_config', 'ion_auth');
        $this->email->clear(TRUE);
        $this->email->initialize($email_config);
        $this->email->from($settings['smtp_from_email'], $settings['smtp_from_name']);
        $this->email->to($this->ion_auth->user()->row()->email);
        $this->email->subject('Prueba de correo de ' . $settings['site_name']);
        $this->email->message('La configuración SMTP está funcionando.');
        if ($this->email->send()) {
            try {
                $saved = $this->App_settings_model->save(['smtp_verified' => '1'], $this->ion_auth->user()->row()->id);
            } catch (Throwable $exception) {
                $saved = FALSE;
            }
            if ($saved) {
                $this->session->set_flashdata('message', 'Correo de prueba aceptado por el servidor SMTP.');
            } else {
                log_message('error', 'SMTP test succeeded but verification status could not be saved');
                $this->session->set_flashdata('message', 'El correo se envió, pero no se pudo guardar la verificación SMTP.');
            }
        } else {
            log_message('error', 'SMTP test failed');
            $this->session->set_flashdata('message', 'No se pudo enviar el correo de prueba.');
        }
        redirect('auth/settings');
    }

    private function require_admin()
    {
        if (!$this->ion_auth->logged_in() || !$this->ion_auth->is_admin()) {
            show_error('Acceso denegado', 403);
            exit;
        }
    }

    private function validated_input(array &$errors)
    {
        $post = $this->input->post(NULL, FALSE);
        $values = [];
        foreach (['public_registration', 'email_activation', 'remember_users'] as $name) {
            $values[$name] = isset($post[$name]) && $post[$name] === '1' ? '1' : '0';
        }
        foreach (['min_password_length' => [12, 128], 'maximum_login_attempts' => [3, 10],
            'lockout_time' => [60, 3600], 'smtp_port' => [1, 65535]] as $name => $range) {
            $raw = isset($post[$name]) ? (string) $post[$name] : '';
            if (!ctype_digit($raw) || (int) $raw < $range[0] || (int) $raw > $range[1]) {
                $errors[] = 'Valor inválido: ' . $name;
            } else {
                $values[$name] = $raw;
            }
        }
        foreach (['site_name' => 80, 'smtp_host' => 253, 'smtp_user' => 254,
            'smtp_from_email' => 254, 'smtp_from_name' => 80] as $name => $max) {
            $value = trim((string) ($post[$name] ?? ''));
            if (mb_strlen($value) > $max || ($name === 'site_name' && $value === '')) {
                $errors[] = 'Valor inválido: ' . $name;
            }
            $values[$name] = $value;
        }
        if ($values['smtp_from_email'] !== '' && !filter_var($values['smtp_from_email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Correo remitente inválido.';
        }
        $color = trim((string) ($post['brand_color'] ?? ''));
        if (!preg_match('/^#[0-9a-fA-F]{6}$/D', $color)) {
            $errors[] = 'El color debe tener formato #RRGGBB.';
        }
        $values['brand_color'] = $color;
        $crypto = (string) ($post['smtp_crypto'] ?? '');
        if (!in_array($crypto, ['tls', 'ssl'], TRUE)) {
            $errors[] = 'Elige TLS o SSL para SMTP.';
        }
        $values['smtp_crypto'] = $crypto;
        $password = (string) ($post['smtp_password'] ?? '');
        if (strlen($password) > 1024) {
            $errors[] = 'La clave SMTP es demasiado larga.';
        }
        if ($password !== '') {
            $values['smtp_password'] = $password;
        }
        $current = $this->app_settings;
        foreach (['smtp_host', 'smtp_port', 'smtp_crypto', 'smtp_user', 'smtp_from_email', 'smtp_from_name'] as $field) {
            if (isset($values[$field]) && (string) $values[$field] !== (string) $current[$field]) {
                $values['smtp_verified'] = '0';
                break;
            }
        }
        if ($password !== '') {
            $values['smtp_verified'] = '0';
        }
        if ($values['public_registration'] === '1' && $values['email_activation'] === '1'
            && (!$current['smtp_configured'] || isset($values['smtp_verified']))) {
            $errors[] = 'Prueba SMTP antes de habilitar registro con activación por correo.';
        }
        return $values;
    }

    private function save_logo(array $file)
    {
        if ($file['error'] !== UPLOAD_ERR_OK || $file['size'] > 1024 * 1024) {
            throw new RuntimeException('El logo debe medir como máximo 1 MB.');
        }
        $info = @getimagesize($file['tmp_name']);
        if (!$info || $info[0] < 1 || $info[1] < 1 || $info[0] > 2048 || $info[1] > 2048) {
            throw new RuntimeException('Dimensiones de logo inválidas.');
        }
        $types = [IMAGETYPE_JPEG => ['imagecreatefromjpeg', 'imagejpeg', 'jpg'],
            IMAGETYPE_PNG => ['imagecreatefrompng', 'imagepng', 'png'],
            IMAGETYPE_WEBP => ['imagecreatefromwebp', 'imagewebp', 'webp']];
        if (!isset($types[$info[2]])) {
            throw new RuntimeException('Solo se admiten logos JPEG, PNG o WebP.');
        }
        [$decode, $encode, $extension] = $types[$info[2]];
        $image = @$decode($file['tmp_name']);
        if (!$image) {
            throw new RuntimeException('No se pudo leer el logo.');
        }
        $directory = APPPATH . '../storage/brand';
        if (!is_dir($directory) && !mkdir($directory, 0700, TRUE)) {
            imagedestroy($image);
            throw new RuntimeException('No se pudo guardar el logo.');
        }
        $filename = bin2hex(random_bytes(16)) . '.' . $extension;
        $path = $directory . '/' . $filename;
        $ok = $encode($image, $path);
        imagedestroy($image);
        if (!$ok) {
            @unlink($path);
            throw new RuntimeException('No se pudo guardar el logo.');
        }
        @chmod($path, 0600);
        return $filename;
    }

    private function delete_logo($filename)
    {
        if (!is_string($filename) || !preg_match('/^[a-f0-9]{32}\.(jpg|png|webp)$/D', $filename)) {
            return;
        }
        $path = APPPATH . '../storage/brand/' . $filename;
        if (is_file($path) && !@unlink($path)) {
            log_message('error', 'Could not remove an obsolete brand logo');
        }
    }
}
