<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_role(['secretary']);

$pdo = db_connect();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    verify_csrf();
    if ($_POST['action'] === 'save_finance') {
        $memberId = (int) ($_POST['member_id'] ?? 0);
        $month = trim($_POST['month'] ?? '');
        $contributions = (float) ($_POST['contributions'] ?? 0);
        $offerings = (float) ($_POST['offerings'] ?? 0);
        $notes = trim($_POST['notes'] ?? '');

        if ($memberId > 0 && $month !== '') {
            $stmt = $pdo->prepare('INSERT INTO finance_records (member_id, month, contributions, offerings, notes, recorded_by) VALUES (:member_id, :month, :contributions, :offerings, :notes, :recorded_by) ON DUPLICATE KEY UPDATE contributions = VALUES(contributions), offerings = VALUES(offerings), notes = VALUES(notes), recorded_by = VALUES(recorded_by), updated_at = NOW()');
            $stmt->execute([
                'member_id' => $memberId,
                'month' => $month,
                'contributions' => $contributions,
                'offerings' => $offerings,
                'notes' => $notes,
                'recorded_by' => current_user()['id'],
            ]);
            set_flash('success', 'Finance record saved.');
        }
    }

    if ($_POST['action'] === 'submit_report') {
        $title = trim($_POST['title'] ?? '');
        $summary = trim($_POST['summary'] ?? '');
        $content = trim($_POST['content'] ?? '');

        if ($title !== '' && $summary !== '' && $content !== '') {
            $stmt = $pdo->prepare('INSERT INTO department_reports (department, title, summary, content, created_by, status, published, published_at) VALUES (:department, :title, :summary, :content, :created_by, :status, 0, NULL)');
            $stmt->execute([
                'department' => 'secretary',
                'title' => $title,
                'summary' => $summary,
                'content' => $content,
                'created_by' => current_user()['id'],
                'status' => 'pending',
            ]);
            set_flash('success', 'Financial report submitted to the chair for approval.');
        }
    }
}

$members = $pdo->query('SELECT * FROM users ORDER BY name ASC')->fetchAll();
$finance = $pdo->query('SELECT f.*, u.name AS member_name FROM finance_records f INNER JOIN users u ON u.id = f.member_id ORDER BY f.month DESC, u.name ASC')->fetchAll();
include __DIR__ . '/../views/partials/header.php';
?>
<div class="dash-header">
    <h1>Secretary Dashboard</h1>
    <span class="badge"><?php echo e(role_label(current_user()['role'])); ?></span>
</div>
<?php render_flash(); ?>

<section class="grid" style="margin-bottom: 28px;">
    <div class="card">
        <h2>Record Monthly Finance</h2>
        <form method="post">
            <?php csrf_field(); ?>
            <input type="hidden" name="action" value="save_finance">
            <div class="form-grid">
                <div class="form-group"><label>Member</label>
                    <select name="member_id" required>
                        <option value="">Select member</option>
                        <?php foreach ($members as $member): ?>
                            <option value="<?php echo (int) $member['id']; ?>"><?php echo e($member['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group"><label>Month</label><input type="month" name="month" required></div>
                <div class="form-group"><label>Contributions</label><input type="number" step="0.01" name="contributions" value="0"></div>
                <div class="form-group"><label>Offerings</label><input type="number" step="0.01" name="offerings" value="0"></div>
                <div class="form-group" style="grid-column: 1 / -1;"><label>Notes</label><textarea name="notes"></textarea></div>
            </div>
            <button type="submit" class="btn btn-primary">Save Record</button>
        </form>
    </div>

    <div class="card">
        <h2>Publish Financial Summary</h2>
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
    <h2>Finance Records</h2>
    <div class="table-wrap">
        <table>
            <thead>
                <tr><th>Member</th><th>Month</th><th>Contributions</th><th>Offerings</th><th>Notes</th></tr>
            </thead>
            <tbody>
                <?php foreach ($finance as $record): ?>
                    <tr>
                        <td><?php echo e($record['member_name']); ?></td>
                        <td><?php echo e($record['month']); ?></td>
                        <td>$<?php echo number_format((float) $record['contributions'], 2); ?></td>
                        <td>$<?php echo number_format((float) $record['offerings'], 2); ?></td>
                        <td><?php echo e($record['notes'] ?: '—'); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/../views/partials/footer.php'; ?>
