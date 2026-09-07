<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_role(['music_director']);

$pdo = db_connect();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    verify_csrf();
    if ($_POST['action'] === 'save_music') {
        $title = trim($_POST['title'] ?? '');
        $notes = trim($_POST['notes'] ?? '');
        $rehearsal_date = $_POST['rehearsal_date'] ?? null;

        if ($title !== '') {
            $stmt = $pdo->prepare('INSERT INTO department_music (title, notes, rehearsal_date, created_by) VALUES (:title, :notes, :rehearsal_date, :created_by)');
            $stmt->execute([
                'title' => $title,
                'notes' => $notes,
                'rehearsal_date' => $rehearsal_date,
                'created_by' => current_user()['id'],
            ]);
            set_flash('success', 'Music plan saved.');
        }
    }

    if ($_POST['action'] === 'submit_report') {
        $title = trim($_POST['title'] ?? '');
        $summary = trim($_POST['summary'] ?? '');
        $content = trim($_POST['content'] ?? '');

        if ($title !== '' && $summary !== '' && $content !== '') {
            $stmt = $pdo->prepare('INSERT INTO department_reports (department, title, summary, content, created_by, status, published, published_at) VALUES (:department, :title, :summary, :content, :created_by, :status, 0, NULL)');
            $stmt->execute([
                'department' => 'music_director',
                'title' => $title,
                'summary' => $summary,
                'content' => $content,
                'created_by' => current_user()['id'],
                'status' => 'pending',
            ]);
            set_flash('success', 'Music report submitted to the chair for approval.');
        }
    }
}

$musicPlans = $pdo->query('SELECT * FROM department_music ORDER BY rehearsal_date DESC, created_at DESC')->fetchAll();
include __DIR__ . '/../views/partials/header.php';
?>
<div class="dash-header">
    <h1>Music Director Dashboard</h1>
    <span class="badge"><?php echo e(role_label(current_user()['role'])); ?></span>
</div>
<?php render_flash(); ?>

<section class="grid" style="margin-bottom: 28px;">
    <div class="card">
        <h2>Add Music Plan</h2>
        <form method="post">
            <?php csrf_field(); ?>
            <input type="hidden" name="action" value="save_music">
            <div class="form-group"><label>Title</label><input type="text" name="title" required></div>
            <div class="form-group"><label>Rehearsal Date</label><input type="datetime-local" name="rehearsal_date"></div>
            <div class="form-group"><label>Notes</label><textarea name="notes"></textarea></div>
            <button type="submit" class="btn btn-primary">Save Plan</button>
        </form>
    </div>

    <div class="card">
        <h2>Publish Music Report</h2>
        <form method="post">
            <?php csrf_field(); ?>
            <input type="hidden" name="action" value="submit_report">
            <div class="form-group"><label>Title</label><input type="text" name="title" required></div>
            <div class="form-group"><label>Summary</label><textarea name="summary" required></textarea></div>
            <div class="form-group"><label>Full Content</label><textarea name="content" required></textarea></div>
            <button type="submit" class="btn btn-success">Submit to Chair</button>
        </form>
    </div>
</section>

<div class="card">
    <h2>Music Plans</h2>
    <div class="table-wrap">
        <table>
            <thead>
                <tr><th>Title</th><th>Rehearsal</th><th>Notes</th></tr>
            </thead>
            <tbody>
                <?php foreach ($musicPlans as $plan): ?>
                    <tr>
                        <td><?php echo e($plan['title']); ?></td>
                        <td><?php echo e($plan['rehearsal_date'] ? format_datetime($plan['rehearsal_date']) : 'Not set'); ?></td>
                        <td><?php echo nl2br(e($plan['notes'] ?: '—')); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/../views/partials/footer.php'; ?>
