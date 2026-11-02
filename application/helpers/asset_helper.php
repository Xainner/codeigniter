<?php
defined('BASEPATH') OR exit('No direct script access allowed');

if ( ! function_exists('asset_url'))
{
	/**
	 * URL of a file under public/ carrying its modification time, so browsers
	 * that cache assets for a week still fetch them again after a deployment.
	 *
	 * @param	string	$path	Path relative to public/
	 * @return	string
	 */
	function asset_url($path)
	{
		$path = ltrim((string) $path, '/');
		$modified = is_file(FCPATH.$path) ? filemtime(FCPATH.$path) : FALSE;

		return base_url($path).($modified ? '?v='.$modified : '');
	}
}
