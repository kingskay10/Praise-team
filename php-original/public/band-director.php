<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_role(['band_director']);

$pdo = db_connect();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    verify_csrf();
    if ($_POST['action'] === 'save_band') {
        $title = trim($_POST['title'] ?? '');
        $assignments = trim($_POST['assignments'] ?? '');
        $equipment = trim($_POST['equipment'] ?? '');
        $schedule_notes = trim($_POST['schedule_notes'] ?? '');

        if ($title !== '') {
            $stmt = $pdo->prepare('INSERT INTO department_band (title, assignments, equipment, schedule_notes, created_by) VALUES (:title, :assignments, :equipment, :schedule_notes, :created_by)');
            $stmt->execute([
                'title' => $title,
                'assignments' => $assignments,
                'equipment' => $equipment,
                'schedule_notes' => $schedule_notes,
                'created_by' => current_user()['id'],
            ]);
            set_flash('success', 'Band plan saved.');
        }
    }

    if ($_POST['action'] === 'submit_report') {
        $title = trim($_POST['title'] ?? '');
        $summary = trim($_POST['summary'] ?? '');
        $content = trim($_POST['content'] ?? '');

        if ($title !== '' && $summary !== '' && $content !== '') {
            $stmt = $pdo->prepare('INSERT INTO department_reports (department, title, summary, content, created_by, status, published, published_at) VALUES (:department, :title, :summary, :content, :created_by, :status, 0, NULL)');
            $stmt->execute([
                'department' => 'band_director',
                'title' => $title,
                'summary' => $summary,
                'content' => $content,
                'created_by' => current_user()['id'],
                'status' => 'pending',
            ]);
            set_flash('success', 'Band report submitted to the chair for approval.');
        }
    }
}

$bandPlans = $pdo->query('SELECT * FROM department_band ORDER BY created_at DESC')->fetchAll();
include __DIR__ . '/../views/partials/header.php';
?>
<div class="dash-header">
    <h1>Band/Worship Director Dashboard</h1>
    <span class="badge"><?php echo e(role_label(current_user()['role'])); ?></span>
</div>
<?php render_flash(); ?>

<section class="grid" style="margin-bottom: 28px;">
    <div class="card">
        <h2>Add Band Plan</h2>
        <form method="post">
            <?php csrf_field(); ?>
            <input type="hidden" name="action" value="save_band">
            <div class="form-group"><label>Title</label><input type="text" name="title" required></div>
            <div class="form-group"><label>Assignments</label><textarea name="assignments"></textarea></div>
            <div class="form-group"><label>Equipment</label><textarea name="equipment"></textarea></div>
            <div class="form-group"><label>Schedule Notes</label><textarea name="schedule_notes"></textarea></div>
            <button type="submit" class="btn btn-primary">Save Plan</button>
        </form>
    </div>

    <div class="card">
        <h2>Publish Band Report</h2>
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
    <h2>Band Plans</h2>
    <div class="table-wrap">
        <table>
            <thead>
                <tr><th>Title</th><th>Assignments</th><th>Equipment</th><th>Schedule Notes</th></tr>
            </thead>
            <tbody>
                <?php foreach ($bandPlans as $plan): ?>
                    <tr>
                        <td><?php echo e($plan['title']); ?></td>
                        <td><?php echo nl2br(e($plan['assignments'] ?: '—')); ?></td>
                        <td><?php echo nl2br(e($plan['equipment'] ?: '—')); ?></td>
                        <td><?php echo nl2br(e($plan['schedule_notes'] ?: '—')); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/../views/partials/footer.php'; ?>
