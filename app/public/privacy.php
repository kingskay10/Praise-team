<?php
require_once __DIR__ . '/../includes/bootstrap.php';
include __DIR__ . '/../views/partials/header.php';
?>
<div class="card legal-page">
    <h1>Privacy Policy</h1>
    <p><strong>Last updated:</strong> <?php echo date('F j, Y'); ?></p>
    <h2>Information collected</h2>
    <p>The portal may store your name, email address, phone number, location, social handles, role, profile and gallery photos, verses, and finance records entered by authorized staff.</p>
    <h2>How information is used</h2>
    <p>Information is used to operate the praise team directory, coordinate worship activities, publish approved updates and reports, and maintain church finance records.</p>
    <h2>Storage and security</h2>
    <p>Passwords are stored as one-way password hashes. Application access is role-based, and uploads are validated. Administrators should use HTTPS, strong database credentials, restricted file permissions, and regular backups.</p>
    <h2>Who can see information</h2>
    <p>Members can see information made available in the team directory and shared gallery. The chair can manage member and event information. The secretary can access finance records. Directors can access their department plans and reports. Public visitors can see only published public content.</p>
    <h2>Your choices</h2>
    <p>To correct or remove your personal information, or to request account deletion, contact the church praise team administrator. Some records may need to be retained for church administration or legal obligations.</p>
    <h2>Contact</h2>
    <p>Contact the Praise Team through the email link provided on this site with privacy questions or requests.</p>
</div>
<?php include __DIR__ . '/../views/partials/footer.php'; ?>
