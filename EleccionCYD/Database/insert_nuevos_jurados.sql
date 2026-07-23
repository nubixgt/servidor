-- EleccionCYD - Alta de 3 jurados nuevos
-- Hashes generados con PHP password_hash() (bcrypt), compatible con password_verify()
-- usado en Backend/src/Services/AuthService.php.
--
-- Credenciales en texto plano (solo para que hagas las pruebas de login; no se guardan así en la BD):
--   usuario: pflores   | password: Pflores2026!  | Pamela Flores
--   usuario: emorela   | password: Emorela2026!  | Edin Mórela
--   usuario: gtobias   | password: Gtobias2026!  | Gilary Tobías

INSERT INTO usuarios (usuario, password, nombre, rol) VALUES
('pflores', '$2y$10$/ET3pmMwIxhGyVu1nVRFou47qjNBT4/txZDoGWxck8dDY49bb8/Vu', 'Pamela Flores', 'admin'),
('emorela', '$2y$10$jOQwcctOhesEwK/AOQxqRe.1xUSktvx5hovn7Vfx7BdY8GdHuUDJC', 'Edin Mórela', 'admin'),
('gtobias', '$2y$10$51E.GIio55mJJ1pXS1yQHOx5HjJOxwKGWU9E/s6.EOVHCuA8QdlTe', 'Gilary Tobías', 'admin');
