# Ashwamedh Gurukul eLearning

## Run locally on Windows with XAMPP

The site requires PHP 8.0 or newer with PDO MySQL and mbstring, and MySQL 5.7.8 or compatible MariaDB.

1. Install XAMPP, then extract this project to C:\xampp\htdocs\elearning.
2. Start MySQL from the XAMPP Control Panel.
3. Open http://localhost/phpmyadmin, create a database named ashwamedh, and import database/schema.sql.
4. Copy config.example.php to config.php. Edit config.php with your local MySQL username and password. A default XAMPP installation commonly uses username root and a blank password.
5. Open XAMPP Shell and run:

       cd C:\xampp\htdocs\elearning
       php database\seed.php
       php -S 127.0.0.1:8000 -t public public\index.php

6. Open http://127.0.0.1:8000 in your browser. Keep the XAMPP Shell window open while using the site; press Ctrl+C to stop it.

The seed command imports the supplied curriculum. Run it once for a newly created local database.

## Using an existing database export

If you import an existing database backup instead of database/schema.sql, import database/migrations/20261001_role_portals.sql once in phpMyAdmin afterward. That migration adds the new staff roles while preserving existing accounts and role values. Do not import the fresh-install schema over an existing database.

Staff registration is intentionally unavailable. To grant a staff role to an existing account, use phpMyAdmin's SQL page and replace the sample email:

    UPDATE users SET role = 'teacher' WHERE email = 'teacher@example.com';
    UPDATE users SET role = 'reviewer' WHERE email = 'approvals@example.com';
    UPDATE users SET role = 'timetable_manager' WHERE email = 'timetable@example.com';
    UPDATE users SET role = 'accounts' WHERE email = 'accounts@example.com';
    UPDATE users SET role = 'admin' WHERE email = 'admin@example.com';

## Login and registration pages

| Role | Login | Registration | Dashboard |
| --- | --- | --- | --- |
| Student | /login | /signup | /dashboard |
| Teacher | /teacher/login | — | /teacher |
| Syllabus / Course Creator | /creator/login | /creator/signup | /creator |
| Syllabus / Course Approval Department | /review/login | — | /review |
| Time Table Manager | /timetable/login | — | /timetable |
| Accounts | /accounts/login | — | /accounts |
| Super Admin | /super-admin/login | — | /super-admin |

The timetable and accounts dashboards currently provide role-specific summaries and navigation placeholders. Their full scheduling and accounting workflows are not part of this login update.

## Project files

- index.php, app.php: route dispatch and PHP-rendered pages.
- includes/bootstrap.php: MySQL connection, sessions, CSRF and role checks.
- database/schema.sql: fresh-install database schema, including all portal roles.
- database/migrations/20261001_role_portals.sql: one-time role update for an existing database.
- database/seed.php: curriculum importer.
- public/: styles, images, data, sample resources and JavaScript.

The project uses PHP, MySQL, HTML, CSS and JavaScript; it does not require React, Node.js or PostgreSQL.
