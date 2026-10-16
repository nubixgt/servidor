-- Asignaciones desactivables: una asignación con planificaciones no se elimina (se conserva el
-- historial); el docente la desactiva para que no aparezca al crear planes nuevos.
ALTER TABLE assignments ADD COLUMN active INTEGER NOT NULL DEFAULT 1 CHECK (active IN (0,1));
