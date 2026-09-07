<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_role(['chair']);

$pdo = db_connect();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    verify_csrf();
    if ($_POST['action'] === 'create_member') {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $role = $_POST['role'] ?? 'member';
        $location = trim($_POST['location'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $social_media = trim($_POST['social_media'] ?? '');
        $position = trim($_POST['position'] ?? '');
        $marital_status = $_POST['marital_status'] ?? null;

        if ($name !== '' && $email !== '' && strlen($password) >= 12 && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $stmt = $pdo->prepare('INSERT INTO users (name, email, password_hash, role, marital_status, phone, social_media, location, position) VALUES (:name, :email, :password_hash, :role, :marital_status, :phone, :social_media, :location, :position)');
            $stmt->execute([
                'name' => $name,
                'email' => $email,
                'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                'role' => $role,
                'marital_status' => $marital_status,
                'phone' => $phone,
                'social_media' => $social_media,
                'location' => $location,
                'position' => $position,
            ]);
            $memberId = (int) $pdo->lastInsertId();
            $uploadedPhoto = save_uploaded_photo($_FILES['photo'] ?? [], $memberId);
            if ($uploadedPhoto) {
                $pdo->prepare('UPDATE users SET photo = :photo WHERE id = :id')->execute(['photo' => $uploadedPhoto, 'id' => $memberId]);
            }
            set_flash('success', 'Member added successfully.');
        } else {
            set_flash('danger', 'Name, email and password are required.');
        }
    }

    if ($_POST['action'] === 'create_event') {
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $event_date = $_POST['event_date'] ?? '';
        $location = trim($_POST['location'] ?? '');

        if ($title !== '' && $event_date !== '') {
            $stmt = $pdo->prepare('INSERT INTO events (title, description, event_date, location, created_by) VALUES (:title, :description, :event_date, :location, :created_by)');
            $stmt->execute([
                'title' => $title,
                'description' => $description,
                'event_date' => $event_date,
                'location' => $location,
                'created_by' => current_user()['id'],
            ]);

            $eventId = (int) $pdo->lastInsertId();
            $posterPath = save_uploaded_file($_FILES['poster'] ?? [], $eventId, 'events', ['image/jpeg', 'image/png', 'image/webp']);
            $attachmentPath = save_uploaded_file($_FILES['attachment'] ?? [], $eventId, 'events', ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document']);

            if ($posterPath) {
                $pdo->prepare('INSERT INTO gallery_photos (uploader_id, source_type, source_id, image_path, caption, created_at) VALUES (:uploader_id, :source_type, :source_id, :image_path, :caption, NOW())')->execute([
                    'uploader_id' => current_user()['id'],
                    'source_type' => 'event',
                    'source_id' => $eventId,
                    'image_path' => $posterPath,
                    'caption' => $title,
                ]);
            }

            if ($posterPath || $attachmentPath) {
                $update = $pdo->prepare('UPDATE events SET poster = :poster, attachment = :attachment WHERE id = :id');
                $update->execute([
                    'poster' => $posterPath,
                    'attachment' => $attachmentPath,
                    'id' => $eventId,
                ]);
            }

            set_flash('success', 'Event created successfully.');
        } else {
            set_flash('danger', 'Title and event date are required.');
        }
    }

    if ($_POST['action'] === 'create_update') {
        $title = trim($_POST['title'] ?? '');
        $content = trim($_POST['content'] ?? '');

        if ($title !== '' && $content !== '') {
            $stmt = $pdo->prepare('INSERT INTO updates (title, content, author_id, published_at) VALUES (:title, :content, :author_id, NOW())');
            $stmt->execute([
                'title' => $title,
                'content' => $content,
                'author_id' => current_user()['id'],
            ]);
            set_flash('success', 'Update published successfully.');
        } else {
            set_flash('danger', 'Title and content are required.');
        }
    }

    if ($_POST['action'] === 'publish_report') {
        $reportId = (int) ($_POST['report_id'] ?? 0);
        if ($reportId > 0) {
            $stmt = $pdo->prepare('UPDATE department_reports SET published = 1, status = :status, published_at = NOW() WHERE id = :id');
            $stmt->execute([
                'status' => 'published',
                'id' => $reportId,
            ]);
            set_flash('success', 'Report approved and published to the public dashboard.');
        }
    }
}

$members = $pdo->query('SELECT * FROM users ORDER BY name ASC')->fetchAll();
$events = $pdo->query('SELECT * FROM events ORDER BY event_date DESC')->fetchAll();
$updates = $pdo->query('SELECT u.*, users.name AS author_name FROM updates u INNER JOIN users ON users.id = u.author_id ORDER BY published_at DESC')->fetchAll();
$reports = $pdo->query('SELECT * FROM department_reports ORDER BY created_at DESC')->fetchAll();

include __DIR__ . '/../views/partials/header.php';
?>
<div class="dash-header">
    <h1>Praise Team Chair Dashboard</h1>
    <span class="badge"><?php echo e(role_label(current_user()['role'])); ?></span>
</div>

<?php render_flash(); ?>

<div class="stat-grid" style="margin-bottom: 30px;">
    <div class="stat-box">
        <span class="label">Total Members</span>
        <span class="value"><?php echo count($members); ?></span>
    </div>
    <div class="stat-box">
        <span class="label">Events</span>
        <span class="value"><?php echo count($events); ?></span>
    </div>
    <div class="stat-box">
        <span class="label">Updates</span>
        <span class="value"><?php echo count($updates); ?></span>
    </div>
</div>

<section class="grid" style="margin-bottom: 28px;">
    <div class="card">
        <h2>Add Member</h2>
        <form method="post" enctype="multipart/form-data">
            <?php csrf_field(); ?>
            <input type="hidden" name="action" value="create_member">
            <div class="form-grid">
                <div class="form-group"><label>Name</label><input type="text" name="name" required></div>
                <div class="form-group"><label>Email</label><input type="email" name="email" required></div>
                <div class="form-group"><label>Password</label><input type="password" name="password" required></div>
                <div class="form-group"><label>Role</label><select name="role"><option value="member">Member</option><option value="chair">Chair</option><option value="secretary">Secretary</option><option value="music_director">Music Director</option><option value="band_director">Band/Worship Director</option></select></div>
                <div class="form-group"><label>Marital Status</label><select name="marital_status"><option value="">Select</option><option value="single">Single</option><option value="married">Married</option></select></div>
                <div class="form-group"><label>Phone</label><input type="text" name="phone"></div>
                <div class="form-group"><label>Social Media</label><input type="text" name="social_media"></div>
                <div class="form-group"><label>Location</label><input type="text" name="location"></div>
                <div class="form-group"><label>Position</label><input type="text" name="position"></div>
                <div class="form-group" style="grid-column: 1 / -1;"><label>Profile Picture</label><input type="file" name="photo" accept="image/png,image/jpeg,image/webp"></div>
            </div>
            <button type="submit" class="btn btn-primary">Save Member</button>
        </form>
    </div>

    <div class="card">
        <h2>Create Event</h2>
        <form method="post" enctype="multipart/form-data">
            <?php csrf_field(); ?>
            <input type="hidden" name="action" value="create_event">
            <div class="form-group"><label>Title</label><input type="text" name="title" required></div>
            <div class="form-group"><label>Date & Time</label><input type="datetime-local" name="event_date" required></div>
            <div class="form-group"><label>Location</label><input type="text" name="location"></div>
            <div class="form-group"><label>Description</label><textarea name="description"></textarea></div>
            <div class="form-group"><label>Event Poster</label><input type="file" name="poster" accept="image/png,image/jpeg,image/webp"></div>
            <div class="form-group"><label>Event Letter / Attachment (PDF or Word)</label><input type="file" name="attachment" accept="application/pdf,.pdf,application/msword,.doc,.docx,application/vnd.openxmlformats-officedocument.wordprocessingml.document,.docx"></div>
            <button type="submit" class="btn btn-success">Add Event</button>
        </form>
    </div>
</section>

<section class="grid" style="margin-bottom: 28px;">
    <div class="card">
        <h2>Member Directory</h2>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Role</th>
                        <th>Phone</th>
                        <th>Location</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($members as $member): ?>
                        <tr>
                            <td><?php echo e($member['name']); ?></td>
                            <td><?php echo e(role_label($member['role'])); ?></td>
                            <td><?php echo e($member['phone'] ?: 'N/A'); ?></td>
                            <td><?php echo e($member['location'] ?: 'N/A'); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card">
        <h2>Publish Update</h2>
        <form method="post">
            <?php csrf_field(); ?>
            <input type="hidden" name="action" value="create_update">
            <div class="form-group"><label>Title</label><input type="text" name="title" required></div>
            <div class="form-group"><label>Content</label><textarea name="content" required></textarea></div>
            <button type="submit" class="btn btn-primary">Publish Update</button>
        </form>
    </div>
</section>

<div class="card">
    <h2>Published Reports Queue</h2>
    <div class="table-wrap">
        <table>
            <thead>
                <tr><th>Department</th><th>Title</th><th>Status</th><th>Action</th></tr>
            </thead>
            <tbody>
                <?php foreach ($reports as $report): ?>
                    <tr>
                        <td><?php echo e(ucfirst(str_replace('_', ' ', $report['department']))); ?></td>
                        <td><?php echo e($report['title']); ?></td>
                        <td>
                            <span class="badge <?php echo $report['published'] ? 'published' : ($report['status'] === 'pending' ? 'draft' : 'draft'); ?>"><?php echo $report['published'] ? 'Published' : ucfirst($report['status'] ?? 'draft'); ?></span>
                        </td>
                        <td>
                            <?php if (($report['status'] ?? 'draft') === 'pending'): ?>
                                <form method="post" style="display:inline;">
                                    <?php csrf_field(); ?>
                                    <input type="hidden" name="action" value="publish_report">
                                    <input type="hidden" name="report_id" value="<?php echo (int) $report['id']; ?>">
                                    <button type="submit" class="btn btn-success">Approve & Publish</button>
                                </form>
                            <?php elseif ($report['published']): ?>
                                <span>Live</span>
                            <?php else: ?>
                                <span>Waiting</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
    </div>
</div>

<?php include __DIR__ . '/../views/partials/footer.php'; ?>
