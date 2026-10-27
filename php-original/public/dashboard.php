<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_login();

$user = current_user();
$role = $user['role'];

if ($role === 'chair') {
    redirect('/app/public/chair.php');
}
if ($role === 'secretary') {
    redirect('/app/public/secretary.php');
}
if ($role === 'music_director') {
    redirect('/app/public/music-director.php');
}
if ($role === 'band_director') {
    redirect('/app/public/band-director.php');
}

redirect('/app/public/member.php');
