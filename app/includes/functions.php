<?php
function config_value(string $key, $default = null)
{
    static $config = null;

    if ($config === null) {
        $config = require __DIR__ . '/../config/config.php';
    }

    return $config[$key] ?? $default;
}

function app_url(string $path = ''): string
{
    $base = rtrim(config_value('app_url'), '/');
    $relative = ltrim($path, '/');

    return $relative === '' ? $base : $base . '/' . $relative;
}

function redirect(string $path): void
{
    $location = app_url($path);
    header('Location: ' . $location);
    exit;
}

function e(string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function format_date(string $date): string
{
    if (empty($date)) {
        return 'Not set';
    }

    return date('M d, Y', strtotime($date));
}

function format_datetime(?string $value): string
{
    if (empty($value)) {
        return 'Not set';
    }

    return date('M d, Y h:i A', strtotime($value));
}

function role_label(string $role): string
{
    $labels = config_value('role_labels', []);
    return $labels[$role] ?? ucfirst(str_replace('_', ' ', $role));
}

function set_flash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function get_flash(): ?array
{
    if (!isset($_SESSION['flash'])) {
        return null;
    }

    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return $flash;
}

function render_flash(): void
{
    $flash = get_flash();

    if (!$flash) {
        return;
    }

    $type = $flash['type'] ?? 'info';
    $message = e($flash['message'] ?? '');
    echo '<div class="alert alert-' . e($type) . '">' . $message . '</div>';
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function csrf_field(): void
{
    echo '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function verify_csrf(): void
{
    $token = $_POST['csrf_token'] ?? '';
    if (!is_string($token) || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
        http_response_code(403);
        exit('Invalid request token.');
    }
}

function save_uploaded_file(array $file, int $ownerId, string $folderName = 'members', array $allowed = ['image/jpeg', 'image/png', 'image/webp']): ?string
{
    if (!isset($file['name']) || $file['error'] !== UPLOAD_ERR_OK || empty($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        return null;
    }

    if (($file['size'] ?? 0) < 1 || $file['size'] > 10 * 1024 * 1024) {
        return null;
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!is_string($mime) || !in_array($mime, $allowed, true)) {
        return null;
    }

    if (str_starts_with($mime, 'image/') && @getimagesize($file['tmp_name']) === false) {
        return null;
    }

    $extension = match ($mime) {
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'application/pdf' => 'pdf',
        'application/msword' => 'doc',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
        default => 'bin',
    };

    $folder = __DIR__ . '/../../uploads/' . $folderName;
    if (!is_dir($folder) && !mkdir($folder, 0750, true)) {
        return null;
    }

    $filename = $folderName . '_' . $ownerId . '_' . bin2hex(random_bytes(16)) . '.' . $extension;
    $destination = $folder . '/' . $filename;

    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        return null;
    }

    return 'uploads/' . $folderName . '/' . $filename;
}

function save_uploaded_photo(array $file, int $userId): ?string
{
    return save_uploaded_file($file, $userId, 'members', ['image/jpeg', 'image/png', 'image/webp']);
}

function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function is_authenticated(): bool
{
    return isset($_SESSION['user']['id']);
}

function login_user(array $user): void
{
    session_regenerate_id(true);
    $_SESSION['user'] = [
        'id' => (int) $user['id'],
        'name' => $user['name'],
        'email' => $user['email'],
        'role' => $user['role'],
    ];
}

function logout_user(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
}

function require_login(): void
{
    if (!is_authenticated()) {
        redirect('/app/public/login.php');
    }
}

function require_role(array $allowed): void
{
    require_login();

    $user = current_user();
    if (!in_array($user['role'], $allowed, true)) {
        redirect('/app/public/dashboard.php');
    }
}

function fetch_table(string $table, array $conditions = [], string $orderBy = 'id DESC'): array
{
    $pdo = db_connect();
    $sql = 'SELECT * FROM ' . $table;
    $params = [];

    if ($conditions) {
        $clauses = [];
        foreach ($conditions as $field => $value) {
            $clauses[] = $field . ' = :field_' . $field;
            $params['field_' . $field] = $value;
        }
        $sql .= ' WHERE ' . implode(' AND ', $clauses);
    }

    $sql .= ' ORDER BY ' . $orderBy;

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}
