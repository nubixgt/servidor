-- Run this ONCE after 001_create_users_table.sql, edited with your real Google account.
-- google_sub is unknown until you actually log in with Google for the first time
-- (that's the only place the app can read it from), so this seeds a placeholder
-- that gets overwritten automatically on your first successful Google login.
--
-- Steps:
--   1. Replace 'tu_correo@gmail.com' and 'Tu Nombre' below.
--   2. Run this script.
--   3. Open the app and log in with Google using that exact email. The backend
--      will find this row by email, fill in the real google_sub, and let you in
--      as admin immediately (no manual approval needed for this first account).

INSERT INTO users (google_sub, email, name, role, status, approved_at)
VALUES (
    CONCAT('pending-first-login-', UNIX_TIMESTAMP()),
    'm1gu3l.fu3nt3s@gmail.com',
    'Miguel',
    'admin',
    'approved',
    NOW()
);
