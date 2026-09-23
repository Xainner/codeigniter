<?php
/** Local STARTTLS SMTP sink for the disposable integration database. */
declare(strict_types=1);

$certificate = (string) getenv('TEST_SMTP_CERT');
$key = (string) getenv('TEST_SMTP_KEY');
$directory = (string) getenv('TEST_SMTP_CAPTURE_DIR');
if (!is_file($certificate) || !is_file($key) || !is_dir($directory)) {
    fwrite(STDERR, "TEST_SMTP_CERT, TEST_SMTP_KEY and TEST_SMTP_CAPTURE_DIR are required\n");
    exit(1);
}
$context = stream_context_create(['ssl' => [
    'local_cert' => $certificate,
    'local_pk' => $key,
    'verify_peer' => false,
]]);
$server = stream_socket_server('tcp://127.0.0.1:2525', $errno, $error, STREAM_SERVER_BIND | STREAM_SERVER_LISTEN, $context);
if ($server === false) {
    throw new RuntimeException("SMTP sink cannot listen: $error ($errno)");
}
while ($client = @stream_socket_accept($server, -1)) {
    stream_set_timeout($client, 20);
    fwrite($client, "220 localhost test SMTP\r\n");
    $encrypted = false;
    $recipient = '';
    $message = '';
    $data = false;
    while (!feof($client)) {
        $line = fgets($client);
        if ($line === false) { break; }
        if ($data) {
            if (rtrim($line, "\r\n") === '.') {
                $path = $directory . DIRECTORY_SEPARATOR . 'message-' . bin2hex(random_bytes(8)) . '.json';
                file_put_contents($path, json_encode(['recipient' => $recipient, 'body' => $message], JSON_THROW_ON_ERROR));
                fwrite($client, "250 accepted\r\n");
                $data = false;
                $message = '';
            } else {
                $message .= $line[0] === '.' ? substr($line, 1) : $line;
            }
            continue;
        }
        $command = strtoupper(strtok($line, " \r\n"));
        if ($command === 'EHLO' || $command === 'HELO') {
            fwrite($client, $encrypted ? "250 localhost\r\n" : "250-localhost\r\n250-STARTTLS\r\n250 SIZE 10485760\r\n");
        } elseif ($command === 'STARTTLS' && !$encrypted) {
            fwrite($client, "220 ready for TLS\r\n");
            $encrypted = stream_socket_enable_crypto($client, true, STREAM_CRYPTO_METHOD_TLS_SERVER) === true;
            if (!$encrypted) { break; }
        } elseif ($command === 'MAIL') {
            fwrite($client, "250 sender accepted\r\n");
        } elseif ($command === 'RCPT') {
            $recipient = trim(substr($line, 8), " \r\n<>");
            fwrite($client, "250 recipient accepted\r\n");
        } elseif ($command === 'DATA') {
            $data = true;
            fwrite($client, "354 end with dot\r\n");
        } elseif ($command === 'RSET' || $command === 'NOOP') {
            fwrite($client, "250 OK\r\n");
        } elseif ($command === 'QUIT') {
            fwrite($client, "221 goodbye\r\n");
            break;
        } else {
            fwrite($client, "502 unsupported\r\n");
        }
    }
    fclose($client);
}
