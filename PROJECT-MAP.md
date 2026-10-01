# Source project and PHP conversion

## Original archive

The supplied application is a React/Vite single-page frontend, an Express/Node API, and a PostgreSQL database. Its source tree includes the route components, six stylesheet files, source image assets, the 224-topic catalog, 35 categories, sample learning resources, and route modules for authentication, catalog, user, admin and teacher/reviewer workspace behavior.

### Original route families

- **Public:** Home, Indian/Gurukul/Nepal education explorers, category and course catalogs, course detail, lessons/PDF, live classes, and information pages.
- **Accounts:** student signup, sign-in, forgot/reset password, and separate student/teacher/reviewer/admin sign-in URLs.
- **Student:** dashboard, profile, bookmarks, learning history, quiz/paper, assignments/exams, results and certificates.
- **Teacher:** dashboard, courses/course builder, syllabus/content library, question bank/exams, assignments/live classes, results, people/messages/settings and AI tools.
- **Reviewer:** submitted, approval, revision, approved and published queues, plus people/results/messages.
- **Admin:** content, membership/payments, approvals, people, results and workspace settings.

PostgreSQL tables in the source cover accounts/sessions, the course catalog, lessons/questions, progress/bookmarks/quiz results, certificates, memberships/study paths and the teacher/reviewer workflow. The API uses role and ownership checks.

## Converted files

- `app.php` and `index.php`: PHP route rendering and form actions.
- `public/index.php`: clean-route front controller for PHP's local server or Apache.
- `public/styles/`: all six original stylesheets copied byte-for-byte and included in the original load order.
- `public/images/`, `public/data/`, `public/samples/`: source artwork, address data and supplied lesson samples.
- `database/schema.sql`: MySQL tables for the catalog, students/staff, progress, quizzes, certificates, memberships and workflow records.
- `database/seed.php`: source-compatible course-cover selection and import of source category/catalog records.

The PHP edition preserves the source home composition, public header and bottom navigation, desktop/mobile portal shell, course/lesson view families, account forms and role-gated workspace navigation. It also connects course browsing, lessons, bookmarks, completion, quiz submissions, assignments, papers, certificates and the teacher/reviewer publishing path to MySQL where implemented.

## Differences to keep in mind

Changing frameworks is a rewrite, so exact pixel and behavior parity cannot be guaranteed from this conversion alone. Several detailed React workflows still need full route-by-route conversion, including OTP delivery, password-reset email, the full profile/address verification experience, teacher course-builder steps/question bank, AI tools, payment provider, and detailed analytics/settings. Some staff navigation items currently show a workspace placeholder until their original screen is ported. See the local run and portal notes in [README.md](README.md).
