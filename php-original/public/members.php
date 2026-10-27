<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_login();

$pdo = db_connect();
$members = $pdo->query('SELECT * FROM users ORDER BY name ASC')->fetchAll();

include __DIR__ . '/../views/partials/header.php';
?>
<div class="page-shell">
    <h1 class="section-title">Praise Team Members</h1>
    <p>Meet the members of the praise team </p>

    <?php if ($members): ?>
        <div class="members-grid">
            <?php foreach ($members as $member): ?>
                <article class="member-card">
                    <?php if (!empty($member['photo'])): ?>
                        <img src="<?php echo app_url($member['photo']); ?>" alt="<?php echo e($member['name']); ?>" class="member-photo">
                    <?php else: ?>
                        <div class="member-photo member-photo-placeholder" aria-label="No photo available">
                            <?php echo e(substr($member['name'] ?? 'M', 0, 1)); ?>
                        </div>
                    <?php endif; ?>

                    <div class="member-body">
                        <h2 class="member-name"><?php echo e($member['name']); ?></h2>
                        <p class="member-meta"><strong><?php echo e(role_label($member['role'])); ?></strong></p>
                        <?php if (!empty($member['position'])): ?>
                            <p class="member-detail"><strong>Position:</strong> <?php echo e($member['position']); ?></p>
                        <?php endif; ?>
                        <?php if (!empty($member['email'])): ?>
                            <p class="member-detail"><strong>Email:</strong> <?php echo e($member['email']); ?></p>
                        <?php endif; ?>
                        <?php if (!empty($member['phone'])): ?>
                            <p class="member-detail"><strong>Phone:</strong> <?php echo e($member['phone']); ?></p>
                        <?php endif; ?>
                        <?php if (!empty($member['location'])): ?>
                            <p class="member-detail"><strong>Location:</strong> <?php echo e($member['location']); ?></p>
                        <?php endif; ?>
                        <?php if (!empty($member['social_media'])): ?>
                            <p class="member-detail"><strong>Social:</strong> <?php echo e($member['social_media']); ?></p>
                        <?php endif; ?>
                        <?php if (!empty($member['marital_status'])): ?>
                            <p class="member-detail"><strong>Marital Status:</strong> <?php echo e(ucfirst($member['marital_status'])); ?></p>
                        <?php endif; ?>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="card">
            <p>No members have been added yet.</p>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../views/partials/footer.php'; ?>
