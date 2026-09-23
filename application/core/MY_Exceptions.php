<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Keep HTML error output escaped without modifying the Composer package.
 * This carries the security fix previously applied to system/core/Exceptions.php.
 */
class MY_Exceptions extends CI_Exceptions
{
    public function show_error($heading, $message, $template = 'error_general', $status_code = 500)
    {
        if ( ! is_cli())
        {
            $heading = html_escape($heading);
            $message = html_escape($message);
        }

        return parent::show_error($heading, $message, $template, $status_code);
    }

    public function show_exception($exception)
    {
        $templates_path = config_item('error_views_path');
        $templates_path = empty($templates_path)
            ? VIEWPATH.'errors'.DIRECTORY_SEPARATOR
            : rtrim($templates_path, '/\\').DIRECTORY_SEPARATOR;

        $message = $exception->getMessage();
        if (empty($message))
        {
            $message = '(null)';
        }

        if (is_cli())
        {
            $templates_path .= 'cli'.DIRECTORY_SEPARATOR;
        }
        else
        {
            $message = html_escape($message);
            $templates_path .= 'html'.DIRECTORY_SEPARATOR;
        }

        if (ob_get_level() > $this->ob_level + 1)
        {
            ob_end_flush();
        }
        ob_start();
        include($templates_path.'error_exception.php');
        $buffer = ob_get_contents();
        ob_end_clean();
        echo $buffer;
    }

    public function show_php_error($severity, $message, $filepath, $line)
    {
        if ( ! is_cli())
        {
            $severity = isset($this->levels[$severity]) ? $this->levels[$severity] : $severity;
            $severity = html_escape($severity);
            $message = html_escape($message);
            $filepath = html_escape($filepath);
        }

        parent::show_php_error($severity, $message, $filepath, $line);
    }
}
