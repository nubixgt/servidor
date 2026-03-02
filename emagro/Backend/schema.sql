-- roles: Almacena los roles del sistema identificados en la UI (Administrador, Vendedor).
CREATE TABLE roles (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(50) NOT NULL
);

-- privileges: Permisos granulares inferidos de la UI.
CREATE TABLE privileges (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(50) NOT NULL UNIQUE
);

-- role_privileges: Tabla intermedia que une roles con sus permisos asignados.
CREATE TABLE role_privileges (
  role_id INT NOT NULL,
  privilege_id INT NOT NULL,
  PRIMARY KEY (role_id, privilege_id),
  FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
  FOREIGN KEY (privilege_id) REFERENCES privileges(id) ON DELETE CASCADE
);

-- users: Almacena las credenciales y perfiles de los usuarios (login y gestión de usuarios).
CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(100) NOT NULL UNIQUE, -- ej: nombre.apellido
  password_hash VARCHAR(255) NOT NULL,
  email VARCHAR(150) UNIQUE,
  role_id INT NOT NULL,
  status VARCHAR(20) DEFAULT 'Activo', -- 'Activo', 'Bloqueado'
  avatar_url VARCHAR(255),
  FOREIGN KEY (role_id) REFERENCES roles(id)
);

-- clients: Directorio de clientes con sus límites de crédito y estatus.
CREATE TABLE clients (
  id INT AUTO_INCREMENT PRIMARY KEY,
  client_code VARCHAR(20) UNIQUE, -- ID visible ej: #C001
  name VARCHAR(150) NOT NULL,
  company_name VARCHAR(150),
  nit VARCHAR(20),
  phone VARCHAR(20),
  address TEXT,
  credit_limit DECIMAL(10, 2),
  status VARCHAR(20) DEFAULT 'Activo',
  avatar_url VARCHAR(255)
);

-- categories: Agrupación de productos (Fertilizantes, Semillas, Herramientas, Otros).
CREATE TABLE categories (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL
);

-- products: Catálogo de productos agrícolas con precios y existencia aparente.
CREATE TABLE products (
  id INT AUTO_INCREMENT PRIMARY KEY,
  category_id INT NOT NULL,
  sku VARCHAR(50) UNIQUE,
  name VARCHAR(150) NOT NULL,
  description TEXT,
  price DECIMAL(10, 2) NOT NULL,
  stock_status VARCHAR(20) DEFAULT 'En Stock', -- 'En Stock', 'Agotado'
  image_url VARCHAR(255),
  FOREIGN KEY (category_id) REFERENCES categories(id)
);

-- sales: Registro maestro de ventas o facturas mostradas en Dashboard e Historial.
CREATE TABLE sales (
  id INT AUTO_INCREMENT PRIMARY KEY,
  invoice_number VARCHAR(20) UNIQUE, -- ej: #00041
  client_id INT NOT NULL,
  seller_id INT NOT NULL,
  sale_date DATETIME NOT NULL,
  total_amount DECIMAL(10, 2) NOT NULL,
  status VARCHAR(20) DEFAULT 'Completado', -- 'Completado', 'Pendiente'
  FOREIGN KEY (client_id) REFERENCES clients(id),
  FOREIGN KEY (seller_id) REFERENCES users(id)
);

-- sale_details: Items vendidos en cada venta (Implícito por el total de las ventas e ítems vendidos en Dashboard).
CREATE TABLE sale_details (
  id INT AUTO_INCREMENT PRIMARY KEY,
  sale_id INT NOT NULL,
  product_id INT NOT NULL,
  quantity INT NOT NULL,
  unit_price DECIMAL(10, 2) NOT NULL,
  FOREIGN KEY (sale_id) REFERENCES sales(id),
  FOREIGN KEY (product_id) REFERENCES products(id)
);

-- payments: Seguimiento de cobros recurrentes y deudas.
CREATE TABLE payments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  payment_number VARCHAR(20) UNIQUE, -- ej: #P-2026-001
  client_id INT NOT NULL,
  concept VARCHAR(255) NOT NULL,
  due_date DATE NOT NULL,
  amount DECIMAL(10, 2) NOT NULL,
  status VARCHAR(20) DEFAULT 'Pendiente', -- 'Pagado', 'Pendiente', 'Vencido'
  FOREIGN KEY (client_id) REFERENCES clients(id)
);
