<?php
// Router for the PHP development server; production must point its document root at public/.
$request_path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$file = is_string($request_path) ? realpath(__DIR__.rawurldecode($request_path)) : false;
$extension = $file === false ? '' : strtolower(pathinfo($file, PATHINFO_EXTENSION));
$public_prefix = __DIR__.DIRECTORY_SEPARATOR;

if ($file !== false && strncmp($file, $public_prefix, strlen($public_prefix)) === 0
    && is_file($file) && in_array($extension, array('css', 'js', 'png', 'jpg', 'jpeg', 'gif', 'webp', 'svg', 'ico', 'woff', 'woff2', 'ttf'), true))
{
    return false;
}

// PHP's built-in server may set SCRIPT_NAME to a dotted route segment
// (IonAuth activation tokens contain a dot). CI3 then strips the route.
$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['PHP_SELF'] = '/index.php';
$_SERVER['SCRIPT_FILENAME'] = __DIR__.'/index.php';
require __DIR__.'/index.php';
