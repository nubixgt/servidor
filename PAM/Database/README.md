# Database

SQL manual (DDL + datos iniciales) del sistema PAM. Sin seeders ni lógica en código de la aplicación.
Probado en MySQL 8.0 y MariaDB 10.11. Requiere **MySQL 8.0+ / MariaDB 10.6+** (funciones de ventana y `REGEXP_REPLACE`).

| Archivo | Contenido |
|---|---|
| `schema.sql` | Crea la base `pam`: tablas, vistas, funciones, procedimientos y triggers de auditoría |
| `seed.sql` | Catálogos (22 departamentos, 340 municipios, estados, 46 tipos de resolución), permisos, roles y parámetros |

## Instalación

```bash
mysql -u root -p < Database/schema.sql
mysql -u root -p pam < Database/seed.sql
php Backend/bin/crear_admin.php          # primer usuario administrador
mysql -u root -p pam -e "CALL mart_refrescar();"
```

Con phpMyAdmin: importar `schema.sql` y luego `seed.sql` (seleccionando la base `pam`). El archivo usa `DELIMITER $$`, que phpMyAdmin soporta.

Los dos scripts se pueden ejecutar más de una vez sin duplicar datos (el `schema.sql` no borra tablas existentes).

## Equivalencias con el diseño original (PostgreSQL)

| PostgreSQL | MySQL |
|---|---|
| Esquemas `ref`, `seg`, `core`, `audit`, `stg`, `mart` | Prefijos `ref_`, `seg_`, `core_`, `audit_`, `stg_`, `mart_` |
| Vistas materializadas `mart.mv_*` | Tablas `mart_cubo`, `mart_serie`, `mart_tiempos` reconstruidas por `CALL mart_refrescar()` y cambiadas de forma atómica (`RENAME TABLE`) |
| Índice único parcial de estado vigente | Columna generada `vigente_unico` + índice único |
| Búsqueda trigram (`pg_trgm`) | `LIKE '%texto%'` sobre `core_persona.nombre_norm` (texto ya normalizado) |
| `set_config('pam.usuario_id', …)` | Variables de sesión `@pam_usuario_id`, `@pam_usuario`, `@pam_ip`, `@pam_request_id` |
| `percentile_cont` | Interpolación lineal con funciones de ventana |
| `stg.procesar_lote` devuelve JSON | `CALL stg_procesar_lote(lote, solo_validar)`; el reporte de conciliación lo arma el backend |

## Notas

- **Refrescar el tablero:** `CALL mart_refrescar();` tras cada importación o cuando se quiera actualizar los agregados. Se puede programar con un `EVENT` de MySQL o con cron (`php Backend/bin/refrescar.php`).
- **Bitácora inmutable:** los triggers impiden `UPDATE` y `DELETE`. `TRUNCATE` no dispara triggers, así que el usuario de la aplicación no debe tener el privilegio `DROP`/`TRUNCATE` sobre `audit_bitacora`. Ejemplo:
  ```sql
  CREATE USER 'pam_app'@'localhost' IDENTIFIED BY 'cambiar';
  GRANT SELECT, INSERT, UPDATE, DELETE, EXECUTE, CREATE TEMPORARY TABLES ON pam.* TO 'pam_app'@'localhost';
  ```
  (`pam_app` no recibe `DROP` ni `ALTER`, pero `mart_refrescar()` crea y renombra tablas: ejecútalo con un usuario con esos privilegios, p. ej. el que corre el cron.)
- **Binlog activo:** para crear los triggers sin ser `SUPER`, MySQL exige `log_bin_trust_function_creators=1`. Con `root` no hace falta.
- El borrado en cascada por clave foránea no dispara los triggers de auditoría (comportamiento de MySQL).
- Las fechas se guardan en hora de Guatemala (UTC-6); el backend fija `time_zone = '-06:00'` en cada conexión.
