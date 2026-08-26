-- Migración para estandarizar las tablas del módulo VISAN

-- Renombrar la tabla de asistencia
RENAME TABLE datos_asistencia TO visan_datos_asistencia;

-- Renombrar la tabla de historial de cambios
RENAME TABLE historial_cambios TO visan_historial_cambios;
