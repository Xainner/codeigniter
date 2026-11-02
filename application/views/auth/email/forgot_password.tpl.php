<?php
defined('BASEPATH') OR exit('No direct script access allowed');

$minutes = (int) floor((int) $this->config->item('forgot_password_expiration', 'ion_auth') / 60);
echo $this->load->view('auth/email/_layout', [
	'email_title' => 'Restablece tu contraseña',
	'email_intro' => 'Recibimos una solicitud para restablecer la contraseña de ' . $identity . '.'
		. ($minutes > 0 ? ' El enlace caduca en ' . $minutes . ' minutos.' : ''),
	'email_button' => 'Elegir una contraseña nueva',
	'email_url' => site_url('auth/reset_password/' . $forgotten_password_code),
	'email_note' => 'Si no lo solicitaste, ignora este mensaje: tu contraseña no cambiará.',
], TRUE);
