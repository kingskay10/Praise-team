<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_login();

$pdo = db_connect();
$currentUser = current_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'toggle_like') {
        $photoId = (int) ($_POST['photo_id'] ?? 0);
        if ($photoId > 0) {
            $exists = $pdo->prepare('SELECT id FROM gallery_photo_likes WHERE photo_id = :photo_id AND user_id = :user_id LIMIT 1');
            $exists->execute(['photo_id' => $photoId, 'user_id' => $currentUser['id']]);
            if ($exists->fetch()) {
                $pdo->prepare('DELETE FROM gallery_photo_likes WHERE photo_id = :photo_id AND user_id = :user_id')->execute(['photo_id' => $photoId, 'user_id' => $currentUser['id']]);
            } else {
                $pdo->prepare('INSERT INTO gallery_photo_likes (photo_id, user_id) VALUES (:photo_id, :user_id)')->execute([
                    'photo_id' => $photoId,
                    'user_id' => $currentUser['id'],
                ]);
            }
        }
        redirect('/app/public/gallery.php');
    }

    if ($action === 'add_comment') {
        $photoId = (int) ($_POST['photo_id'] ?? 0);
        $comment = trim($_POST['comment'] ?? '');
        if ($photoId > 0 && $comment !== '') {
            $pdo->prepare('INSERT INTO gallery_comments (photo_id, user_id, comment) VALUES (:photo_id, :user_id, :comment)')->execute([
                'photo_id' => $photoId,
                'user_id' => $currentUser['id'],
                'comment' => $comment,
            ]);
        }
        redirect('/app/public/gallery.php');
    }

    if ($action === 'reply_comment') {
        $photoId = (int) ($_POST['photo_id'] ?? 0);
        $parentId = (int) ($_POST['parent_comment_id'] ?? 0);
        $reply = trim($_POST['reply'] ?? '');
        if ($photoId > 0 && $parentId > 0 && $reply !== '') {
            $pdo->prepare('INSERT INTO gallery_comments (photo_id, user_id, parent_comment_id, comment) VALUES (:photo_id, :user_id, :parent_comment_id, :comment)')->execute([
                'photo_id' => $photoId,
                'user_id' => $currentUser['id'],
                'parent_comment_id' => $parentId,
                'comment' => $reply,
            ]);
        }
        redirect('/app/public/gallery.php');
    }

    if ($action === 'toggle_comment_like') {
        $commentId = (int) ($_POST['comment_id'] ?? 0);
        if ($commentId > 0) {
            $exists = $pdo->prepare('SELECT id FROM gallery_comment_likes WHERE comment_id = :comment_id AND user_id = :user_id LIMIT 1');
            $exists->execute(['comment_id' => $commentId, 'user_id' => $currentUser['id']]);
            if ($exists->fetch()) {
                $pdo->prepare('DELETE FROM gallery_comment_likes WHERE comment_id = :comment_id AND user_id = :user_id')->execute(['comment_id' => $commentId, 'user_id' => $currentUser['id']]);
            } else {
                $pdo->prepare('INSERT INTO gallery_comment_likes (comment_id, user_id) VALUES (:comment_id, :user_id)')->execute([
                    'comment_id' => $commentId,
                    'user_id' => $currentUser['id'],
                ]);
            }
        }
        redirect('/app/public/gallery.php');
    }
}

$photos = $pdo->query('SELECT gp.*, u.name AS uploader_name, (SELECT COUNT(*) FROM gallery_photo_likes gpl WHERE gpl.photo_id = gp.id) AS like_count, (SELECT COUNT(*) FROM gallery_comments gc WHERE gc.photo_id = gp.id) AS comment_count FROM gallery_photos gp INNER JOIN users u ON u.id = gp.uploader_id ORDER BY gp.created_at DESC')->fetchAll();

$photoIds = array_map(static fn($photo) => (int) $photo['id'], $photos);
$userLikedPhotos = [];
if ($photoIds) {
    $photoPlaceholders = [];
    foreach ($photoIds as $index => $photoId) {
        $photoPlaceholders[] = ':photo_' . $index;
    }
    $stmt = $pdo->prepare('SELECT photo_id FROM gallery_photo_likes WHERE user_id = :user_id AND photo_id IN (' . implode(',', $photoPlaceholders) . ')');
    $params = ['user_id' => $currentUser['id']];
    foreach ($photoIds as $index => $photoId) {
        $params['photo_' . $index] = $photoId;
    }
    $stmt->execute($params);
    foreach ($stmt->fetchAll() as $row) {
        $userLikedPhotos[(int) $row['photo_id']] = true;
    }
}

