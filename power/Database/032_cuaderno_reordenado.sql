-- 032: «Reordenar cuaderno» (Beta 3.0, F9).
--
-- Run ONCE, after 031. Aditiva y repetible.
--
-- ## Por qué
--
-- Reordenar propone fusionar, reescribir, pasar al perfil o retirar anotaciones, y el
-- atleta acepta cada cambio. Nada se borra: la original se RETIRA con su motivo y, si la
-- sustituye otra, `replaced_by` dice cuál. La nueva lleva `source = 'reorden'` para que se
-- sepa de dónde salió.
--
-- Tolerancia: `NotebookRepository::admiteReorden()`; sin la migración, la anotación nueva se
-- guarda como 'manual' y la original se retira igual (sin `replaced_by`).

SET @hay := (SELECT COUNT(*) FROM information_schema.COLUMNS
              WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'athlete_notebook'
                AND COLUMN_NAME = 'source' AND COLUMN_TYPE LIKE '%reorden%');
SET @sql := IF(@hay = 0,
    'ALTER TABLE athlete_notebook MODIFY COLUMN source ENUM(''manual'',''chat'',''reorden'') NOT NULL DEFAULT ''manual''',
    'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @hay := (SELECT COUNT(*) FROM information_schema.COLUMNS
              WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'athlete_notebook' AND COLUMN_NAME = 'replaced_by');
SET @sql := IF(@hay = 0,
    'ALTER TABLE athlete_notebook ADD COLUMN replaced_by INT UNSIGNED NULL COMMENT ''La anotación que la sustituyó al reordenar.'' AFTER retired_reason',
    'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
