-- REINICIO DE LA BETA 2.0 — borra todos los datos de atletas.
--
-- NO es una migración numerada y NO se corre nunca por accidente: se ejecuta a
-- mano UNA vez, en producción, el día del relanzamiento (2026-09-15), después de:
--
--   1. la copia de seguridad completa (mysqldump), y
--   2. `php Backend/scripts/retirar_garmin_antes_del_reinicio.php --ejecutar`,
--      que saca de los relojes los entrenos que subió la app. Después de este
--      script y del borrado de `_runtime/` ya no hay registro que diga cuáles
--      eran nuestros.
--
-- Decisión del responsable del producto: se borra TODO, cuentas incluidas. Las
-- filas de ADMINISTRADOR se conservan (sin ellas nadie podría aprobar a los
-- atletas que vuelvan a pedir acceso), pero se les borran también sus datos de
-- atleta y su aceptación de condiciones, para que empiecen de cero como todos.
--
-- Lo que este script NO borra, y hay que borrar en el servidor:
--   rm -rf /home/visionwe/aiservice/app/_runtime/storage/* \
--          /home/visionwe/aiservice/app/_runtime/jobs/*    \
--          /home/visionwe/aiservice/app/_runtime/upload_jobs/*
--   (trazas, expedientes, registro de subidas, trabajos con specs viejos y el
--    libro de costos del AiService). Ver contexto/planes/PLAN_LANZAMIENTO_BETA_2_0.md.

START TRANSACTION;

-- Datos de atleta, de todos (administradores incluidos).
DELETE FROM notification_dismissals;
DELETE FROM notifications;
DELETE FROM athlete_feedback;
DELETE FROM app_events;
DELETE FROM athlete_usage;
DELETE FROM athlete_plan_history;
DELETE FROM athlete_plans;
DELETE FROM athlete_checkins;
DELETE FROM athlete_notebook;
DELETE FROM athlete_competitions;
DELETE FROM athlete_injuries;
DELETE FROM athlete_profiles;

-- Cuentas: fuera todas menos las de administrador.
DELETE FROM users WHERE role <> 'admin';

-- Los administradores también vuelven a aceptar las condiciones nuevas.
UPDATE users SET terms_accepted_at = NULL, terms_version = NULL WHERE role = 'admin';

-- Comprobación antes de confirmar: debe dar 0 en todo menos `admins`.
SELECT
    (SELECT COUNT(*) FROM users WHERE role = 'admin')  AS admins,
    (SELECT COUNT(*) FROM users WHERE role <> 'admin') AS atletas,
    (SELECT COUNT(*) FROM athlete_profiles)            AS perfiles,
    (SELECT COUNT(*) FROM athlete_plans)               AS planes,
    (SELECT COUNT(*) FROM athlete_usage)               AS usos,
    (SELECT COUNT(*) FROM app_events)                  AS eventos;

-- Si los números cuadran:  COMMIT;
-- Si algo no cuadra:        ROLLBACK;
