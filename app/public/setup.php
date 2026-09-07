<?php
require_once __DIR__ . '/../includes/bootstrap.php';

$pdo = db_connect();
if ((int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn() > 0) {
    redirect('/app/public/login.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $name = trim($_POST['name'] ?? '');

    if ($email === '' || $password === '' || $name === '' || strlen($password) < 12 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        set_flash('danger', 'Name, email and password are required.');
    } else {
        $insert = $pdo->prepare('INSERT INTO users (name, email, password_hash, role) VALUES (:name, :email, :password_hash, :role)');
        $insert->execute([
            'name' => $name,
            'email' => $email,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'role' => 'chair',
        ]);
        set_flash('success', 'Chair account created. You can now log in.');
    }

    redirect('/app/public/login.php');
}

include __DIR__ . '/../views/partials/header.php';
?>
<div class="card auth-card">
    <h2>Create First Praise Team Chair Account</h2>
    <p>Create the first admin account for the church praise team portal.</p>
    <form method="post">
        <?php csrf_field(); ?>
        <div class="form-group">
            <label>Name</label>
            <input type="text" name="name" required>
        </div>
        <div class="form-group">
            <label>Email</label>
            <input type="email" name="email" required>
        </div>
        <div class="form-group">
            <label>Password</label>
            <input type="password" name="password" required minlength="12">
        </div>
        <button type="submit" class="btn btn-primary">Create Admin</button>
    </form>
</div>
<?php include __DIR__ . '/../views/partials/footer.php'; ?>
