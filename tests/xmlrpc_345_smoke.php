<?php
// Run with the XML extension: php -d error_reporting=E_ALL tests/xmlrpc_345_smoke.php
define('BASEPATH', dirname(__DIR__).DIRECTORY_SEPARATOR.'vendor'.DIRECTORY_SEPARATOR.'pocketarc'.DIRECTORY_SEPARATOR.'codeigniter'.DIRECTORY_SEPARATOR.'system'.DIRECTORY_SEPARATOR);

function log_message($level, $message)
{
}

require BASEPATH.'libraries/Xmlrpc.php';
require BASEPATH.'libraries/Xmlrpcs.php';

set_error_handler(function ($severity, $message) {
	if ($severity === E_DEPRECATED || $severity === E_USER_DEPRECATED)
	{
		throw new ErrorException($message, 0, $severity);
	}
	return FALSE;
});

$server = new CI_Xmlrpcs();
foreach (array(
	'<broken',
	'<methodCall><methodName>missing.method</methodName><params></params></methodCall>',
) as $request)
{
	$response = $server->parseRequest($request);
	if ( ! ($response instanceof XML_RPC_Response))
	{
		throw new RuntimeException('XML-RPC request parsing failed');
	}
}

$message = new XML_RPC_Message('test');
foreach (array(
	'<broken',
	'<methodResponse><params><param><value><string>ok</string></value></param></params></methodResponse>',
) as $body)
{
	$stream = fopen('php://temp', 'w+');
	fwrite($stream, "HTTP/1.1 200 OK\r\nContent-Type: text/xml\r\n\r\n".$body);
	rewind($stream);
	$response = $message->parseResponse($stream);
	fclose($stream);
	if ( ! ($response instanceof XML_RPC_Response))
	{
		throw new RuntimeException('XML-RPC response parsing failed');
	}
}

restore_error_handler();
echo "XML-RPC parse paths passed without deprecations\n";
