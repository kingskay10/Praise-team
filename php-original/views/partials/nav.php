<header class="topbar" id="site-header">
    <div class="brand-wrap">
        <a href="<?php echo app_url('/app/public/index.php'); ?>" class="brand">ST ANDREWS CCAP PRAISE TEAM</a>
    </div>
   <!-- <button type="button" class="nav-toggle" id="navToggle" aria-expanded="false" aria-controls="siteNav" aria-label="Open menu">&#9776;</button>
    <nav class="topnav" id="siteNav"> -->
        <a href="<?php echo app_url('/app/public/index.php'); ?>">Home</a>
        <a href="<?php echo app_url('/app/public/members.php'); ?>">Members</a>
        <?php if (is_authenticated()): ?>
            <a href="<?php echo app_url('/app/public/gallery.php'); ?>">Gallery</a>
            <a href="<?php echo app_url('/app/public/dashboard.php'); ?>">Dashboard</a>
        <?php else: ?>
            <a href="<?php echo app_url('/app/public/login.php'); ?>">Login</a>
        <?php endif; ?>
        <form class="site-search" action="<?php echo app_url('/app/public/search.php'); ?>" method="get" role="search">
            <label class="sr-only" for="site-search-input">Search site</label>
            <input id="site-search-input" name="q" type="search" placeholder="Search" minlength="2">
            <button type="submit" aria-label="Search">Search</button>
        </form>
    </nav>
</header>
