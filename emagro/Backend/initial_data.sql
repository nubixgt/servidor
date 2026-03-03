-- roles: Enables the assignment of system access levels required for user management in "Usuarios.vue".
INSERT INTO roles (name) VALUES 
('Administrador'),
('Vendedor');

-- privileges: Fine-grained capabilities.
INSERT INTO privileges (name) VALUES 
('manage_users');

-- role_privileges: Map 'manage_users' strictly to the 'Administrador' role (ID=1).
INSERT INTO role_privileges (role_id, privilege_id) VALUES 
(1, 1);

-- categories: Provides the static lookup grouping values required to filter and classify inventory items in "Catalogo.vue" and "Dashboard.vue".
INSERT INTO categories (name) VALUES 
('Fertilizantes'),
('Semillas'),
('Herramientas'),
('Otros');
