-- 1. USERS TABLE
-- Stores system users, their roles, and authentication data.
-- Sourced from: Login screen, Admin Users screen.
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE, -- e.g. "nombre.apellido" from Login
    password_hash VARCHAR(255) NOT NULL,
    name VARCHAR(150) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    role ENUM('admin', 'tech') NOT NULL,
    status ENUM('Activo', 'Inactivo') DEFAULT 'Activo',
    last_login_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 2. LOCATIONS TABLE
-- Stores physical and commercial locations. Unifies ice cream shops, houses, and commercial units.
-- Sourced from: Admin Locations screen, New Transaction forms.
CREATE TABLE locations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(20) NOT NULL UNIQUE,     -- e.g. 'H-01', 'L-01', 'A-01'
    type ENUM('Heladería', 'Casa', 'Local', 'Bodega') NOT NULL,
    name VARCHAR(150) NULL,               -- e.g. 'Heladería CC Pradera'
    address VARCHAR(255) NULL,
    status ENUM('Activa', 'Ocupada', 'Disponible', 'Mantenimiento') NOT NULL,
    responsible_id INT NULL,              -- For locations assigned to specific staff
    tenant_name VARCHAR(150) NULL,        -- 'Inquilino' (e.g. 'Familia Pérez')
    monthly_rent DECIMAL(10, 2) NULL,     -- Rent tied to houses/commercial units
    next_payment_date DATE NULL,          -- Next billing date for tenants
    FOREIGN KEY (responsible_id) REFERENCES users(id) ON DELETE SET NULL
);

-- 3. FINANCIAL TRANSACTIONS TABLE
-- Stores financial inflows/outflows managed by the Admin.
-- Sourced from: Admin New Transaction screen, Admin Dashboard, Reports.
CREATE TABLE financial_transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    type ENUM('ingreso', 'egreso') NOT NULL,
    amount DECIMAL(12, 2) NOT NULL,
    transaction_date DATE NOT NULL,
    location_id INT NOT NULL,
    category VARCHAR(100) NOT NULL,       -- e.g. 'Venta Diaria', 'Insumos', 'Pago de Renta'
    provider VARCHAR(150) NULL,           -- Only for 'egreso' (Proveedor)
    description TEXT NOT NULL,
    receipt_path VARCHAR(255) NULL,       -- Path to the uploaded Comprobante
    status ENUM('Pendiente', 'Aprobado', 'Rechazado') DEFAULT 'Pendiente',
    created_by INT NOT NULL,              -- The user who recorded it
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (location_id) REFERENCES locations(id) ON DELETE RESTRICT,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE RESTRICT
);

-- 4. ASSETS TABLE
-- Stores the physical items/inventory being managed. 
-- Sourced from: Login Stats (5k Activos) and Tech Dashboard tracking.
CREATE TABLE assets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,           -- e.g. 'Lector de Código', 'Taladro Inalámbrico'
    current_location_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (current_location_id) REFERENCES locations(id) ON DELETE RESTRICT
);

-- 5. ASSET TRANSACTIONS TABLE
-- Stores inventory movements (checking in/checking out physical assets).
-- Sourced from: Tech Dashboard (Ingreso/Egreso de activos).
CREATE TABLE asset_transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    type ENUM('Ingreso', 'Egreso') NOT NULL,
    asset_id INT NOT NULL,
    location_id INT NOT NULL,             -- Destination or origin location
    status ENUM('Completado', 'Pendiente Revisión') DEFAULT 'Completado',
    created_by INT NOT NULL,              -- The tech who recorded the movement
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (asset_id) REFERENCES assets(id) ON DELETE RESTRICT,
    FOREIGN KEY (location_id) REFERENCES locations(id) ON DELETE RESTRICT,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE RESTRICT
);
