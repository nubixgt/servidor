-- 1. PRIVILEGES TABLE
-- Holds the exact UI-driven actions capable of being secured natively by the backend attributes.
CREATE TABLE privileges (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE
);

-- 2. ROLE TO PRIVILEGE MAPPING
-- Ties the strings `admin` and `tech` from the `users` table ENUM to the relational privileges.
CREATE TABLE role_privileges (
    id INT AUTO_INCREMENT PRIMARY KEY,
    role_name VARCHAR(50) NOT NULL,
    privilege_id INT NOT NULL,
    FOREIGN KEY (privilege_id) REFERENCES privileges(id) ON DELETE CASCADE
);

-- 3. SEED REQUIRED ACTIONS
INSERT INTO privileges (id, name) VALUES
(1, 'view_dashboard_admin'),
(2, 'view_reports'),
(3, 'manage_users'),
(4, 'create_financial_transaction'),
(5, 'view_locations'),
(6, 'view_dashboard_tech'),
(7, 'create_asset_transaction');

-- 4. ASSOCIATE ADMIN PRIVILEGES
INSERT INTO role_privileges (role_name, privilege_id) VALUES
('admin', 1),
('admin', 2),
('admin', 3),
('admin', 4),
('admin', 5);

-- 5. ASSOCIATE TECH PRIVILEGES
INSERT INTO role_privileges (role_name, privilege_id) VALUES
('tech', 5),
('tech', 6),
('tech', 7);
