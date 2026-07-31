CREATE DATABASE IF NOT EXISTS deportes CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE deportes;

CREATE TABLE IF NOT EXISTS equipos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    representante VARCHAR(100) NOT NULL,
    telefono VARCHAR(20) NOT NULL,
    foto_ruta VARCHAR(255) NOT NULL,
    foto_representante_ruta VARCHAR(255) DEFAULT NULL,
    dpi VARCHAR(20) NOT NULL UNIQUE,
    usuario VARCHAR(50) DEFAULT NULL,
    password_hash VARCHAR(255) DEFAULT NULL,
    rol ENUM('encargado', 'admin') DEFAULT 'encargado',
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS jugadores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    equipo_id INT NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    dpi VARCHAR(20) NOT NULL UNIQUE,
    foto_ruta VARCHAR(255) NOT NULL,
    telefono VARCHAR(20) NOT NULL,
    posicion VARCHAR(10) DEFAULT NULL,
    estado ENUM('activo', 'inactivo') DEFAULT 'activo',
    razon_baja TEXT NULL,
    fecha_baja DATETIME NULL,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (equipo_id) REFERENCES equipos(id) ON DELETE CASCADE
);
