-- ==============================================================================
-- Migración para Módulo Transporte: Vehículos, Maquinaria, Transporte Pesado y Maquinaria Especial
-- ==============================================================================

-- 1. VEHÍCULOS
ALTER TABLE `vehicles`
  ADD COLUMN IF NOT EXISTS `seguro_contacto_nombre` VARCHAR(255) NULL AFTER `tipo_seguro`,
  ADD COLUMN IF NOT EXISTS `seguro_contacto_telefono` VARCHAR(50) NULL AFTER `seguro_contacto_nombre`,
  ADD COLUMN IF NOT EXISTS `seguro_aseguradora` VARCHAR(255) NULL AFTER `seguro_contacto_telefono`,
  ADD COLUMN IF NOT EXISTS `seguro_contrato_adjunto_path` VARCHAR(255) NULL AFTER `seguro_aseguradora`,
  ADD COLUMN IF NOT EXISTS `calcomania_adjunto_path` VARCHAR(255) NULL,
  ADD COLUMN IF NOT EXISTS `titulo_propiedad_adjunto_path` VARCHAR(255) NULL,
  ADD COLUMN IF NOT EXISTS `tarjeta_circulacion_adjunto_path` VARCHAR(255) NULL;

-- 2. MAQUINARIA
ALTER TABLE `machinery`
  ADD COLUMN IF NOT EXISTS `clasificacion_tipo` VARCHAR(50) DEFAULT 'Pesada' AFTER `categoria`,
  ADD COLUMN IF NOT EXISTS `fecha_servicio` DATE NULL AFTER `horometro_actual`,
  ADD COLUMN IF NOT EXISTS `seguro_contacto_nombre` VARCHAR(255) NULL,
  ADD COLUMN IF NOT EXISTS `seguro_contacto_telefono` VARCHAR(50) NULL,
  ADD COLUMN IF NOT EXISTS `seguro_aseguradora` VARCHAR(255) NULL,
  ADD COLUMN IF NOT EXISTS `seguro_contrato_adjunto_path` VARCHAR(255) NULL;

-- 3. TRANSPORTE PESADO
ALTER TABLE `heavy_transport`
  ADD COLUMN IF NOT EXISTS `kilometros` INT NULL AFTER `kilometraje`,
  ADD COLUMN IF NOT EXISTS `seguro_contacto_nombre` VARCHAR(255) NULL AFTER `tipo_seguro`,
  ADD COLUMN IF NOT EXISTS `seguro_contacto_telefono` VARCHAR(50) NULL AFTER `seguro_contacto_nombre`,
  ADD COLUMN IF NOT EXISTS `seguro_aseguradora` VARCHAR(255) NULL AFTER `seguro_contacto_telefono`,
  ADD COLUMN IF NOT EXISTS `seguro_contrato_adjunto_path` VARCHAR(255) NULL AFTER `seguro_aseguradora`;

-- 4. MAQUINARIA ESPECIAL
ALTER TABLE `special_machinery`
  ADD COLUMN IF NOT EXISTS `codigo` VARCHAR(100) NULL AFTER `id`,
  ADD COLUMN IF NOT EXISTS `valor` DECIMAL(12,2) NULL AFTER `precio`,
  ADD COLUMN IF NOT EXISTS `seguro_aseguradora` VARCHAR(255) NULL,
  ADD COLUMN IF NOT EXISTS `seguro_contacto_nombre` VARCHAR(255) NULL,
  ADD COLUMN IF NOT EXISTS `seguro_contacto_telefono` VARCHAR(50) NULL,
  ADD COLUMN IF NOT EXISTS `seguro_poliza` VARCHAR(100) NULL,
  ADD COLUMN IF NOT EXISTS `seguro_contrato_adjunto_path` VARCHAR(255) NULL;
