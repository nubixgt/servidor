-- =====================================================================
-- Script para eliminar TODA la información de las tablas de visionwe_ConcretosOriente
-- y reiniciar los contadores AUTO_INCREMENT (ID) de cada tabla en 1.
--
-- IMPORTANTE: Este script borra TODOS los registros de TODAS las tablas.
-- Solo debe ejecutarse en la base de datos indicada, y una vez ejecutado
-- la información eliminada no se puede recuperar (a menos que exista backup).
-- =====================================================================

SET FOREIGN_KEY_CHECKS = 0;

TRUNCATE TABLE `alerts_config`;
TRUNCATE TABLE `alerts_history`;
TRUNCATE TABLE `bank_accounts`;
TRUNCATE TABLE `budget_extensions`;
TRUNCATE TABLE `budget_items`;
TRUNCATE TABLE `clients`;
TRUNCATE TABLE `concrete_trips`;
TRUNCATE TABLE `contractors`;
TRUNCATE TABLE `credits`;
TRUNCATE TABLE `credit_payments`;
TRUNCATE TABLE `digital_documents`;
TRUNCATE TABLE `employee_incidents`;
TRUNCATE TABLE `employee_payroll_payments`;
TRUNCATE TABLE `estimations`;
TRUNCATE TABLE `estimation_items`;
TRUNCATE TABLE `expenses`;
TRUNCATE TABLE `expense_records`;
TRUNCATE TABLE `fuel_records`;
TRUNCATE TABLE `heavy_transport`;
TRUNCATE TABLE `incomes`;
TRUNCATE TABLE `income_records`;
TRUNCATE TABLE `inventory_items`;
TRUNCATE TABLE `inventory_kardex`;
TRUNCATE TABLE `machinery`;
TRUNCATE TABLE `machinery_log`;
TRUNCATE TABLE `maintenance_logs`;
TRUNCATE TABLE `maintenance_parts`;
TRUNCATE TABLE `mechanic_records`;
TRUNCATE TABLE `mechanic_record_items`;
TRUNCATE TABLE `payrolls`;
TRUNCATE TABLE `payroll_details`;
TRUNCATE TABLE `personnel`;
TRUNCATE TABLE `projects`;
TRUNCATE TABLE `project_contractors`;
TRUNCATE TABLE `project_incomes`;
TRUNCATE TABLE `project_income_sources`;
TRUNCATE TABLE `purchase_orders`;
TRUNCATE TABLE `purchase_order_items`;
TRUNCATE TABLE `recurrents`;
TRUNCATE TABLE `roles`;
TRUNCATE TABLE `special_machinery`;
TRUNCATE TABLE `suppliers`;
TRUNCATE TABLE `tipo_puestos`;
-- La tabla `users` se excluye a propósito: no se debe eliminar ningún usuario.
TRUNCATE TABLE `vehicles`;
TRUNCATE TABLE `vehicle_logs`;
TRUNCATE TABLE `viaticos`;

SET FOREIGN_KEY_CHECKS = 1;
