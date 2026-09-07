<?php
require_once __DIR__ . '/../includes/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        set_flash('danger', 'Please provide both email and password.');
    } else {
        $pdo = db_connect();
        $stmt = $pdo->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            login_user($user);
            set_flash('success', 'Welcome back, ' . $user['name'] . '!');
            redirect('/app/public/dashboard.php');
        } else {
            set_flash('danger', 'Invalid email or password.');
        }
    }
}

include __DIR__ . '/../views/partials/header.php';
?>
<div class="card auth-card">
    <h2>Member Login</h2>
    <?php render_flash(); ?>
    <form method="post">
        <?php csrf_field(); ?>
        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" required>
        </div>
        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" required>
        </div>
        <button type="submit" class="btn btn-primary">Login</button>
    </form>
</div>
<?php include __DIR__ . '/../views/partials/footer.php'; ?>
