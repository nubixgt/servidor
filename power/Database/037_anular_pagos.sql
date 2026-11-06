-- 037: anular un pago registrado por error (decisión del responsable, 2026-10-21).
--
-- Run ONCE, after 036. Aditiva y repetible: no borra nada.
--
-- ## Por qué
--
-- «Un pago registrado por error no se puede corregir desde la pantalla, sólo en la base.» Un pago es
-- contabilidad y nunca se borra (036): se ANULA, con quién, cuándo y por qué, y deja de contar para el
-- acceso. Sólo se puede anular el último pago vigente de un atleta: los siguientes empiezan donde
-- terminaba él, y anular uno de en medio dejaría un hueco en las fechas de los demás.
--
-- Tolerancia: sin estas columnas `AccessService` cuenta todos los pagos, como antes, y «Anular» no
-- aparece en «Usuarios».

SET @existe := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'athlete_payments'
      AND COLUMN_NAME  = 'voided_at'
);
SET @sql := IF(@existe = 0,
    'ALTER TABLE athlete_payments
        ADD COLUMN voided_at   DATETIME NULL DEFAULT NULL COMMENT "Anulado: deja de contar para el acceso. NULL = vigente.",
        ADD COLUMN voided_by   INT UNSIGNED NULL DEFAULT NULL COMMENT "El administrador que lo anuló.",
        ADD COLUMN void_reason VARCHAR(191) NULL DEFAULT NULL COMMENT "Por qué se anuló."',
    'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
