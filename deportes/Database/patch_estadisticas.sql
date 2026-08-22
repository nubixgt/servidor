-- Paso 1: Actualizar datos existentes para coincidir con el nuevo ENUM
UPDATE jugadores SET posicion = 'Portero' WHERE posicion = 'POR';
UPDATE jugadores SET posicion = 'Defensa' WHERE posicion = 'DEF';
UPDATE jugadores SET posicion = 'Mediocampo' WHERE posicion = 'MED';
UPDATE jugadores SET posicion = 'Delantero' WHERE posicion = 'DEL';

-- Paso 2: Cambiar la columna a ENUM
ALTER TABLE jugadores MODIFY COLUMN posicion ENUM('Portero', 'Defensa', 'Mediocampo', 'Delantero') NULL;

-- Paso 3: Crear tabla partidos
CREATE TABLE IF NOT EXISTS partidos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fecha DATETIME NOT NULL,
    equipo_local_id INT NOT NULL,
    equipo_visitante_id INT NOT NULL,
    goles_local INT DEFAULT 0,
    goles_visitante INT DEFAULT 0,
    registrado_por INT NOT NULL,
    fecha_registro DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (equipo_local_id) REFERENCES equipos(id) ON DELETE CASCADE,
    FOREIGN KEY (equipo_visitante_id) REFERENCES equipos(id) ON DELETE CASCADE,
    FOREIGN KEY (registrado_por) REFERENCES equipos(id) ON DELETE CASCADE
);

-- Paso 4: Crear tabla estadisticas_partido
CREATE TABLE IF NOT EXISTS estadisticas_partido (
    id INT AUTO_INCREMENT PRIMARY KEY,
    partido_id INT NOT NULL,
    jugador_id INT NOT NULL,
    goles INT DEFAULT 0,
    tarjetas_amarillas INT DEFAULT 0,
    tarjetas_rojas INT DEFAULT 0,
    goles_recibidos INT DEFAULT 0,
    jugo_como_portero BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (partido_id) REFERENCES partidos(id) ON DELETE CASCADE,
    FOREIGN KEY (jugador_id) REFERENCES jugadores(id) ON DELETE CASCADE
);
