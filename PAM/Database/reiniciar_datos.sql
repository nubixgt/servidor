-- Vacía los DATOS de expedientes para volver a cargar la matriz oficial desde cero.
-- NO toca: catálogos (ref_*), usuarios/roles/sesiones (seg_*), bitácora (audit_bitacora) ni el historial de lotes.
-- Úsalo cuando la nueva matriz reemplaza a la anterior: una importación incremental dejaría duplicados los
-- expedientes cuyo número, DPI, fecha de solicitud, nombre o municipio fueron corregidos en la fuente.
-- TRUNCATE no dispara triggers, así que no llena la bitácora. Ejecutar en la base seleccionada (phpMyAdmin > SQL).
-- Después: php bin/importar.php <archivo.csv>  y  php bin/refrescar.php --forzar

SET FOREIGN_KEY_CHECKS = 0;
TRUNCATE TABLE core_documento;
TRUNCATE TABLE core_caso_revision;
TRUNCATE TABLE core_estado_expediente;
TRUNCATE TABLE core_movimiento_nomina;
TRUNCATE TABLE core_resolucion;
TRUNCATE TABLE core_observacion;
TRUNCATE TABLE core_visita;
TRUNCATE TABLE core_resumen_scope;
TRUNCATE TABLE core_expediente;
TRUNCATE TABLE core_apoderado;
TRUNCATE TABLE core_persona;
TRUNCATE TABLE stg_import_error;
TRUNCATE TABLE stg_norm;
TRUNCATE TABLE stg_matriz;
TRUNCATE TABLE mart_cubo;
TRUNCATE TABLE mart_serie;
TRUNCATE TABLE mart_tiempos;
TRUNCATE TABLE mart_meta;
SET FOREIGN_KEY_CHECKS = 1;
