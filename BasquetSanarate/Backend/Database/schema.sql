-- ============================================================================
--  Liga de Baloncesto Sanarateca — Esquema de base de datos
--  MySQL 5.7 / 8.x · utf8mb4 · InnoDB
--
--  Importar:
--    mysql -u <user> -p visionwe_BasquetSanarate < Backend/Database/schema.sql
--  Luego los datos iniciales:
--    mysql -u <user> -p visionwe_BasquetSanarate < Backend/Database/seed.sql
-- ============================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------------------------------------------------------
--  usuarios  (login del panel de administración — único rol: admin)
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS usuarios;
CREATE TABLE usuarios (
    id             INT UNSIGNED NOT NULL AUTO_INCREMENT,
    usuario        VARCHAR(50)  NOT NULL,
    password_hash  VARCHAR(255) NOT NULL,
    nombre         VARCHAR(120) NOT NULL,
    rol            ENUM('admin') NOT NULL DEFAULT 'admin',
    activo         TINYINT(1)   NOT NULL DEFAULT 1,
    creado_en      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado_en TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_usuarios_usuario (usuario)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
--  equipos  (franquicias / clubes)
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS equipos;
CREATE TABLE equipos (
    id                INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nombre            VARCHAR(120) NOT NULL,
    sede              VARCHAR(150) NOT NULL,
    conferencia       ENUM('Norte','Sur') NOT NULL,
    rama              ENUM('Masculina Mayor','Femenina Libre','Juvenil Sub-18') NOT NULL,
    logo_ruta         VARCHAR(255) NULL,
    director_tecnico  VARCHAR(120) NOT NULL,
    telefono_delegado VARCHAR(20)  NOT NULL,
    color_hex         VARCHAR(7)   NULL,
    activo            TINYINT(1)   NOT NULL DEFAULT 1,
    creado_en         TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado_en    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_equipos_conferencia (conferencia),
    KEY idx_equipos_rama (rama),
    KEY idx_equipos_activo (activo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
--  clasificacion  (1:1 con equipos — tabla de posiciones)
--  pj/pg/pp/pf/pc los recalcula POST /estadisticas/recalcular
--  racha/puntos_liga/sancion son manuales (el recálculo NO los toca)
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS clasificacion;
CREATE TABLE clasificacion (
    equipo_id      INT UNSIGNED NOT NULL,
    pj             INT NOT NULL DEFAULT 0,
    pg             INT NOT NULL DEFAULT 0,
    pp             INT NOT NULL DEFAULT 0,
    pf             INT NOT NULL DEFAULT 0,
    pc             INT NOT NULL DEFAULT 0,
    racha          VARCHAR(10)  NULL,
    puntos_liga    INT NOT NULL DEFAULT 0,
    sancion        VARCHAR(255) NULL,
    actualizado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (equipo_id),
    CONSTRAINT fk_clasificacion_equipo FOREIGN KEY (equipo_id)
        REFERENCES equipos (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
--  jugadores
--  Columnas de stats denormalizadas: se editan a mano en la consola de
--  estadísticas o las sobrescribe POST /estadisticas/recalcular.
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS jugadores;
CREATE TABLE jugadores (
    id               INT UNSIGNED NOT NULL AUTO_INCREMENT,
    equipo_id        INT UNSIGNED NULL,
    nombre_completo  VARCHAR(150) NOT NULL,
    dorsal           SMALLINT NULL,
    dpi              VARCHAR(20) NULL,
    fecha_nacimiento DATE NULL,
    nacionalidad     VARCHAR(60) NULL DEFAULT 'Guatemalteca',
    posicion         ENUM('Base','Escolta','Alero','Ala-Pívot','Pívot') NULL,
    estatura_cm      SMALLINT NULL,
    peso_kg          SMALLINT NULL,
    estado           ENUM('Habilitado','Suspendido') NOT NULL DEFAULT 'Habilitado',
    foto_ruta        VARCHAR(255) NULL,
    pj               INT NOT NULL DEFAULT 0,
    ppg              DECIMAL(5,1) NOT NULL DEFAULT 0,
    rpg              DECIMAL(5,1) NOT NULL DEFAULT 0,
    apg              DECIMAL(5,1) NOT NULL DEFAULT 0,
    tres_pct         DECIMAL(4,1) NOT NULL DEFAULT 0,
    tl_pct           DECIMAL(4,1) NOT NULL DEFAULT 0,
    eff              DECIMAL(5,1) NOT NULL DEFAULT 0,
    activo           TINYINT(1) NOT NULL DEFAULT 1,
    creado_en        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado_en   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_jugadores_dpi (dpi),
    KEY idx_jugadores_equipo (equipo_id),
    KEY idx_jugadores_posicion (posicion),
    KEY idx_jugadores_estado (estado),
    KEY idx_jugadores_activo (activo),
    CONSTRAINT fk_jugadores_equipo FOREIGN KEY (equipo_id)
        REFERENCES equipos (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
--  partidos  (fixture / marcadores)
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS partidos;
CREATE TABLE partidos (
    id                  INT UNSIGNED NOT NULL AUTO_INCREMENT,
    jornada             SMALLINT NULL,
    fase                VARCHAR(40) NOT NULL DEFAULT 'Regular',
    equipo_local_id     INT UNSIGNED NOT NULL,
    equipo_visitante_id INT UNSIGNED NOT NULL,
    fecha               DATE NULL,
    hora                TIME NULL,
    sede                VARCHAR(150) NULL,
    estado              ENUM('Programado','En Vivo','Finalizado','Pospuesto') NOT NULL DEFAULT 'Programado',
    marcador_local      INT NOT NULL DEFAULT 0,
    marcador_visitante  INT NOT NULL DEFAULT 0,
    arbitro_principal   VARCHAR(120) NULL,
    juez_mesa           VARCHAR(120) NULL,
    acta_cerrada        TINYINT(1) NOT NULL DEFAULT 0,
    creado_en           TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado_en      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_partidos_estado (estado),
    KEY idx_partidos_fecha (fecha),
    KEY idx_partidos_jornada (jornada),
    KEY idx_partidos_local (equipo_local_id),
    KEY idx_partidos_visitante (equipo_visitante_id),
    CONSTRAINT fk_partidos_local FOREIGN KEY (equipo_local_id)
        REFERENCES equipos (id) ON DELETE RESTRICT,
    CONSTRAINT fk_partidos_visitante FOREIGN KEY (equipo_visitante_id)
        REFERENCES equipos (id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
--  estadisticas  (box score: una fila por jugador por partido)
--  EFF = puntos + rebotes + asistencias
--        - (tl_intentos - tl_anotados) - (triples_intentos - triples)
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS estadisticas;
CREATE TABLE estadisticas (
    id               INT UNSIGNED NOT NULL AUTO_INCREMENT,
    partido_id       INT UNSIGNED NOT NULL,
    jugador_id       INT UNSIGNED NOT NULL,
    puntos           INT NOT NULL DEFAULT 0,
    rebotes          INT NOT NULL DEFAULT 0,
    asistencias      INT NOT NULL DEFAULT 0,
    triples          INT NOT NULL DEFAULT 0,
    triples_intentos INT NOT NULL DEFAULT 0,
    tl_anotados      INT NOT NULL DEFAULT 0,
    tl_intentos      INT NOT NULL DEFAULT 0,
    faltas           INT NOT NULL DEFAULT 0,
    minutos          INT NOT NULL DEFAULT 0,
    PRIMARY KEY (id),
    UNIQUE KEY uq_estadisticas_partido_jugador (partido_id, jugador_id),
    KEY idx_estadisticas_jugador (jugador_id),
    CONSTRAINT fk_estadisticas_partido FOREIGN KEY (partido_id)
        REFERENCES partidos (id) ON DELETE CASCADE,
    CONSTRAINT fk_estadisticas_jugador FOREIGN KEY (jugador_id)
        REFERENCES jugadores (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
--  novedades  (noticias / boletines)
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS novedades;
CREATE TABLE novedades (
    id             INT UNSIGNED NOT NULL AUTO_INCREMENT,
    titulo         VARCHAR(200) NOT NULL,
    categoria      VARCHAR(40)  NOT NULL DEFAULT 'Noticias',
    cuerpo         MEDIUMTEXT NULL,
    portada_ruta   VARCHAR(255) NULL,
    pdf_ruta       VARCHAR(255) NULL,
    fijado         TINYINT(1) NOT NULL DEFAULT 0,
    estado         ENUM('borrador','publicado') NOT NULL DEFAULT 'borrador',
    fecha_emision  DATE NULL,
    publicado_en   TIMESTAMP NULL DEFAULT NULL,
    autor_id       INT UNSIGNED NULL,
    creado_en      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_novedades_estado (estado),
    KEY idx_novedades_fijado (fijado),
    KEY idx_novedades_fecha (fecha_emision),
    CONSTRAINT fk_novedades_autor FOREIGN KEY (autor_id)
        REFERENCES usuarios (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
--  Vistas de verificación (opcionales — la API usa las columnas denormalizadas)
-- ----------------------------------------------------------------------------
DROP VIEW IF EXISTS vw_jugador_stats;
CREATE VIEW vw_jugador_stats AS
SELECT
    e.jugador_id,
    COUNT(*)                                                              AS pj,
    ROUND(AVG(e.puntos), 1)                                               AS ppg,
    ROUND(AVG(e.rebotes), 1)                                              AS rpg,
    ROUND(AVG(e.asistencias), 1)                                          AS apg,
    ROUND(100 * SUM(e.triples)     / NULLIF(SUM(e.triples_intentos), 0), 1) AS tres_pct,
    ROUND(100 * SUM(e.tl_anotados) / NULLIF(SUM(e.tl_intentos), 0), 1)      AS tl_pct,
    ROUND(AVG(
        e.puntos + e.rebotes + e.asistencias
        - (e.tl_intentos - e.tl_anotados)
        - (e.triples_intentos - e.triples)
    ), 1)                                                                 AS eff
FROM estadisticas e
JOIN partidos p ON p.id = e.partido_id AND p.estado = 'Finalizado'
GROUP BY e.jugador_id;

DROP VIEW IF EXISTS vw_clasificacion;
CREATE VIEW vw_clasificacion AS
SELECT
    t.equipo_id,
    COUNT(*)                                            AS pj,
    SUM(CASE WHEN t.pf > t.pc THEN 1 ELSE 0 END)        AS pg,
    SUM(CASE WHEN t.pf < t.pc THEN 1 ELSE 0 END)        AS pp,
    SUM(t.pf)                                           AS pf,
    SUM(t.pc)                                           AS pc
FROM (
    SELECT equipo_local_id     AS equipo_id, marcador_local     AS pf, marcador_visitante AS pc
    FROM partidos WHERE estado = 'Finalizado'
    UNION ALL
    SELECT equipo_visitante_id AS equipo_id, marcador_visitante AS pf, marcador_local     AS pc
    FROM partidos WHERE estado = 'Finalizado'
) t
GROUP BY t.equipo_id;

SET FOREIGN_KEY_CHECKS = 1;
