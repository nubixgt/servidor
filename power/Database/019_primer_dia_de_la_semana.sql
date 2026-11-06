-- v1.2 — el día en que empieza la semana de entrenamiento del atleta.
--
-- Hay quien planifica de lunes a domingo y quien de sábado a viernes (la
-- tirada larga del sábado abre su semana). Hasta aquí la semana empezaba el
-- día en que se generaba la PRIMERA, y desde ahí cada una arrancaba el día
-- siguiente a la anterior: quien generó un miércoles tenía semanas de miércoles
-- a martes para siempre.
--
-- Con este campo la primera generación escribe sólo hasta la víspera de ese
-- día (hoy miércoles y semana de lunes: de miércoles a domingo), y las
-- siguientes ya caen enteras en su sitio. Ver web_app/server.py::_fin_de_bloque.
--
-- NULL = no lo ha elegido: bloques de siete días como hasta ahora.
--
-- El código aguanta que esta migración no se haya corrido todavía:
-- ProfileRepository::upsert() sólo escribe columnas que existen.

-- Repetible: correrla dos veces no falla (MariaDB 10.0+).
-- Correr con un cliente en utf8mb4: el ENUM lleva "Miércoles" y "Sábado".
ALTER TABLE athlete_profiles
    ADD COLUMN IF NOT EXISTS week_start_day
        ENUM('Lunes','Martes','Miércoles','Jueves','Viernes','Sábado','Domingo')
        NULL AFTER preferred_rest_day;
