<?php
/** HTTP smoke suite for a disposable database and a running PHP server. */
declare(strict_types=1);

$base = rtrim((string) getenv('TEST_BASE_URL'), '/');
$setupToken = (string) getenv('APP_SETUP_TOKEN');
if ($base === '' || $setupToken === '') {
    fwrite(STDERR, "TEST_BASE_URL and APP_SETUP_TOKEN are required\n");
    exit(1);
}
$cookieFile = tempnam(sys_get_temp_dir(), 'ci-cookie-');
register_shutdown_function(static function () use ($cookieFile): void { @unlink($cookieFile); });

function check(bool $ok, string $message): void
{
    if (!$ok) {
        throw new RuntimeException($message);
    }
    echo "OK $message\n";
}

function redirected(array $response): bool
{
    return in_array($response['status'], [302, 303], true);
}

function http(string $method, string $path, ?array $data = null, bool $ajax = false): array
{
    global $base, $cookieFile;
    $curl = curl_init($base . $path);
    curl_setopt_array($curl, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER => true,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_COOKIEJAR => $cookieFile,
        CURLOPT_COOKIEFILE => $cookieFile,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_ENCODING => '',
        CURLOPT_HTTPHEADER => $ajax ? ['Accept: application/json', 'X-Requested-With: XMLHttpRequest'] : [],
    ]);
    if ($data !== null) {
        $multipart = false;
        foreach ($data as $item) { if ($item instanceof CURLFile) { $multipart = true; break; } }
        curl_setopt($curl, CURLOPT_POSTFIELDS, $multipart ? $data : http_build_query($data));
    }
    $result = curl_exec($curl);
    if ($result === false) {
        throw new RuntimeException('HTTP request failed: ' . curl_error($curl));
    }
    $headerSize = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
    $response = [
        'status' => curl_getinfo($curl, CURLINFO_RESPONSE_CODE),
        'headers' => substr($result, 0, $headerSize),
        'body' => substr($result, $headerSize),
    ];
    curl_close($curl);
    return $response;
}

function hidden(string $html, string $name): string
{
    if (!preg_match('/<input[^>]+name="' . preg_quote($name, '/') . '"[^>]+value="([^"]*)"/i', $html, $match)) {
        throw new RuntimeException("Missing hidden input $name");
    }
    return html_entity_decode($match[1], ENT_QUOTES, 'UTF-8');
}

function csrf(string $html): array
{
    return ['csrf_test_name' => hidden($html, 'csrf_test_name')];
}

function db(): mysqli
{
    $conn = new mysqli((string) getenv('DB_HOST'), (string) getenv('DB_USER'),
        (string) getenv('DB_PASS'), (string) getenv('DB_NAME'), (int) (getenv('DB_PORT') ?: 3306));
    if ($conn->connect_errno) { throw new RuntimeException($conn->connect_error); }
    return $conn;
}

function countUsers(): int
{
    $result = db()->query('SELECT COUNT(*) AS total FROM users');
    return (int) $result->fetch_assoc()['total'];
}

function queryCount(array $response): int
{
    if (!preg_match('/^X-Query-Count:\s*(\d+)/im', $response['headers'], $match)) {
        throw new RuntimeException('Missing test query count header');
    }
    return (int) $match[1];
}

function capturedMessages(): array
{
    $directory = (string) getenv('TEST_SMTP_CAPTURE_DIR');
    return $directory === '' ? [] : (glob($directory . DIRECTORY_SEPARATOR . 'message-*.json') ?: []);
}

function newMessage(array $previous): array
{
    $files = array_values(array_diff(capturedMessages(), $previous));
    if (count($files) !== 1) { throw new RuntimeException('Expected one captured SMTP message'); }
    $message = json_decode((string) file_get_contents($files[0]), true);
    if (!is_array($message)) { throw new RuntimeException('Invalid captured SMTP message'); }
    return $message;
}

