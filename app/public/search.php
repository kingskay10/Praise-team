<?php
require_once __DIR__ . '/../includes/bootstrap.php';

$query = trim($_GET['q'] ?? '');
$results = [];
if (strlen($query) >= 2) {
    $like = '%' . $query . '%';
    $pdo = db_connect();
    $stmt = $pdo->prepare("SELECT title, description AS excerpt, event_date AS sort_date, 'Event' AS type FROM events WHERE title LIKE :event_title OR description LIKE :event_description UNION ALL SELECT title, content AS excerpt, published_at AS sort_date, 'Update' AS type FROM updates WHERE title LIKE :update_title OR content LIKE :update_content UNION ALL SELECT title, summary AS excerpt, published_at AS sort_date, 'Report' AS type FROM department_reports WHERE published = 1 AND (title LIKE :report_title OR summary LIKE :report_summary) ORDER BY sort_date DESC LIMIT 30");
    $stmt->execute([
        'event_title' => $like,
        'event_description' => $like,
        'update_title' => $like,
        'update_content' => $like,
        'report_title' => $like,
        'report_summary' => $like,
    ]);
    $results = $stmt->fetchAll();
}

include __DIR__ . '/../views/partials/header.php';
?>
<div class="card search-page">
    <h1>Site Search</h1>
    <form class="site-search search-form" method="get" role="search">
        <label for="search-query">Search public events, updates, and reports</label>
        <input id="search-query" name="q" type="search" value="<?php echo e($query); ?>" minlength="2" required>
        <button type="submit" class="btn btn-primary">Search</button>
    </form>
    <?php if ($query !== '' && strlen($query) < 2): ?>
        <p class="alert alert-danger">Enter at least two characters.</p>
    <?php elseif ($query !== '' && !$results): ?>
        <p class="empty-state">No public results found.</p>
    <?php elseif ($results): ?>
        <p><?php echo count($results); ?> result(s) for &quot;<?php echo e($query); ?>&quot;</p>
        <?php foreach ($results as $result): ?>
            <article class="search-result">
                <span class="badge"><?php echo e($result['type']); ?></span>
                <h2><?php echo e($result['title']); ?></h2>
                <p><?php echo e($result['excerpt'] ?? ''); ?></p>
                <small>Last updated: <?php echo e(format_datetime($result['sort_date'])); ?></small>
            </article>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
<?php include __DIR__ . '/../views/partials/footer.php'; ?>