$commentsByPhoto = [];
if ($photoIds) {
    $commentPhotoPlaceholders = [];
    foreach ($photoIds as $index => $photoId) {
        $commentPhotoPlaceholders[] = ':photo_' . $index;
    }
    $commentStmt = $pdo->prepare('SELECT gc.*, u.name AS commenter_name, (SELECT COUNT(*) FROM gallery_comment_likes gcl WHERE gcl.comment_id = gc.id) AS like_count FROM gallery_comments gc INNER JOIN users u ON u.id = gc.user_id WHERE gc.photo_id IN (' . implode(',', $commentPhotoPlaceholders) . ') ORDER BY gc.created_at ASC');
    $commentParams = [];
    foreach ($photoIds as $index => $photoId) {
        $commentParams['photo_' . $index] = $photoId;
    }
    $commentStmt->execute($commentParams);
    $comments = $commentStmt->fetchAll();
    foreach ($comments as $comment) {
        $commentsByPhoto[(int) $comment['photo_id']][] = $comment;
    }
}

$userLikedComments = [];
$commentIds = [];
foreach ($commentsByPhoto as $photoComments) {
    foreach ($photoComments as $comment) {
        $commentIds[] = (int) $comment['id'];
    }
}
if ($commentIds) {
    $commentLikePlaceholders = [];
    foreach ($commentIds as $index => $commentId) {
        $commentLikePlaceholders[] = ':comment_' . $index;
    }
    $commentLikeStmt = $pdo->prepare('SELECT comment_id FROM gallery_comment_likes WHERE user_id = :user_id AND comment_id IN (' . implode(',', $commentLikePlaceholders) . ')');
    $commentLikeParams = ['user_id' => $currentUser['id']];
    foreach ($commentIds as $index => $commentId) {
        $commentLikeParams['comment_' . $index] = $commentId;
    }
    $commentLikeStmt->execute($commentLikeParams);
    foreach ($commentLikeStmt->fetchAll() as $row) {
        $userLikedComments[(int) $row['comment_id']] = true;
    }
}

