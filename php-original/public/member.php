<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_login();

$pdo = db_connect();
$user = current_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    verify_csrf();
    if ($_POST['action'] === 'update_photo') {
        $photo = save_uploaded_photo($_FILES['photo'] ?? [], (int) $user['id']);
        if ($photo) {
            $pdo->prepare('UPDATE users SET photo = :photo WHERE id = :id')->execute([
                'photo' => $photo,
                'id' => (int) $user['id'],
            ]);

            $existingGallery = $pdo->prepare('SELECT id FROM gallery_photos WHERE uploader_id = :uploader_id AND source_type = :source_type AND source_id = :source_id AND image_path = :image_path LIMIT 1');
            $existingGallery->execute([
                'uploader_id' => (int) $user['id'],
                'source_type' => 'member',
                'source_id' => (int) $user['id'],
                'image_path' => $photo,
            ]);
            if (!$existingGallery->fetch()) {
                $pdo->prepare('INSERT INTO gallery_photos (uploader_id, source_type, source_id, image_path, caption, created_at) VALUES (:uploader_id, :source_type, :source_id, :image_path, :caption, NOW())')->execute([
                    'uploader_id' => (int) $user['id'],
                    'source_type' => 'member',
                    'source_id' => (int) $user['id'],
                    'image_path' => $photo,
                    'caption' => 'Profile photo',
                ]);
            }

            $user['photo'] = $photo;
            $_SESSION['user']['photo'] = $photo;
            set_flash('success', 'Profile picture updated successfully.');
        } else {
            set_flash('danger', 'Please upload a valid JPG, PNG, or WEBP image.');
        }
    }

    if ($_POST['action'] === 'upload_gallery_photo') {
        $galleryPhoto = save_uploaded_file($_FILES['gallery_photo'] ?? [], (int) $user['id'], 'gallery', ['image/jpeg', 'image/png', 'image/webp']);
        if ($galleryPhoto) {
            $pdo->prepare('INSERT INTO member_photos (user_id, image_path) VALUES (:user_id, :image_path)')->execute([
                'user_id' => (int) $user['id'],
                'image_path' => $galleryPhoto,
            ]);
            $pdo->prepare('INSERT INTO gallery_photos (uploader_id, source_type, source_id, image_path, caption, created_at) VALUES (:uploader_id, :source_type, :source_id, :image_path, :caption, NOW())')->execute([
                'uploader_id' => (int) $user['id'],
                'source_type' => 'member',
                'source_id' => (int) $user['id'],
                'image_path' => $galleryPhoto,
                'caption' => 'Member photo',
            ]);
            set_flash('success', 'Photo added to your gallery.');
        } else {
            set_flash('danger', 'Please upload a valid JPG, PNG, or WEBP image for your gallery.');
        }
    }

    if ($_POST['action'] === 'submit_verse') {
        $verseText = trim($_POST['verse_text'] ?? '');
        if ($verseText !== '') {
            $pdo->prepare('INSERT INTO daily_verses (member_id, verse_text, posted_on, created_at) VALUES (:member_id, :verse_text, CURDATE(), NOW())')->execute([
                'member_id' => (int) $user['id'],
                'verse_text' => $verseText,
            ]);
            set_flash('success', 'Your verse was posted for today.');
        } else {
            set_flash('danger', 'Please enter a Bible verse before submitting.');
        }
    }
}

$member = $pdo->prepare('SELECT * FROM users WHERE id = :id LIMIT 1');
$member->execute(['id' => (int) $user['id']]);
$profile = $member->fetch();

$gallery = $pdo->prepare('SELECT * FROM member_photos WHERE user_id = :id ORDER BY created_at DESC');
$gallery->execute(['id' => (int) $user['id']]);
$photos = $gallery->fetchAll();

$events = $pdo->query('SELECT * FROM events ORDER BY event_date DESC LIMIT 5')->fetchAll();
$updates = $pdo->query('SELECT u.*, users.name AS author_name FROM updates u INNER JOIN users ON users.id = u.author_id ORDER BY published_at DESC LIMIT 5')->fetchAll();
$reports = $pdo->query('SELECT * FROM department_reports WHERE published = 1 ORDER BY published_at DESC LIMIT 5')->fetchAll();

