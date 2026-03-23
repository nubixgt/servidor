-- 1. USERS --
-- Mandatory accounts to fulfill the frontend's hardcoded login and activity tracking logic.
INSERT INTO users (id, username, password_hash, name, email, role, status) VALUES 
(1, 'admin.user', '$2y$10$dummyAdminHash', 'Admin User', 'admin@empresa.com', 'admin', 'Activo'),
(2, 'tech.user', '$2y$10$dummyTechHash', 'Tech User', 'tech@empresa.com', 'tech', 'Activo'),
(3, 'ana.gomez', '$2y$10$dummyHash', 'Ana Gómez', 'ana.gomez@empresa.com', 'admin', 'Activo'),
(4, 'carlos.ruiz', '$2y$10$dummyHash', 'Carlos Ruiz', 'carlos.ruiz@empresa.com', 'tech', 'Activo'),
(5, 'maria.lopez', '$2y$10$dummyHash', 'María López', 'maria.lopez@empresa.com', 'tech', 'Activo');

-- 2. LOCATIONS --
-- Static locations hardcoded into the New Transaction form dropdowns and Tech Dashboard activity logs.
-- Uses predetermined IDs to satisfy exact Foreign Key relationships.
INSERT INTO locations (id, code, type, name, address, status, responsible_id, tenant_name, monthly_rent) VALUES 
(1, 'H-01', 'Heladería', 'Heladería CC Pradera', 'CC Pradera, Guatemala', 'Activa', 3, NULL, NULL),
(2, 'H-02', 'Heladería', 'Heladería Gasolinera Texaco', 'Gasolinera Texaco', 'Activa', 4, NULL, NULL),
(3, 'H-03', 'Heladería', 'Heladería Tecpán', 'Tecpán Guatemala, Chimaltenango', 'Activa', 5, NULL, NULL),
(4, 'A-01', 'Casa', 'Casa en Arrendamiento 1', 'Zona 14, Ciudad', 'Ocupada', NULL, 'Familia Pérez', 5000.00),
(5, 'A-02', 'Casa', 'Casa en Arrendamiento 2', 'Zona 15, Ciudad', 'Disponible', NULL, NULL, 6500.00),
(6, 'A-03', 'Casa', 'Casa en Arrendamiento 3', 'Carretera a El Salvador', 'Mantenimiento', NULL, NULL, 4800.00),
(7, 'L-01', 'Local', 'Local L-01', NULL, 'Ocupada', NULL, 'Cafetería El Grano', 3500.00),
(8, 'B-01', 'Bodega', 'Bodega B-01', NULL, 'Ocupada', NULL, 'Logística S.A.', 2000.00),
(9, 'W-01', 'Bodega', 'Almacén Principal', 'Central', 'Activa', NULL, NULL, NULL),
(10, 'T-01', 'Local', 'Taller B', 'Sector Industrial', 'Activa', NULL, NULL, NULL);

-- 3. ASSETS --
-- Required inventory base enabling the Tech Dashboard's "Ingreso/Egreso" tracking logic.
INSERT INTO assets (id, name, current_location_id) VALUES 
(1, 'Lector de Código', 9),     -- Almacén Principal
(2, 'Caja de Herramientas', 10),  -- Taller B
(3, 'Taladro Inalámbrico', 9);    -- Almacén Principal
