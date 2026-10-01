-- Adds the roles needed by the separate academy login portals.
-- Existing users and their current role values are preserved.
ALTER TABLE users
  MODIFY role ENUM(
    'student',
    'teacher',
    'course_creator',
    'reviewer',
    'timetable_manager',
    'accounts',
    'admin'
  ) NOT NULL DEFAULT 'student';

-- Assign existing staff accounts by their email address, for example:
-- UPDATE users SET role = 'teacher' WHERE email = 'teacher@example.com';
-- UPDATE users SET role = 'reviewer' WHERE email = 'approvals@example.com';
-- UPDATE users SET role = 'timetable_manager' WHERE email = 'timetable@example.com';
-- UPDATE users SET role = 'accounts' WHERE email = 'accounts@example.com';
-- UPDATE users SET role = 'admin' WHERE email = 'admin@example.com';
