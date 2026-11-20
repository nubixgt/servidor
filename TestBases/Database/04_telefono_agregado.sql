-- =====================================================================
--  TestBases · 04 · TELÉFONO AGREGADO DESDE LA APP
--  Ejecutar ANTES de desplegar la versión que permite agregar teléfonos.
--
--  Los técnicos y administradores pueden escribir el teléfono de un
--  beneficiario que no tenía (telefono IS NULL). Un teléfono que ya venía
--  del Excel no se puede sobrescribir desde la app.
--  Para distinguir lo original de lo agregado se guarda quién y cuándo.
--  Ambas columnas quedan NULL en los teléfonos que vinieron del Excel.
-- =====================================================================

USE testbases;

ALTER TABLE beneficiarios
    ADD COLUMN telefono_agregado_por INT UNSIGNED NULL AFTER telefono,
    ADD COLUMN telefono_agregado_en  DATETIME     NULL AFTER telefono_agregado_por,
    ADD KEY ix_beneficiarios_telefono_agregado_por (telefono_agregado_por),
    ADD CONSTRAINT fk_beneficiarios_telefono_agregado_por
        FOREIGN KEY (telefono_agregado_por) REFERENCES usuarios (id);
