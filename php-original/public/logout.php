<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_login();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
	redirect('/app/public/dashboard.php');
}
verify_csrf();
logout_user();
set_flash('success', 'You have been logged out.');
redirect('/app/public/index.php');
