-- 004: categoría del equipo (A o B). Los equipos existentes quedan en A; el admin puede cambiarlos.
ALTER TABLE equipos
    ADD COLUMN categoria ENUM('A','B') NOT NULL DEFAULT 'A' AFTER abreviatura;
