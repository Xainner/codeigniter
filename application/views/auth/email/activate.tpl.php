<?php
defined('BASEPATH') OR exit('No direct script access allowed');

echo $this->load->view('auth/email/_layout', [
	'email_title' => 'Activa tu cuenta',
	'email_intro' => 'Hola, ' . $identity . '. Confirma tu correo para empezar a usar tu cuenta.',
	'email_button' => 'Activar cuenta',
	'email_url' => site_url('auth/activate/' . $id . '/' . $activation),
	'email_note' => 'Si no creaste esta cuenta, ignora este mensaje: no se activará.',
], TRUE);
