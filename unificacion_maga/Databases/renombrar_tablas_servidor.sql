-- Script SQL para renombrar tablas y agregar prefijos

-- ==============================================================
-- MODULO: Tablero VIDER
-- ==============================================================
ALTER TABLE `actividades` RENAME TO `vider_actividades`;
ALTER TABLE `datos_vider` RENAME TO `vider_datos`;
ALTER TABLE `departamentos` RENAME TO `vider_departamentos`;
ALTER TABLE `dependencias` RENAME TO `vider_dependencias`;
ALTER TABLE `importaciones` RENAME TO `vider_importaciones`;
ALTER TABLE `intervenciones` RENAME TO `vider_intervenciones`;
ALTER TABLE `medidas` RENAME TO `vider_medidas`;
ALTER TABLE `municipios` RENAME TO `vider_municipios`;
ALTER TABLE `productos` RENAME TO `vider_productos`;
ALTER TABLE `programas` RENAME TO `vider_programas`;

-- ==============================================================
-- MODULO: Productores
-- ==============================================================
ALTER TABLE `productores` RENAME TO `agro_productores`;

-- ==============================================================
-- MODULO: Extension Rural
-- ==============================================================
ALTER TABLE `extensionistas` RENAME TO `extension_extensionistas`;
