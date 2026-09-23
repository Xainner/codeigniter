<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$feedback_message = trim((string) ($message ?? ''));
$feedback_errors = $errors ?? [];
$feedback_errors = is_array($feedback_errors) ? $feedback_errors : ($feedback_errors === '' ? [] : [$feedback_errors]);
?>
<?php if ($feedback_message !== ''): ?>
<div class="notice" role="status"><?php echo html_escape($feedback_message); ?></div>
<?php endif; ?>
<?php if ($feedback_errors !== []): ?>
<div class="notice notice-error" role="alert"><strong>Revisa los siguientes datos:</strong><ul><?php foreach ($feedback_errors as $error): ?><li><?php echo html_escape((string) $error); ?></li><?php endforeach; ?></ul></div>
<?php endif; ?>
