-- EleccionCYD - Esquema inicial: tabla de usuarios (login del jurado)
-- Ejecutar directamente en la base de datos configurada en Backend/config/database.php

CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    rol VARCHAR(30) NOT NULL DEFAULT 'admin',
    activo TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Usuario administrador inicial.
-- usuario: admin
-- contraseña: admin123  (hash bcrypt, verificable con password_verify() en PHP)
INSERT INTO usuarios (usuario, password, nombre, rol) VALUES
('admin', '$2b$10$R6zmnnEIzXDrrxLRBbCYNOm22urniQw4Hf1ucpBa/tp4SttmMWhM.', 'Jurado Oficial', 'admin');

-- Para dar de alta a cada jurado real, agrega una fila más aquí (cambia usuario/nombre y genera
-- un hash bcrypt nuevo para su contraseña -- NO reutilices el hash de arriba, es único para "admin123").
-- INSERT INTO usuarios (usuario, password, nombre, rol) VALUES ('jurado2', '<hash_bcrypt>', 'Nombre del Jurado', 'admin');


-- ============================================================
-- Participantes
-- ============================================================
-- "codigo" es el mismo identificador que ya usa el Frontend (SR01..SR09, JV01..JV09) para
-- emparejar cada fila con su foto, que sigue viviendo en Frontend/src/assets/images/.
CREATE TABLE IF NOT EXISTS participantes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    codigo VARCHAR(10) NOT NULL UNIQUE,
    nombre VARCHAR(150) NOT NULL,
    categoria ENUM('SENORITA', 'JOVEN') NOT NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO participantes (codigo, nombre, categoria) VALUES
('SR01', 'Luna Estefany Consuelo Arevalo Ramos', 'SENORITA'),
('SR02', 'Diana Luisa Alejandra Castro Velásquez', 'SENORITA'),
('SR03', 'Madelyn Samara Hernandez Hernandez', 'SENORITA'),
('SR04', 'Victoria Margarita Sharshente Gonzalez', 'SENORITA'),
('SR05', 'Laisha Sofia Xitumul Ixcopal', 'SENORITA'),
('SR06', 'Delmi Leonela Catalan Gonzalez', 'SENORITA'),
('SR07', 'Dayra Yamileth Gomez Ixpata', 'SENORITA'),
('SR08', 'Elba Dayanary Lopez Perez', 'SENORITA'),
('SR09', 'Yaneli Alexandra Molineros Franco', 'SENORITA'),
('JV01', 'Erik Josue Marroquin Villavicencio', 'JOVEN'),
('JV02', 'Anthony Jose Salvatierra Rodriguez', 'JOVEN'),
('JV03', 'Edgar Julio Manuel Juarez Garcia', 'JOVEN'),
('JV04', 'Nery Bagner Josué Tello Oliva', 'JOVEN'),
('JV05', 'Jefferson Gerrad Canahui Jeronimo', 'JOVEN'),
('JV06', 'Juan Pablo Hernandez Rodriguez', 'JOVEN'),
('JV07', 'Jeremy Alain Ebany Sarpec Ixtecoc', 'JOVEN'),
('JV08', 'Ardany Edwin Mendoza', 'JOVEN'),
('JV09', 'Cristhian Samuel Cuellar Flores', 'JOVEN');


-- ============================================================
-- Calificaciones (una tabla por ronda, igual que las hojas del Excel original)
-- ============================================================
-- Cada jurado tiene su propia fila por participante (UNIQUE participante_id + jurado_id).
-- "total" es el promedio de los 3 rubros DE ESE JURADO. Para el ranking final, el total que se
-- muestra por ronda es la SUMA de "total" entre todos los jurados (no el promedio) -- así lo pidió
-- el cliente. El Total Final del ranking es la suma de las 3 rondas.

CREATE TABLE IF NOT EXISTS calificaciones_fashion_show (
    id INT AUTO_INCREMENT PRIMARY KEY,
    participante_id INT NOT NULL,
    jurado_id INT NOT NULL,
    originalidad TINYINT UNSIGNED NOT NULL,
    presentacion TINYINT UNSIGNED NOT NULL,
    coordinacion TINYINT UNSIGNED NOT NULL,
    total DECIMAL(4,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_fashion_show (participante_id, jurado_id),
    CONSTRAINT fk_fs_participante FOREIGN KEY (participante_id) REFERENCES participantes(id) ON DELETE CASCADE,
    CONSTRAINT fk_fs_jurado FOREIGN KEY (jurado_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS calificaciones_coreografia (
    id INT AUTO_INCREMENT PRIMARY KEY,
    participante_id INT NOT NULL,
    jurado_id INT NOT NULL,
    coordinacion TINYINT UNSIGNED NOT NULL,
    ritmo TINYINT UNSIGNED NOT NULL,
    desplazamiento TINYINT UNSIGNED NOT NULL,
    total DECIMAL(4,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_coreografia (participante_id, jurado_id),
    CONSTRAINT fk_co_participante FOREIGN KEY (participante_id) REFERENCES participantes(id) ON DELETE CASCADE,
    CONSTRAINT fk_co_jurado FOREIGN KEY (jurado_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- "pregunta_o_elegancia": Pregunta para Señoritas, Elegancia para Jóvenes (mismo campo, la etiqueta
-- que se muestra depende de la categoría del participante, igual que ya hace el Frontend).
CREATE TABLE IF NOT EXISTS calificaciones_gala (
    id INT AUTO_INCREMENT PRIMARY KEY,
    participante_id INT NOT NULL,
    jurado_id INT NOT NULL,
    modelaje TINYINT UNSIGNED NOT NULL,
    seguridad TINYINT UNSIGNED NOT NULL,
    pregunta_o_elegancia TINYINT UNSIGNED NOT NULL,
    total DECIMAL(4,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_gala (participante_id, jurado_id),
    CONSTRAINT fk_ga_participante FOREIGN KEY (participante_id) REFERENCES participantes(id) ON DELETE CASCADE,
    CONSTRAINT fk_ga_jurado FOREIGN KEY (jurado_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
