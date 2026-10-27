<?php
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: strict-origin-when-cross-origin');
if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
}
header("Content-Security-Policy: default-src 'self'; img-src 'self' data:; style-src 'self' 'unsafe-inline'; script-src 'self' 'unsafe-inline'; frame-ancestors 'none'; base-uri 'self'; form-action 'self'");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo e(config_value('app_name')); ?></title>
    <link rel="stylesheet" href="<?php echo app_url('assets/css/styles.css'); ?>">
</head>
<body>
    <a class="skip-link" href="#main-content">Skip to content</a>
    <div class="scroll-progress" id="scrollProgress" aria-hidden="true"></div>
    <?php require_once __DIR__ . '/nav.php'; ?>
    <main class="page-shell" id="main-content">