include __DIR__ . '/../views/partials/header.php';
?>
<div class="dash-header">
    <h1>Member Dashboard</h1>
    <span class="badge"><?php echo e(role_label(current_user()['role'])); ?></span>
</div>

<?php render_flash(); ?>

<div class="grid" style="margin-bottom: 28px;">
    <div class="card">
        <h2>Profile</h2>
        <div class="profile-card">
            <?php if (!empty($profile['photo'])): ?>
                <img src="<?php echo app_url($profile['photo']); ?>" alt="Profile photo" class="profile-avatar">
            <?php else: ?>
                <div class="profile-avatar-placeholder"><?php echo e(substr($profile['name'] ?? 'M', 0, 1)); ?></div>
            <?php endif; ?>
            <p><strong><?php echo e($profile['name'] ?? 'Member'); ?></strong></p>
            <p><?php echo e($profile['position'] ?: 'Member'); ?></p>
            <form method="post" enctype="multipart/form-data">
                <?php csrf_field(); ?>
                <input type="hidden" name="action" value="update_photo">
                <div class="form-group">
                    <label for="member_photo">Change Profile Picture</label>
                    <input type="file" id="member_photo" name="photo" accept="image/png,image/jpeg,image/webp">
                </div>
                <button type="submit" class="btn btn-primary">Update Photo</button>
            </form>
        </div>
    </div>

    <div class="card">
        <h2>Latest Updates</h2>
        <?php foreach ($updates as $update): ?>
            <div style="margin-bottom: 16px;">
                <strong><?php echo e($update['title']); ?></strong><br>
                <small>By <?php echo e($update['author_name']); ?></small>
                <p><?php echo nl2br(e($update['content'])); ?></p>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<div class="grid" style="margin-bottom: 28px;">
    <div class="card">
        <h2>Upcoming Events</h2>
        <?php foreach ($events as $event): ?>
            <div style="margin-bottom: 14px;">
                <strong><?php echo e($event['title']); ?></strong><br>
                <small><?php echo e(format_datetime($event['event_date'])); ?></small><br>
                <?php echo e($event['location'] ?: 'Location not set'); ?>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="card">
        <h2>Published Reports</h2>
        <?php foreach ($reports as $report): ?>
            <div style="margin-bottom: 18px; border-bottom: 1px solid var(--line); padding-bottom: 10px;">
                <h3><?php echo e($report['title']); ?></h3>
                <strong><?php echo e(ucfirst($report['department'])); ?></strong>
                <p><?php echo nl2br(e($report['summary'])); ?></p>
                <div><?php echo nl2br(e($report['content'])); ?></div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<div class="card" style="margin-bottom: 28px;">
    <h2>Verse of the Day</h2>
    <form method="post">
        <?php csrf_field(); ?>
        <input type="hidden" name="action" value="submit_verse">
        <div class="form-group">
            <label for="verse_text">Add a Bible verse</label>
            <textarea id="verse_text" name="verse_text" placeholder="Add a Bible verse" required></textarea>
        </div>
        <button type="submit" class="btn btn-primary">Post Verse</button>
    </form>
</div>

<div class="card" style="margin-bottom: 28px;">
    <h2>Personal Photo Gallery</h2>
    <form method="post" enctype="multipart/form-data">
        <?php csrf_field(); ?>
        <input type="hidden" name="action" value="upload_gallery_photo">
        <div class="form-group">
            <label for="gallery_photo">Upload another photo</label>
            <input type="file" id="gallery_photo" name="gallery_photo" accept="image/png,image/jpeg,image/webp">
        </div>
        <button type="submit" class="btn btn-primary">Add to Gallery</button>
    </form>

    <?php if ($photos): ?>
        <div class="gallery-grid">
            <?php foreach ($photos as $photo): ?>
                <div class="gallery-item">
                    <img src="<?php echo app_url($photo['image_path']); ?>" alt="Gallery photo">
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="empty-state" style="margin-top: 16px;">No extra photos uploaded yet.</div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../views/partials/footer.php'; ?>