try {
    $setup = http('GET', '/auth/setup');
    check($setup['status'] === 200, 'initial setup page opens');
    $token = csrf($setup['body']);
    $details = $token + [
        'setup_token' => 'wrong', 'first_name' => 'Ada', 'last_name' => 'Admin',
        'email' => 'ada@example.test', 'password' => 'VeryStrongPassword123!',
        'password_confirm' => 'VeryStrongPassword123!',
    ];
    $wrong = http('POST', '/auth/setup', $details);
    check($wrong['status'] === 200 && countUsers() === 0, 'wrong setup secret creates no user');
    $details['setup_token'] = $setupToken;
    $created = http('POST', '/auth/setup', $details);
    check(redirected($created) && countUsers() === 1,
        'setup creates one administrator (HTTP ' . $created['status'] . ', users ' . countUsers() . ')');
    check(http('GET', '/auth/setup')['status'] === 404, 'setup closes after completion');

    $login = http('GET', '/auth/login');
    check($login['status'] === 200, 'login page opens');
    check(str_contains($login['headers'], 'X-Frame-Options: DENY')
        && str_contains($login['headers'], 'X-Content-Type-Options: nosniff'),
        'application responses include security headers');
    $loginResult = http('POST', '/auth/login', csrf($login['body']) + [
        'identity' => 'ada@example.test', 'password' => 'VeryStrongPassword123!',
    ], true);
    $loginJson = json_decode($loginResult['body'], true);
    check($loginResult['status'] === 200 && ($loginJson['success'] ?? false)
        && str_contains($loginJson['redirect'] ?? '', '/auth'), 'admin login redirects to dashboard');

    $dashboard = http('GET', '/auth');
    check($dashboard['status'] === 200 && str_contains($dashboard['body'], 'Usuarios y grupos'), 'dashboard renders');
    $oneUserQueries = queryCount($dashboard);
    check(http('GET', '/auth/register')['status'] === 404, 'public registration starts disabled');
    check(http('POST', '/auth/register', csrf($dashboard['body']))['status'] === 404, 'disabled registration rejects POST');

    $conn = db();
    $hash = password_hash('FixturePassword123!', PASSWORD_BCRYPT);
    $insert = $conn->prepare('INSERT INTO users (ip_address, password, email, created_on, active, first_name, last_name) VALUES (?, ?, ?, ?, 1, ?, ?)');
    $ip = '127.0.0.1'; $first = 'Fixture'; $last = 'Member'; $createdOn = time();
    for ($n = 1; $n <= 30; ++$n) {
        $email = "fixture$n@example.test";
        $insert->bind_param('sssiss', $ip, $hash, $email, $createdOn, $first, $last);
        $insert->execute();
        $id = $conn->insert_id;
        $conn->query('INSERT INTO users_groups (user_id, group_id) VALUES (' . (int) $id . ', 2)');
    }
    $many = http('GET', '/auth');
    check($many['status'] === 200 && queryCount($many) === $oneUserQueries,
        'dashboard query count stays constant with more users (' . $oneUserQueries . ' queries)');
    check(str_contains($many['body'], 'Páginas de usuarios'), 'dashboard paginates users');
    check(http('GET', '/auth/deactivate/1')['status'] === 403, 'GET cannot deactivate users');

    $settings = http('GET', '/auth/settings');
    check($settings['status'] === 200, 'admin settings page opens');
    $values = csrf($settings['body']) + [
        'public_registration' => '1', 'email_activation' => '0', 'remember_users' => '0',
        'min_password_length' => '12', 'maximum_login_attempts' => '5', 'lockout_time' => '900',
        'smtp_host' => '', 'smtp_port' => '587', 'smtp_crypto' => 'tls', 'smtp_user' => '',
        'smtp_from_email' => '', 'smtp_from_name' => '', 'site_name' => 'Demo Team',
        'brand_color' => '#2255aa',
    ];
    check(redirected(http('POST', '/auth/settings', $values)), 'admin saves live settings');
    $audit = db()->query('SELECT changed_keys FROM settings_audit ORDER BY id DESC LIMIT 1')->fetch_assoc();
    check($audit && in_array('public_registration', json_decode($audit['changed_keys'], true), true),
        'settings changes are audited');
    check(http('GET', '/auth/register')['status'] === 200, 'registration opens after settings change');
    $registerPage = http('GET', '/auth/register');
    $registered = http('POST', '/auth/register', csrf($registerPage['body']) + [
        'first_name' => 'Public', 'last_name' => 'Member', 'email' => 'public@example.test',
        'password' => 'PublicStrongPassword123!', 'password_confirm' => 'PublicStrongPassword123!',
    ], true);
    check($registered['status'] === 200 && (json_decode($registered['body'], true)['success'] ?? false),
        'public registration works when enabled');
    check(http('POST', '/auth/settings', ['site_name' => 'bad'])['status'] === 403,
        'settings reject missing CSRF token');

    $forgot = http('GET', '/auth/forgot_password');
    $unavailable = http('POST', '/auth/forgot_password', csrf($forgot['body']) + [
        'identity' => 'ada@example.test',
    ], true);
    check($unavailable['status'] === 503, 'recovery reports SMTP unavailable without false success');

    $groupForm = http('GET', '/auth/create_group');
    check($groupForm['status'] === 200, 'group form opens');
    check(redirected(http('POST', '/auth/create_group', csrf($groupForm['body']) + [
        'group_name' => 'editors', 'description' => 'Content editors',
    ])), 'admin creates group');
    $adminGroup = http('GET', '/auth/edit_group/1');
    $rename = http('POST', '/auth/edit_group/1', csrf($adminGroup['body']) + [
        'group_name' => 'renamed', 'group_description' => 'Changed',
    ]);
    check($rename['status'] === 200 && str_contains($rename['body'], 'No se puede cambiar'),
        'admin group cannot be renamed');

    $selfEdit = http('GET', '/auth/edit_user/1');
    $selfDemote = http('POST', '/auth/edit_user/1', csrf($selfEdit['body']) + [
        'auth_nonce' => hidden($selfEdit['body'], 'auth_nonce'), 'id' => '1',
        'first_name' => 'Ada', 'last_name' => 'Admin', 'email' => 'ada@example.test',
        'phone' => '', 'company' => '', 'password' => '', 'password_confirm' => '',
        'groups' => ['2'],
    ]);
    check($selfDemote['status'] === 200 && str_contains($selfDemote['body'], 'No puedes retirar'),
        'administrator cannot remove own admin membership');

    $userForm = http('GET', '/auth/create_user');
    check($userForm['status'] === 200, 'user form opens');
    $newUser = http('POST', '/auth/create_user', csrf($userForm['body']) + [
        'first_name' => 'Grace', 'last_name' => 'Hopper', 'email' => 'grace@example.test',
        'phone' => '', 'company' => '', 'password' => 'AnotherStrongPassword123!',
        'password_confirm' => 'AnotherStrongPassword123!', 'groups' => ['2'],
    ]);
    check(redirected($newUser), 'admin creates user');
    $id = (int) $conn->query("SELECT id FROM users WHERE email='grace@example.test'")->fetch_assoc()['id'];
    $editForm = http('GET', '/auth/edit_user/' . $id);
    $edited = http('POST', '/auth/edit_user/' . $id, csrf($editForm['body']) + [
        'auth_nonce' => hidden($editForm['body'], 'auth_nonce'), 'id' => (string) $id,
        'first_name' => 'Grace', 'last_name' => 'Hopper Jr', 'email' => 'grace@example.test',
        'phone' => '', 'company' => '', 'password' => '', 'password_confirm' => '',
        'groups' => ['2', '3'],
    ]);
    $editedUser = $conn->query('SELECT last_name FROM users WHERE id=' . $id)->fetch_assoc();
    $membership = $conn->query('SELECT COUNT(*) AS total FROM users_groups WHERE user_id=' . $id)->fetch_assoc();
    check(redirected($edited) && $editedUser['last_name'] === 'Hopper Jr' && (int) $membership['total'] === 2,
        'admin edits user and group membership');
    $adminCookieFile = $cookieFile;
    $memberCookieFile = tempnam(sys_get_temp_dir(), 'ci-member-cookie-');
    register_shutdown_function(static function () use ($memberCookieFile): void { @unlink($memberCookieFile); });
    $cookieFile = $memberCookieFile;
    $memberLogin = http('GET', '/auth/login');
    $memberSession = http('POST', '/auth/login', csrf($memberLogin['body']) + [
        'identity' => 'grace@example.test', 'password' => 'AnotherStrongPassword123!',
    ], true);
    check($memberSession['status'] === 200 && http('GET', '/auth/edit_user/' . $id)['status'] === 200,
        'member session accesses its own profile before deactivation');
    $cookieFile = $adminCookieFile;
    $page = http('GET', '/auth?q=grace');
    $nonce = hidden($page['body'], 'auth_nonce');
    check(redirected(http('POST', '/auth/deactivate/' . $id, csrf($page['body']) + ['auth_nonce' => $nonce])),
        'admin deactivates another user');
    sleep(2);
    $cookieFile = $memberCookieFile;
    check(http('GET', '/auth/edit_user/' . $id)['status'] === 403,
        'deactivation revokes an existing session');
    $cookieFile = $adminCookieFile;
    $page = http('GET', '/auth?q=grace');
    check(redirected(http('POST', '/auth/activate/' . $id, csrf($page['body']) + ['auth_nonce' => hidden($page['body'], 'auth_nonce')])),
        'admin reactivates user');
    $page = http('GET', '/auth?q=ada');
    check(http('POST', '/auth/deactivate/1', csrf($page['body']) + ['auth_nonce' => hidden($page['body'], 'auth_nonce')])['status'] === 403,
        'admin cannot deactivate own account');

    $logo = tempnam(sys_get_temp_dir(), 'ci-logo-');
    $image = imagecreatetruecolor(16, 16);
    imagepng($image, $logo);
    imagedestroy($image);
    $settings = http('GET', '/auth/settings');
    $withLogo = $values;
    $withLogo['csrf_test_name'] = hidden($settings['body'], 'csrf_test_name');
    $withLogo['brand_logo_file'] = new CURLFile($logo, 'image/png', 'logo.png');
    check(redirected(http('POST', '/auth/settings', $withLogo)), 'admin uploads brand logo');
    @unlink($logo);
    $logoResponse = http('GET', '/brand/logo');
    check($logoResponse['status'] === 200 && str_contains($logoResponse['headers'], 'image/png')
        && getimagesizefromstring($logoResponse['body'])[0] === 16,
        'logo streams from private storage');
    $badLogo = tempnam(sys_get_temp_dir(), 'ci-bad-logo-');
    file_put_contents($badLogo, 'not an image');
    $settings = http('GET', '/auth/settings');
    $invalidLogo = $values;
    $invalidLogo['csrf_test_name'] = hidden($settings['body'], 'csrf_test_name');
    $invalidLogo['brand_logo_file'] = new CURLFile($badLogo, 'image/png', 'bad.png');
    $badUpload = http('POST', '/auth/settings', $invalidLogo);
    @unlink($badLogo);
    check($badUpload['status'] === 200 && str_contains($badUpload['body'], 'Dimensiones de logo inválidas'),
        'invalid logo upload is rejected');

    if (getenv('TEST_SMTP_CAPTURE_DIR')) {
        $settings = http('GET', '/auth/settings');
        $smtpValues = $values + ['smtp_password' => ''];
        $smtpValues['csrf_test_name'] = hidden($settings['body'], 'csrf_test_name');
        $smtpValues['smtp_host'] = 'localhost';
        $smtpValues['smtp_port'] = '2525';
        $smtpValues['smtp_crypto'] = 'tls';
        $smtpValues['smtp_from_email'] = 'noreply@example.test';
        $smtpValues['smtp_from_name'] = 'Demo Team';
        $smtpValues['smtp_password'] = 'DisposableSmtpPassword123!';
        check(redirected(http('POST', '/auth/settings', $smtpValues)), 'admin saves SMTP settings');
        $stored = $conn->query("SELECT value FROM app_settings WHERE name='smtp_password'")->fetch_assoc()['value'];
        check($stored !== $smtpValues['smtp_password'] && !str_contains($stored, 'DisposableSmtpPassword'),
            'SMTP password is encrypted at rest');
        $settings = http('GET', '/auth/settings');
        check(!str_contains($settings['body'], 'DisposableSmtpPassword'), 'SMTP password is never displayed');
        $before = capturedMessages();
        $tested = http('POST', '/auth/settings/test-email', csrf($settings['body']));
        $testMail = newMessage($before);
        check(redirected($tested) && $testMail['recipient'] === 'ada@example.test',
            'SMTP test sends a message to the administrator');

        $settings = http('GET', '/auth/settings');
        $smtpValues['csrf_test_name'] = hidden($settings['body'], 'csrf_test_name');
        $smtpValues['email_activation'] = '1';
        unset($smtpValues['smtp_password']);
        check(redirected(http('POST', '/auth/settings', $smtpValues)),
            'admin enables email activation after SMTP test');
        $registration = http('GET', '/auth/register');
        $before = capturedMessages();
        $activation = http('POST', '/auth/register', csrf($registration['body']) + [
            'first_name' => 'Email', 'last_name' => 'Member', 'email' => 'activate@example.test',
            'password' => 'ActivationPassword123!', 'password_confirm' => 'ActivationPassword123!',
        ], true);
        $activationMail = newMessage($before);
        check($activation['status'] === 200 && $activationMail['recipient'] === 'activate@example.test',
            'registration sends activation mail through simulated SMTP');
        $inactive = $conn->query("SELECT active FROM users WHERE email='activate@example.test'")->fetch_assoc();
        check((int) $inactive['active'] === 0, 'new account stays inactive until confirmation');
        $mailBody = quoted_printable_decode($activationMail['body']);
        if (!preg_match('~https?://[^"\s<>]+/auth/activate/\d+/[^"\s<>]+~', $mailBody, $match)) {
            throw new RuntimeException('Activation link absent from captured mail');
        }
        $activationPath = parse_url(html_entity_decode($match[0]), PHP_URL_PATH);
        $confirm = http('GET', $activationPath);
        check($confirm['status'] === 200, 'activation link requires confirmation');
        check(redirected(http('POST', $activationPath, csrf($confirm['body']))), 'POST activates account');

        $forgot = http('GET', '/auth/forgot_password');
        $before = capturedMessages();
        $known = http('POST', '/auth/forgot_password', csrf($forgot['body']) + [
            'identity' => 'ada@example.test',
        ], true);
        $knownMail = newMessage($before);
        $forgot = http('GET', '/auth/forgot_password');
        $before = capturedMessages();
        $unknown = http('POST', '/auth/forgot_password', csrf($forgot['body']) + [
            'identity' => 'nobody@example.test',
        ], true);
        $probeMail = newMessage($before);
        check($known['status'] === 200 && $known['body'] === $unknown['body']
            && $knownMail['recipient'] === 'ada@example.test'
            && $probeMail['recipient'] === 'noreply@example.test',
            'recovery uses SMTP and gives uniform public responses');
        $recoveryBody = quoted_printable_decode($knownMail['body']);
        if (!preg_match('~https?://[^"\s<>]+/auth/reset_password/[^"\s<>]+~', $recoveryBody, $match)) {
            throw new RuntimeException('Recovery link absent from captured mail');
        }
        $resetPath = parse_url(html_entity_decode($match[0]), PHP_URL_PATH);
        $resetForm = http('GET', $resetPath);
        check($resetForm['status'] === 200 && str_contains($resetForm['body'], 'new_confirm'),
            'recovery link opens password form');
        $reset = http('POST', $resetPath, csrf($resetForm['body']) + [
            'auth_nonce' => hidden($resetForm['body'], 'auth_nonce'),
            'user_id' => hidden($resetForm['body'], 'user_id'),
            'new' => 'RecoveredPassword123!', 'new_confirm' => 'RecoveredPassword123!',
        ]);
        $adminHash = $conn->query("SELECT password FROM users WHERE email='ada@example.test'")->fetch_assoc()['password'];
        check(redirected($reset) && password_verify('RecoveredPassword123!', $adminHash),
            'recovery token changes the password');
    }

    check(http('GET', '/auth/logout')['status'] === 404, 'GET cannot log out');
    $page = http('GET', '/auth');
    $logout = http('POST', '/auth/logout', csrf($page['body']));
    check(redirected($logout), 'logout uses POST (HTTP ' . $logout['status'] . ')');
    $memberLogin = http('GET', '/auth/login');
    $memberResult = http('POST', '/auth/login', csrf($memberLogin['body']) + [
        'identity' => 'public@example.test', 'password' => 'PublicStrongPassword123!',
    ], true);
    check($memberResult['status'] === 200, 'public member can log in');
    check(http('GET', '/auth/settings')['status'] === 403, 'member cannot access settings');
    check(http('GET', '/auth')['status'] !== 200, 'member cannot access admin dashboard');

    $ping = http('GET', '/api/ping');
    check($ping['status'] === 200 && json_decode($ping['body'], true) === ['status' => 'ok'],
        'API ping reveals only minimal JSON');
    echo "Integration suite passed\n";
} catch (Throwable $exception) {
    fwrite(STDERR, "Integration failure: " . $exception->getMessage() . "\n");
    exit(1);
}
