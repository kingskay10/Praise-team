    </main>

    <div class="bottom-toolbar">
        <button type="button" id="themeToggle" class="btn btn-secondary theme-toggle" aria-label="Toggle dark mode">
            Dark Mode
        </button>
        <?php if (is_authenticated()): ?>
            <form method="post" action="<?php echo app_url('/app/public/logout.php'); ?>">
                <?php csrf_field(); ?>
                <button type="submit" class="btn btn-danger">Logout</button>
            </form>
        <?php endif; ?>
    </div>

    <div class="cookie-notice" id="cookieNotice" role="status" hidden>
        <span>We use a small preference cookie to remember your theme choice.</span>
        <button type="button" class="btn btn-secondary" id="dismissCookie">OK</button>
    </div>
    <a class="contact-float" href="mailto:praise-team@example.com" aria-label="Contact the praise team">Contact</a>
    <button type="button" class="back-to-top" id="backToTop" aria-label="Back to top" hidden>&uarr;</button>

    <footer class="site-footer">
        <span>&copy; <?php echo date('Y'); ?> St Andrews CCAP Praise Team</span>
        <span><a href="<?php echo app_url('/app/public/terms.php'); ?>">Terms</a> <a href="<?php echo app_url('/app/public/privacy.php'); ?>">Privacy</a></span>
    </footer>

    <script src="<?php echo app_url('assets/js/site.js'); ?>" defer></script>
</body>
</html>
