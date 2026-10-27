# St Andrews CCAP Praise Team

The active GitHub Pages site is a static HTML/CSS/vanilla JavaScript frontend in `docs/`, backed by Supabase for Postgres, authentication, and storage. The former PHP + MySQL application is preserved in `php-original/`.

## Features found in the original PHP application

- Public home page with today's verse, upcoming/past events, recent updates, published department reports, and FAQs.
- Email/password login, logout, and role-based dashboard routing for chair, secretary, music director, band/worship director, and member.
- Member-only directory and community photo gallery with likes, comments, replies, sharing, and copy-link actions.
- Member workspace for profile updates, profile/gallery photo uploads, and daily verse submissions.
- Chair tools for the directory, event creation with poster/attachment, public updates, and approving department reports.
- Secretary-only finance records for monthly contributions and offerings, plus financial report submission.
- Music and band director workspaces for department plans and reports.
- Public search, privacy policy, and terms pages.

## Supabase setup

1. Create a Supabase project at [supabase.com](https://supabase.com/). Choose a strong database password and keep it private.
2. In the Supabase project, open **SQL Editor**, create a new query, paste all of `supabase/schema.sql`, and run it once. It creates the PostgreSQL tables, auth profile/role trigger, row-level security policies, and the two storage buckets.
3. In **Storage**, confirm these buckets exist and have these settings:
   - `event-media`: public, 10 MB maximum; JPEG, PNG, WEBP, PDF, DOC, and DOCX.
   - `member-media`: private, 10 MB maximum; JPEG, PNG, and WEBP.
   The SQL applies storage object policies; do not make `member-media` public.
4. Open **Project Settings > API**. Copy the Project URL and the `anon`/publishable public key into `docs/js/config.js`. Do not put a `service_role` or secret key in any file under `docs/` or in browser JavaScript.
5. Create the first chair account in **Authentication > Users > Add user**. Use the chair's name, email, and a long unique password. The schema trigger creates a profile and default `member` role.
6. In **SQL Editor**, run this one-time role assignment, replacing the email:

   ```sql
   update public.roles
   set role = 'chair'
   where user_id = (
     select id from public.profiles
     where email = 'chair@example.org'
   );
   ```

7. Sign in at `docs/login.html`. The chair can edit member profiles and assign roles in the chair dashboard. Create each additional Auth account in **Authentication > Users > Add user** first; it will receive the default member role. Role changes are managed from the chair dashboard.
8. In **Authentication > Settings**, disable public sign-ups if only chair-provisioned team accounts should be allowed. Create/invite accounts through **Authentication > Users**.
9. Enable **Authentication > URL Configuration** as needed for your deployed site. Add the GitHub Pages site URL (`https://USERNAME.github.io/REPOSITORY-NAME/`) to the allowed redirect/site URL configuration.
10. Commit and push the project to the `main` branch on GitHub.
11. In GitHub, open **Settings > Pages > Build and deployment**, choose **Deploy from a branch**, select branch **main** and folder **/docs**, then select **Save**.
12. After the Pages deployment completes, visit `https://USERNAME.github.io/REPOSITORY-NAME/` (replace both uppercase segments with your GitHub username and repository name).

GitHub Pages is static hosting; it does not execute PHP. The public website uses only the Supabase anon key, and database/storage RLS is the security boundary. The chair's initial role is assigned in Supabase SQL Editor because a browser-only client must not be allowed to self-assign privileged roles.

## Data migration and privacy

The MySQL schema is `database/schema.sql`. The PostgreSQL equivalent is `supabase/schema.sql`, but existing MySQL rows and passwords are not migrated automatically. Password hashes are incompatible with Supabase Auth; create/invite users in Supabase and migrate only approved profile/content data using a trusted one-time migration process. Do not copy MySQL password hashes into `profiles`.

Never commit real member names, email addresses, phone numbers, profile/gallery photos, passwords, or financial/contribution/offerings records to a public repository. Keep Supabase backups and exports private. The root `.gitignore` excludes `uploads/`, local environment files, private key files, and common SQL backup/dump files; `.gitignore` does not remove data that was already committed.

## Security scan

The inspected legacy config `php-original/config/config.php` reads database settings from `PRAISE_DB_*` environment variables and uses an empty local password fallback; it contained no literal database password, API key, or service-role key. The repository had no `.gitignore` before this migration, so one was added. Keep credentials out of tracked files and rotate any value that was ever committed publicly.

## Features that need an admin-side workaround

- Creating Auth users or resetting credentials cannot be done from the static chair dashboard: create/invite users in Supabase **Authentication > Users**. The chair can edit their profile and role after their first sign-in/profile trigger.
- The original PHP app includes server-side form/CSRF handling and MySQL password verification. Supabase Auth replaces password handling, and RLS replaces server-side row authorization; the browser never receives a service-role key.
- Existing MySQL records and account passwords require a separate, trusted migration. The static app does not import private data automatically.

For local preview, serve `docs/` from a static web server; opening pages directly from `file://` can block browser requests to Supabase. No build step is required.
