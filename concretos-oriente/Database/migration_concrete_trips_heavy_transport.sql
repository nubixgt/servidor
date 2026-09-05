-- ============================================================================
-- Migración: Control Concreto usa el listado de Transporte Pesado
-- ----------------------------------------------------------------------------
-- Antes: concrete_trips.vehiculo_id referenciaba la tabla `vehicles`.
-- Ahora: los despachos de concreto se hacen con las unidades de `heavy_transport`
--        (menú "Transporte Pesado"), por lo que el FK debe apuntar a esa tabla.
--
-- Nota: heavy_transport.id es INT UNSIGNED, así que la columna vehiculo_id
--       también debe ser UNSIGNED para poder crear el FK.
--
-- IMPORTANTE: si la tabla concrete_trips ya tiene registros que apuntan a
--             `vehicles`, deben limpiarse o remapearse antes de correr esto.
-- ============================================================================

ALTER TABLE `concrete_trips` DROP FOREIGN KEY `concrete_trips_ibfk_2`;

ALTER TABLE `concrete_trips` MODIFY `vehiculo_id` INT(11) UNSIGNED NOT NULL;

ALTER TABLE `concrete_trips`
  ADD CONSTRAINT `concrete_trips_ibfk_2`
  FOREIGN KEY (`vehiculo_id`) REFERENCES `heavy_transport` (`id`) ON DELETE CASCADE;
