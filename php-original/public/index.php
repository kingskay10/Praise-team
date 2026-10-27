<?php
require_once __DIR__ . '/../includes/bootstrap.php';

$pdo = db_connect();
$verse = $pdo->query("SELECT dv.*, u.name AS member_name FROM daily_verses dv INNER JOIN users u ON u.id = dv.member_id WHERE dv.posted_on = CURDATE() ORDER BY dv.created_at DESC LIMIT 1")->fetch();
$upcomingEvents = $pdo->query("SELECT * FROM events WHERE event_date >= NOW() ORDER BY event_date ASC LIMIT 3")->fetchAll();
$pastEvents = $pdo->query("SELECT * FROM events WHERE event_date < NOW() ORDER BY event_date DESC LIMIT 3")->fetchAll();
$updates = $pdo->query("SELECT u.*, users.name AS author_name FROM updates u INNER JOIN users ON users.id = u.author_id ORDER BY published_at DESC, u.created_at DESC LIMIT 5")->fetchAll();
$reports = $pdo->query("SELECT * FROM department_reports WHERE published = 1 ORDER BY published_at DESC, created_at DESC LIMIT 6")->fetchAll();

include __DIR__ . '/../views/partials/header.php';
?>
<section class="hero">
    <h1>Welcome to the Praise Team</h1>
    <p>Stay connected with updates, prayer points, events, and published reports from the church worship family.</p>
</section>

<section class="grid" style="margin-bottom: 30px;">
    <div class="card verse-card">
        <p class="verse-label">Daily reflection</p>
        <h2>Verse of the Day</h2>
        <?php if ($verse): ?>
            <blockquote class="verse-quote">
                <?php echo nl2br(e($verse['verse_text'])); ?>
            </blockquote>
            <p class="verse-attribution"><strong>Posted by</strong> <?php echo e($verse['member_name']); ?></p>
        <?php else: ?>
            <p class="empty-state">No verse has been posted for today yet.</p>
        <?php endif; ?>
    </div>

    <div class="card">
        <h2>Upcoming Events</h2>
        <?php if ($upcomingEvents): ?>
            <ul class="meta-list">
                <?php foreach ($upcomingEvents as $event): ?>
                    <li>
                        <?php if (!empty($event['poster'])): ?>
                            <img src="<?php echo app_url($event['poster']); ?>" alt="<?php echo e($event['title']); ?>" class="event-poster">
                        <?php endif; ?>
                        <strong><?php echo e($event['title']); ?></strong><br>
                        <?php echo e(format_datetime($event['event_date'])); ?><br>
                        <?php echo e($event['location'] ?: 'Location not set'); ?><br>
                        <?php if (!empty($event['attachment'])): ?>
                            <a href="<?php echo app_url($event['attachment']); ?>" target="_blank" rel="noopener">View letter/attachment</a>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p>No upcoming events yet.</p>
        <?php endif; ?>
    </div>

    <div class="card">
        <h2>Latest Updates</h2>
        <?php if ($updates): ?>
            <ul class="meta-list">
                <?php foreach ($updates as $update): ?>
                    <li>
                        <strong><?php echo e($update['title']); ?></strong><br>
                        <small>By <?php echo e($update['author_name']); ?></small><br>
                        <?php echo nl2br(e($update['content'])); ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p>No updates published yet.</p>
        <?php endif; ?>
    </div>
</section>

<section class="grid" style="margin-bottom: 30px;">
    <div class="card">
        <h2>Past Events</h2>
        <?php if ($pastEvents): ?>
            <ul class="meta-list">
                <?php foreach ($pastEvents as $event): ?>
                    <li>
                        <?php if (!empty($event['poster'])): ?>
                            <img src="<?php echo app_url($event['poster']); ?>" alt="<?php echo e($event['title']); ?>" class="event-poster">
                        <?php endif; ?>
                        <strong><?php echo e($event['title']); ?></strong><br>
                        <?php echo e(format_datetime($event['event_date'])); ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p>No past events yet.</p>
        <?php endif; ?>
    </div>

    <div class="card">
        <h2>Published Reports</h2>
        <?php if ($reports): ?>
            <ul class="meta-list">
                <?php foreach ($reports as $report): ?>
                    <li>
                        <strong><?php echo e($report['title']); ?></strong> - <?php echo e(ucfirst($report['department'])); ?><br>
                        <?php echo nl2br(e($report['summary'])); ?><br>
                        <small>Last updated: <?php echo e(format_datetime($report['updated_at'] ?? $report['created_at'])); ?></small>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p>No published reports yet.</p>
        <?php endif; ?>
    </div>
</section>

<section class="card faq-section">
    <h2>Frequently Asked Questions</h2>
    <details><summary>Who can access the team portal?</summary><p>Registered praise team members can access private areas according to their assigned role.</p></details>
    <details><summary>Who approves department reports?</summary><p>Reports submitted by the secretary or directors are reviewed and published by the chair.</p></details>
    <details><summary>How do I request a data change or removal?</summary><p>Use the Contact button or the administrator's church contact process to request an update or removal.</p></details>
</section>

<?php include __DIR__ . '/../views/partials/footer.php'; ?>
