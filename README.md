# Praise Team Website (MVP)

This project is a lean PHP + MySQL church praise team portal with role-based dashboards.

## Structure

- `app/config/config.php` – application config
- `app/includes/` – database, auth bootstrap, helpers
- `app/public/` – public pages and protected dashboard routes
- `app/views/partials/` – shared layout partials
- `assets/css/styles.css` – site styling
- `database/schema.sql` – MySQL schema

## Local setup

1. Create the MySQL database:
   - Open phpMyAdmin or MySQL CLI
   - Import `database/schema.sql`
2. Update the database credentials in `app/config/config.php` if needed.
3. Run the project from a PHP server or WAMP document root.
4. Visit: `http://localhost/Praise_team/app/public/index.php`

## Default roles

- Chair
- Secretary
- Music Director
- Band/Worship Director
- Member

Only published reports are visible on the public landing page. Internal department data remains protected in role-specific dashboards.
