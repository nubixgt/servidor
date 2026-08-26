-- Migración para estandarizar las tablas globales del sistema

-- Renombrar tabla de usuarios globales
RENAME TABLE usuarios TO maga_usuarios;

-- Renombrar tabla de notificaciones globales
RENAME TABLE notifications TO maga_notificaciones;