include __DIR__ . '/../views/partials/header.php';
?>
<div class="page-shell">
    <div class="dash-header">
        <h1>Gallery</h1>
        <span class="badge">Shared memories</span>
    </div>

    <?php if ($photos): ?>
        <div class="gallery-grid-layout">
            <?php foreach ($photos as $photo): ?>
                <?php
                $photoId = (int) $photo['id'];
                $liked = isset($userLikedPhotos[$photoId]);
                $photoComments = $commentsByPhoto[$photoId] ?? [];
                ?>
                <article class="gallery-card">
                    <img src="<?php echo app_url($photo['image_path']); ?>" alt="<?php echo e($photo['caption'] ?: 'Gallery photo'); ?>" class="gallery-photo">
                    <div class="gallery-body">
                        <div class="gallery-meta-row">
                            <div>
                                <strong><?php echo e($photo['uploader_name']); ?></strong><br>
                                <small><?php echo e(format_datetime($photo['created_at'])); ?></small>
                            </div>
                            <div class="gallery-share-wrap">
                                <?php $shareUrl = app_url($photo['image_path']) . '?utm_source=gallery&utm_medium=share&utm_campaign=photo'; ?>
                                <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode($shareUrl); ?>" target="_blank" rel="noopener" class="small-link">Facebook</a>
                                <a href="https://wa.me/?text=<?php echo urlencode($shareUrl); ?>" target="_blank" rel="noopener" class="small-link">WhatsApp</a>
                                <button type="button" class="small-link share-button" data-share-url="<?php echo e($shareUrl); ?>">Other apps</button>
                                <button type="button" class="small-link" data-copy="<?php echo e($shareUrl); ?>">Copy link</button>
                            </div>
                        </div>

                        <?php if (!empty($photo['caption'])): ?>
                            <p class="gallery-caption"><?php echo e($photo['caption']); ?></p>
                        <?php endif; ?>

                        <div class="gallery-actions">
                            <form method="post" class="inline-form">
                                <?php csrf_field(); ?>
                                <input type="hidden" name="action" value="toggle_like">
                                <input type="hidden" name="photo_id" value="<?php echo $photoId; ?>">
                                <button type="submit" class="btn btn-secondary"><?php echo $liked ? 'Unlike' : 'Like'; ?></button>
                            </form>
                            <span><?php echo (int) $photo['like_count']; ?> likes</span>
                        </div>

                        <div class="comments-box">
                            <h3>Comments</h3>
                            <?php if ($photoComments): ?>
                                <?php foreach ($photoComments as $comment): ?>
                                    <?php if ((int) $comment['parent_comment_id'] === 0): ?>
                                        <div class="comment-item">
                                            <div class="comment-header">
                                                <strong><?php echo e($comment['commenter_name']); ?></strong>
                                                <small><?php echo e(format_datetime($comment['created_at'])); ?></small>
                                            </div>
                                            <p><?php echo nl2br(e($comment['comment'])); ?></p>
                                            <div class="comment-actions">
                                                <form method="post" class="inline-form">
                                                    <?php csrf_field(); ?>
                                                    <input type="hidden" name="action" value="toggle_comment_like">
                                                    <input type="hidden" name="comment_id" value="<?php echo (int) $comment['id']; ?>">
                                                    <button type="submit" class="small-link"><?php echo isset($userLikedComments[(int) $comment['id']]) ? 'Unlike' : 'Like'; ?></button>
                                                </form>
                                                <span>(<?php echo (int) $comment['like_count']; ?>)</span>
                                            </div>

                                            <?php foreach ($photoComments as $reply): ?>
                                                <?php if ((int) $reply['parent_comment_id'] === (int) $comment['id']): ?>
                                                    <div class="reply-item">
                                                        <div class="comment-header">
                                                            <strong><?php echo e($reply['commenter_name']); ?></strong>
                                                            <small><?php echo e(format_datetime($reply['created_at'])); ?></small>
                                                        </div>
                                                        <p><?php echo nl2br(e($reply['comment'])); ?></p>
                                                        <div class="comment-actions">
                                                            <form method="post" class="inline-form">
                                                                <?php csrf_field(); ?>
                                                                <input type="hidden" name="action" value="toggle_comment_like">
                                                                <input type="hidden" name="comment_id" value="<?php echo (int) $reply['id']; ?>">
                                                                <button type="submit" class="small-link"><?php echo isset($userLikedComments[(int) $reply['id']]) ? 'Unlike' : 'Like'; ?></button>
                                                            </form>
                                                            <span>(<?php echo (int) $reply['like_count']; ?>)</span>
                                                        </div>
                                                    </div>
                                                <?php endif; ?>
                                            <?php endforeach; ?>

                                            <form method="post" class="reply-form">
                                                <?php csrf_field(); ?>
                                                <input type="hidden" name="action" value="reply_comment">
                                                <input type="hidden" name="photo_id" value="<?php echo $photoId; ?>">
                                                <input type="hidden" name="parent_comment_id" value="<?php echo (int) $comment['id']; ?>">
                                                <textarea name="reply" rows="2" placeholder="Write a reply..."></textarea>
                                                <button type="submit" class="btn btn-primary">Reply</button>
                                            </form>
                                        </div>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p class="empty-state">No comments yet.</p>
                            <?php endif; ?>

                            <form method="post" class="comment-form">
                                <?php csrf_field(); ?>
                                <input type="hidden" name="action" value="add_comment">
                                <input type="hidden" name="photo_id" value="<?php echo $photoId; ?>">
                                <textarea name="comment" rows="2" placeholder="Write a comment..."></textarea>
                                <button type="submit" class="btn btn-primary">Comment</button>
                            </form>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="empty-state">No photos uploaded yet.</div>
    <?php endif; ?>
</div>

<script>
    document.addEventListener('click', function (event) {
        const shareButton = event.target.closest('.share-button');
        if (!shareButton) {
            return;
        }

        const url = shareButton.dataset.shareUrl;
        if (navigator.share) {
            navigator.share({
                title: 'Praise Team Gallery',
                text: 'Check out this photo from the Praise Team gallery.',
                url: url,
            }).catch(function () {});
            return;
        }

        if (navigator.clipboard && url) {
            navigator.clipboard.writeText(url).then(function () {
                shareButton.textContent = 'Link copied';
                setTimeout(function () {
                    shareButton.textContent = 'Other apps';
                }, 1500);
            }).catch(function () {});
        }
    });
</script>

<?php include __DIR__ . '/../views/partials/footer.php'; ?>
