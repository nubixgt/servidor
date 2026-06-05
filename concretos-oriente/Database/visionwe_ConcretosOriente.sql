-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Servidor: localhost:3306
-- Tiempo de generación: 05-06-2026 a las 00:22:37
-- Versión del servidor: 11.4.12-MariaDB
-- Versión de PHP: 8.4.21

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `visionwe_ConcretosOriente`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `alerts_config`
--

CREATE TABLE `alerts_config` (
  `id` int(11) NOT NULL,
  `nombre` varchar(255) NOT NULL,
  `tipo_evento` varchar(255) NOT NULL,
  `canales` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`canales`)),
  `destinatarios` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`destinatarios`)),
  `umbral` int(11) NOT NULL DEFAULT 0,
  `activa` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Volcado de datos para la tabla `alerts_config`
--

INSERT INTO `alerts_config` (`id`, `nombre`, `tipo_evento`, `canales`, `destinatarios`, `umbral`, `activa`, `created_at`, `updated_at`) VALUES
(1, 'prueba 1', 'Mantenimiento próximo', '[\"WhatsApp\"]', '[\"Admin\",\"Supervisor\",\"T\\u00e9cnico\"]', 100, 1, '2026-05-21 20:19:43', '2026-05-21 20:19:43'),
(2, 'Prueba 2', 'Sobrecosto de proyecto', '[\"WhatsApp\"]', '[\"Supervisor\"]', 100, 1, '2026-05-22 23:41:54', '2026-05-22 23:41:54');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `alerts_history`
--

CREATE TABLE `alerts_history` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `category` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `is_urgent` tinyint(1) DEFAULT 0,
  `time_ago` varchar(50) DEFAULT NULL,
  `project_or_meta` varchar(255) DEFAULT NULL,
  `value_or_priority` varchar(255) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Volcado de datos para la tabla `alerts_history`
--

INSERT INTO `alerts_history` (`id`, `title`, `category`, `description`, `is_urgent`, `time_ago`, `project_or_meta`, `value_or_priority`, `is_read`, `created_at`) VALUES
(1, 'Alerta Crítica: Stock Bajo', 'proyectos', 'El inventario de Cemento Portland ha bajado de los 15 sacos estipulados en tu configuración de alerta. Se requiere orden de compra.', 1, 'Hace 5 min', 'Bodega Central', 'Requiere Acción', 0, '2026-05-21 20:29:22'),
(2, 'Aviso: Vencimiento de Crédito', 'finanzas', 'La cuenta por pagar a Cementos Progreso S.A. (Factura #F-9023) vence en 2 días. Saldo pendiente sujeto a mora.', 0, 'Hace 2 horas', 'Pago a Proveedores', 'Q15,400.00', 0, '2026-05-21 20:29:22'),
(3, 'Recordatorio de Mantenimiento', 'maquinaria', 'La Retroexcavadora CAT 416F2 (ID: #RET-09) ha alcanzado el límite de horas de uso y requiere mantenimiento preventivo.', 0, 'Hace 1 día', 'Maquinaria Pesada', 'Preventivo', 0, '2026-05-21 20:29:22'),
(4, 'Alerta: Sobrecosto Detectado', 'finanzas', 'El proyecto \"Ampliación Carretera Norte\" ha excedido su presupuesto mensual estimado en el rubro de Materiales en un 12%.', 1, 'Hace 3 días', 'Ampliación Norte', 'Excede Umbral', 1, '2026-05-21 20:29:22');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `bank_accounts`
--

CREATE TABLE `bank_accounts` (
  `id` int(11) NOT NULL,
  `nombre_banco` varchar(255) NOT NULL,
  `numero_cuenta` varchar(100) NOT NULL,
  `tipo_cuenta` varchar(100) NOT NULL,
  `moneda` varchar(10) DEFAULT 'GTQ',
  `activa` tinyint(1) DEFAULT 1,
  `saldo_inicial` decimal(15,2) DEFAULT 0.00,
  `saldo_actual` decimal(15,2) DEFAULT 0.00,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `bank_accounts`
--

INSERT INTO `bank_accounts` (`id`, `nombre_banco`, `numero_cuenta`, `tipo_cuenta`, `moneda`, `activa`, `saldo_inicial`, `saldo_actual`, `created_at`, `updated_at`) VALUES
(1, 'banco industrial', '984651023', 'monetaria', 'GTQ', 1, 0.00, 1200.00, '2026-05-21 16:33:37', '2026-06-05 00:07:06'),
(2, 'gyt continental', '87456120', 'ahorro', 'GTQ', 1, 0.00, 0.00, '2026-05-21 16:43:07', '2026-05-21 16:43:07'),
(3, 'Banco gyt', '65410352130', 'ahorro', 'GTQ', 1, 0.00, 0.00, '2026-05-22 23:11:52', '2026-05-22 23:11:52'),
(4, 'Promerica', '7984651320', 'Monetaria', 'GTQ', 1, 500.00, 800.00, '2026-06-04 23:18:06', '2026-06-04 23:19:54');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `budget_items`
--

CREATE TABLE `budget_items` (
  `id` int(11) NOT NULL,
  `project_id` int(11) NOT NULL,
  `nombre_partida` varchar(255) NOT NULL,
  `categoria` enum('Material','Mano de Obra','Maquinaria','Subcontrato','Indirectos') NOT NULL,
  `unidad_medida` varchar(50) NOT NULL,
  `cantidad_estimada` decimal(15,2) NOT NULL DEFAULT 0.00,
  `precio_unitario` decimal(15,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `budget_items`
--

INSERT INTO `budget_items` (`id`, `project_id`, `nombre_partida`, `categoria`, `unidad_medida`, `cantidad_estimada`, `precio_unitario`, `created_at`) VALUES
(1, 1, 'Prueba 1', 'Mano de Obra', 'unidad', 100.00, 20.00, '2026-05-21 17:22:32'),
(2, 1, 'asdf', 'Material', 'm3', 50.00, 30.00, '2026-05-21 17:26:25'),
(3, 2, 'Prueba 2', 'Maquinaria', 'unidad', 100.00, 20.00, '2026-05-22 23:23:09');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `clients`
--

CREATE TABLE `clients` (
  `id` int(11) NOT NULL,
  `company_name` varchar(255) NOT NULL,
  `ruc` varchar(50) DEFAULT NULL,
  `status` enum('active','prospect','inactive') NOT NULL DEFAULT 'active',
  `contact_name` varchar(255) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `clients`
--

INSERT INTO `clients` (`id`, `company_name`, `ruc`, `status`, `contact_name`, `email`, `phone`, `address`, `created_at`, `updated_at`) VALUES
(1, 'empresa1', '789461532', 'active', 'Ing. Mario', 'mario@gmail.com', '45289012', 'Parque las Americas', '2026-05-26 14:43:13', '2026-05-26 14:43:13');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `credits`
--

CREATE TABLE `credits` (
  `id` int(11) NOT NULL,
  `supplier_id` int(11) NOT NULL,
  `project_id` int(11) DEFAULT NULL,
  `invoice_number` varchar(100) DEFAULT NULL,
  `purchase_date` date NOT NULL,
  `amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `due_date` date NOT NULL,
  `observations` text DEFAULT NULL,
  `status` enum('Pendiente','Parcial','Pagado') DEFAULT 'Pendiente',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `credits`
--

INSERT INTO `credits` (`id`, `supplier_id`, `project_id`, `invoice_number`, `purchase_date`, `amount`, `due_date`, `observations`, `status`, `created_at`, `updated_at`) VALUES
(2, 2, 1, '9865120', '2026-05-21', 500.00, '2026-05-24', 'Prueba 2', 'Parcial', '2026-05-21 17:09:41', '2026-05-21 17:10:08'),
(3, 3, 2, '89456120', '2026-05-22', 100.00, '2026-06-14', 'Prueba 2 para saber si todo carga correctamente', 'Pagado', '2026-05-22 23:32:50', '2026-05-22 23:33:11');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `credit_payments`
--

CREATE TABLE `credit_payments` (
  `id` int(11) NOT NULL,
  `credit_id` int(11) NOT NULL,
  `amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `payment_date` date NOT NULL,
  `bank_account_id` int(11) NOT NULL,
  `check_number` varchar(100) DEFAULT NULL,
  `receipt_path` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `credit_payments`
--

INSERT INTO `credit_payments` (`id`, `credit_id`, `amount`, `payment_date`, `bank_account_id`, `check_number`, `receipt_path`, `created_at`) VALUES
(2, 2, 300.00, '2026-05-21', 2, '8465102', 'Uploads/Payments/2/1779383408_paisaje1.jpg', '2026-05-21 17:10:08'),
(3, 3, 100.00, '2026-05-22', 3, '984510', 'Uploads/Payments/3/1779492791_paisaje1.jpg', '2026-05-22 23:33:11');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `digital_documents`
--

CREATE TABLE `digital_documents` (
  `id` int(11) NOT NULL,
  `tipo_documento` varchar(100) NOT NULL,
  `project_id` int(11) DEFAULT NULL,
  `modulo_relacionado` varchar(100) DEFAULT NULL,
  `nombre_documento` varchar(255) NOT NULL,
  `etiquetas` varchar(255) DEFAULT NULL,
  `archivo_path` varchar(255) NOT NULL,
  `tipo_archivo` varchar(50) DEFAULT NULL,
  `peso_archivo` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `digital_documents`
--

INSERT INTO `digital_documents` (`id`, `tipo_documento`, `project_id`, `modulo_relacionado`, `nombre_documento`, `etiquetas`, `archivo_path`, `tipo_archivo`, `peso_archivo`, `created_at`) VALUES
(1, 'Cheque', 1, 'Finanzas', 'Prueba 1', 'prueba 1', 'Uploads/Documents/doc_6a0f5e49d8fb6_1779392073.jpg', 'jpg', 33938, '2026-05-21 19:34:33'),
(2, 'Fotografía', 1, 'Mantenimiento', 'Prueba 2', 'prueba 2', 'Uploads/Documents/default/doc_6a0f61b25ecff_1779392946.jpg', 'jpg', 22835, '2026-05-21 19:49:06'),
(3, 'Cheque', 2, 'Mantenimiento', 'Prueba 3', 'legal', 'Uploads/Documents/admin/doc_6a10e88f94b7e_1779493007.jpg', 'jpg', 33938, '2026-05-22 23:36:47');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `estimations`
--

CREATE TABLE `estimations` (
  `id` int(11) NOT NULL,
  `project_id` int(11) NOT NULL,
  `periodo` varchar(100) NOT NULL,
  `observaciones` text DEFAULT NULL,
  `estado` enum('Borrador','En Revisión','Aprobado') DEFAULT 'En Revisión',
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `estimations`
--

INSERT INTO `estimations` (`id`, `project_id`, `periodo`, `observaciones`, `estado`, `created_at`) VALUES
(1, 1, 'Semana 1 (1 a 4 dias)', 'Prueba 1 para saber si todo carga correctamente', 'En Revisión', '2026-05-21 17:23:29'),
(2, 1, '2 Semanas', 'Prueba 2 para saber si todo funciona bien', 'En Revisión', '2026-05-21 17:27:10'),
(3, 1, 'Prueba 3', 'Prueba 3', 'En Revisión', '2026-05-21 17:30:40'),
(4, 2, '2 semanas', 'Prueba 2 para saber si todo carga bien', 'En Revisión', '2026-05-22 23:23:38');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `estimation_items`
--

CREATE TABLE `estimation_items` (
  `id` int(11) NOT NULL,
  `estimation_id` int(11) NOT NULL,
  `budget_item_id` int(11) NOT NULL,
  `porcentaje_avance` decimal(5,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `estimation_items`
--

INSERT INTO `estimation_items` (`id`, `estimation_id`, `budget_item_id`, `porcentaje_avance`, `created_at`) VALUES
(1, 1, 1, 50.00, '2026-05-21 17:23:29'),
(2, 2, 2, 100.00, '2026-05-21 17:27:10'),
(3, 2, 1, 100.00, '2026-05-21 17:27:10'),
(4, 3, 2, 0.00, '2026-05-21 17:30:40'),
(5, 3, 1, 100.00, '2026-05-21 17:30:40'),
(6, 4, 3, 100.00, '2026-05-22 23:23:38');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `expenses`
--

CREATE TABLE `expenses` (
  `id` int(11) NOT NULL,
  `proyecto_id` int(11) DEFAULT NULL,
  `tipo_egreso` varchar(100) NOT NULL,
  `monto` decimal(15,2) NOT NULL DEFAULT 0.00,
  `fecha_egreso` date NOT NULL,
  `cuenta_origen` varchar(255) DEFAULT NULL,
  `numero_cheque` varchar(100) DEFAULT NULL,
  `beneficiario` varchar(255) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `comprobante_path` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `expenses`
--

INSERT INTO `expenses` (`id`, `proyecto_id`, `tipo_egreso`, `monto`, `fecha_egreso`, `cuenta_origen`, `numero_cheque`, `beneficiario`, `descripcion`, `comprobante_path`, `created_at`) VALUES
(4, NULL, 'Proveedor', 500.00, '2026-06-04', 'banco industrial - 984651023', '978415230.', 'Prueba 2', 'Prueba 2 para saber si ahora me descuenta el dinero con el banco que ya le di un ingreso', 'Uploads/Expenses/4/1780615046_carro3.jpg', '2026-06-04 23:17:26'),
(5, NULL, 'Recurrentes', 300.00, '2026-06-05', 'banco industrial - 984651023', '894615320', 'Prueba', 'Prueba para saber si todo funciona correctamente al cargar todo y que funcione el egreso en el banco seleccionado', 'Uploads/Expenses/5/1780618026_carro4.jpg', '2026-06-05 00:07:06');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `expense_records`
--

CREATE TABLE `expense_records` (
  `id` int(11) NOT NULL,
  `expense_id` int(11) NOT NULL,
  `descripcion` text NOT NULL,
  `monto` decimal(15,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `expense_records`
--

INSERT INTO `expense_records` (`id`, `expense_id`, `descripcion`, `monto`, `created_at`) VALUES
(3, 4, 'Registro 2', 500.00, '2026-06-04 23:17:26'),
(4, 5, 'Registro 1', 150.00, '2026-06-05 00:07:06'),
(5, 5, 'Registro 2', 150.00, '2026-06-05 00:07:06');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `incomes`
--

CREATE TABLE `incomes` (
  `id` int(11) NOT NULL,
  `proyecto_id` int(11) DEFAULT NULL,
  `tipo_ingreso` varchar(100) NOT NULL,
  `monto` decimal(15,2) NOT NULL DEFAULT 0.00,
  `fecha_ingreso` date NOT NULL,
  `cuenta_bancaria` varchar(255) NOT NULL,
  `numero_cheque` varchar(100) DEFAULT NULL,
  `pagador` varchar(255) DEFAULT NULL,
  `descripcion` text DEFAULT NULL,
  `comprobante_path` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `incomes`
--

INSERT INTO `incomes` (`id`, `proyecto_id`, `tipo_ingreso`, `monto`, `fecha_ingreso`, `cuenta_bancaria`, `numero_cheque`, `pagador`, `descripcion`, `comprobante_path`, `created_at`) VALUES
(4, 2, 'Prueba 1', 2000.00, '2026-06-04', 'banco industrial - 984651023', '978415320', 'Prueba 1', 'Prueba 1 para saber si todo conecta correctamente con el apartado de bancos y si suma todo bien', 'Uploads/Incomes/4/1780614971_carro2.jpg', '2026-06-04 23:16:11'),
(5, NULL, 'Prueba 3', 300.00, '2026-06-04', 'Promerica - 7984651320', '9846510', 'Prueba 3', 'Prueba 3 para saber si carga bien todo', 'Uploads/Incomes/5/1780615194_carro4.jpg', '2026-06-04 23:19:54');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `income_records`
--

CREATE TABLE `income_records` (
  `id` int(11) NOT NULL,
  `income_id` int(11) NOT NULL,
  `descripcion` text NOT NULL,
  `monto` decimal(15,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `income_records`
--

INSERT INTO `income_records` (`id`, `income_id`, `descripcion`, `monto`, `created_at`) VALUES
(4, 4, 'Registro 1', 2000.00, '2026-06-04 23:16:11'),
(5, 5, 'Registro 1', 100.00, '2026-06-04 23:19:54'),
(6, 5, 'Registro 2', 200.00, '2026-06-04 23:19:54');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `inventory_items`
--

CREATE TABLE `inventory_items` (
  `id` int(11) NOT NULL,
  `tipo_item` enum('Material','Repuesto','Herramienta','Consumible') NOT NULL,
  `codigo_sku` varchar(100) NOT NULL,
  `nombre` varchar(255) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `unidad_medida` varchar(50) NOT NULL,
  `costo_unitario` decimal(12,2) NOT NULL DEFAULT 0.00,
  `stock_minimo` decimal(12,2) NOT NULL DEFAULT 0.00,
  `stock_actual` decimal(12,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `inventory_items`
--

INSERT INTO `inventory_items` (`id`, `tipo_item`, `codigo_sku`, `nombre`, `descripcion`, `unidad_medida`, `costo_unitario`, `stock_minimo`, `stock_actual`, `created_at`, `updated_at`) VALUES
(3, 'Repuesto', '64564', 'Cemento', 'Cemento de buena calidad', 'm2', 10.00, 100.00, 170.00, '2026-05-20 21:03:34', '2026-05-26 14:12:31'),
(4, 'Consumible', '97841', 'Prueba 2', 'Prueba 2 para saber si carga todo bien', 'litros', 20.00, 100.00, 500.00, '2026-05-22 22:46:40', '2026-05-22 22:47:09'),
(5, 'Herramienta', '9874615320', 'Prueba 3', 'Prueba 3 para saber si todo carga correctamente', 'm2', 10.00, 200.00, 100.00, '2026-05-26 14:13:37', '2026-05-26 14:17:46');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `inventory_kardex`
--

CREATE TABLE `inventory_kardex` (
  `id` int(11) NOT NULL,
  `tipo_movimiento` enum('Entrada','Salida','Traslado','Ajuste') NOT NULL,
  `item_id` int(11) NOT NULL,
  `proyecto_origen_id` int(11) DEFAULT NULL,
  `proyecto_destino_id` int(11) DEFAULT NULL,
  `cantidad` decimal(12,2) NOT NULL,
  `costo_unitario` decimal(12,2) NOT NULL DEFAULT 0.00,
  `referencia_documento` varchar(255) DEFAULT NULL,
  `notas` text DEFAULT NULL,
  `fecha_movimiento` datetime NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `inventory_kardex`
--

INSERT INTO `inventory_kardex` (`id`, `tipo_movimiento`, `item_id`, `proyecto_origen_id`, `proyecto_destino_id`, `cantidad`, `costo_unitario`, `referencia_documento`, `notas`, `fecha_movimiento`, `created_at`) VALUES
(3, 'Entrada', 3, NULL, NULL, 150.00, 100.00, '9845102', 'Ingresando 100 unidades m2 de cemento a la bodega', '2026-05-20 21:03:00', '2026-05-20 21:04:15'),
(4, 'Entrada', 4, NULL, NULL, 500.00, 20.00, '654651', 'Prueba 2 para saber si todo carga bien', '2026-05-22 22:46:00', '2026-05-22 22:47:09'),
(6, 'Entrada', 5, NULL, 1, 100.00, 10.00, '894615320', 'Prueba 4', '2026-05-26 14:17:00', '2026-05-26 14:17:46');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `machinery`
--

CREATE TABLE `machinery` (
  `id` int(11) NOT NULL,
  `categoria` enum('Maquinaria Pesada','Maquinaria Especial','Vehículo','Transporte Pesado','Equipo Menor') NOT NULL,
  `codigo_interno` varchar(50) NOT NULL,
  `marca` varchar(100) NOT NULL,
  `modelo` varchar(100) NOT NULL,
  `numero_serie` varchar(100) DEFAULT NULL,
  `anio_fabricacion` smallint(6) DEFAULT NULL,
  `placa` varchar(20) DEFAULT NULL,
  `horometro_actual` int(11) NOT NULL DEFAULT 0,
  `kilometraje_actual` int(11) NOT NULL DEFAULT 0,
  `intervalo_servicio` int(11) DEFAULT NULL,
  `fecha_ultimo_servicio` date DEFAULT NULL,
  `operador_id` int(10) UNSIGNED DEFAULT NULL,
  `proyecto_id` int(11) DEFAULT NULL,
  `estado` enum('Activo','En Mantenimiento','En Reparación','Inactivo') NOT NULL DEFAULT 'Activo',
  `costo_adquisicion` decimal(12,2) DEFAULT NULL,
  `fecha_adquisicion` date DEFAULT NULL,
  `foto_path` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `machinery`
--

INSERT INTO `machinery` (`id`, `categoria`, `codigo_interno`, `marca`, `modelo`, `numero_serie`, `anio_fabricacion`, `placa`, `horometro_actual`, `kilometraje_actual`, `intervalo_servicio`, `fecha_ultimo_servicio`, `operador_id`, `proyecto_id`, `estado`, `costo_adquisicion`, `fecha_adquisicion`, `foto_path`, `created_at`) VALUES
(1, 'Maquinaria Especial', '001-2026', 'CAT', 'MOTONIVELADORA', '234DFSDF234', 2001, '567sfd', 1950, 15000, 360, '2026-05-03', 1, 1, 'Activo', 700000.00, '2026-05-22', 'Uploads/Machinery/1/foto.jpg', '2026-05-20 19:45:11'),
(2, 'Maquinaria Pesada', 'EX-042', 'CATERPILAR', '2026', 'SDFDF23423', 2002, 'MD234M', 1600, 30000, 500, '2026-05-16', 3, 1, 'Activo', 500.00, '2026-05-20', 'Uploads/Machinery/2/foto.jpg', '2026-05-22 22:31:16');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `machinery_log`
--

CREATE TABLE `machinery_log` (
  `id` int(11) NOT NULL,
  `maquina_id` int(11) NOT NULL,
  `proyecto_id` int(11) DEFAULT NULL,
  `fecha` date NOT NULL,
  `horometro_inicial` int(11) NOT NULL,
  `horometro_final` int(11) NOT NULL,
  `combustible_consumido` decimal(8,2) DEFAULT NULL,
  `observaciones` text DEFAULT NULL,
  `operador_id` int(10) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `machinery_log`
--

INSERT INTO `machinery_log` (`id`, `maquina_id`, `proyecto_id`, `fecha`, `horometro_inicial`, `horometro_final`, `combustible_consumido`, `observaciones`, `operador_id`, `created_at`) VALUES
(1, 1, 1, '2026-05-20', 15000, 30000, 95641.00, 'Prueba para saber si todo funciona correctamente', 1, '2026-05-20 19:46:39'),
(3, 2, 1, '2026-05-22', 600, 1500, 3600.00, 'Prueba para saber si todo carga bien', 3, '2026-05-22 22:32:14');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `maintenance_logs`
--

CREATE TABLE `maintenance_logs` (
  `id` int(11) NOT NULL,
  `machinery_id` int(11) NOT NULL,
  `tipo_mantenimiento` enum('Preventivo','Correctivo') NOT NULL,
  `fecha_mantenimiento` date NOT NULL,
  `descripcion` text NOT NULL,
  `costo_total` decimal(15,2) NOT NULL DEFAULT 0.00,
  `responsable_id` int(10) UNSIGNED DEFAULT NULL,
  `proximo_mantenimiento` date DEFAULT NULL,
  `horometro_servicio` int(11) NOT NULL,
  `observaciones` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `latitud` varchar(50) DEFAULT NULL,
  `longitud` varchar(50) DEFAULT NULL,
  `path_fotos` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `maintenance_logs`
--

INSERT INTO `maintenance_logs` (`id`, `machinery_id`, `tipo_mantenimiento`, `fecha_mantenimiento`, `descripcion`, `costo_total`, `responsable_id`, `proximo_mantenimiento`, `horometro_servicio`, `observaciones`, `created_at`, `latitud`, `longitud`, `path_fotos`) VALUES
(6, 1, 'Correctivo', '2026-05-21', 'Prueba en el campo de trabajo realizado', 240.00, 1, '2026-06-21', 1950, 'Prueba en el campo de observaciones', '2026-05-21 19:21:50', '14.553938', '-90.546741', '[\"Uploads\\/Maintenance\\/6\\/foto_1_1779391310.jpg\",\"Uploads\\/Maintenance\\/6\\/foto_2_1779391310.jpg\",\"Uploads\\/Maintenance\\/6\\/foto_3_1779391310.jpg\",\"Uploads\\/Maintenance\\/6\\/foto_4_1779391310.jpg\",\"Uploads\\/Maintenance\\/6\\/foto_5_1779391310.jpg\"]'),
(7, 2, 'Preventivo', '2026-05-22', 'Prueba para saber si todo carga bien', 50.00, 3, '2026-05-31', 1600, 'Prueba de observaciones para saber si todo carga bien', '2026-05-22 23:19:02', '14.497033', '-90.557728', '[\"Uploads\\/Maintenance\\/7\\/foto_1_1779491942.jpg\",\"Uploads\\/Maintenance\\/7\\/foto_2_1779491942.jpg\",\"Uploads\\/Maintenance\\/7\\/foto_3_1779491942.jpg\"]');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `maintenance_parts`
--

CREATE TABLE `maintenance_parts` (
  `id` int(11) NOT NULL,
  `maintenance_log_id` int(11) NOT NULL,
  `nombre_repuesto` varchar(255) NOT NULL,
  `cantidad` decimal(10,2) NOT NULL DEFAULT 1.00,
  `costo_unitario` decimal(15,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `maintenance_parts`
--

INSERT INTO `maintenance_parts` (`id`, `maintenance_log_id`, `nombre_repuesto`, `cantidad`, `costo_unitario`, `created_at`) VALUES
(7, 6, 'PIeza 1', 2.00, 120.00, '2026-05-21 19:21:50'),
(8, 7, 'PIeza 4', 1.00, 20.00, '2026-05-22 23:19:02'),
(9, 7, 'Pieza 6', 1.00, 30.00, '2026-05-22 23:19:02');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `payrolls`
--

CREATE TABLE `payrolls` (
  `id` int(11) NOT NULL,
  `periodo` varchar(100) NOT NULL,
  `fecha_corte` date NOT NULL,
  `estado` enum('Borrador','En Revisión','Aprobado','Pagado') DEFAULT 'Borrador',
  `total_pagado` decimal(15,2) DEFAULT 0.00,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `payrolls`
--

INSERT INTO `payrolls` (`id`, `periodo`, `fecha_corte`, `estado`, `total_pagado`, `created_at`) VALUES
(1, 'Primera Quincena', '2026-05-22', 'Pagado', 5000.00, '2026-05-21 18:02:13'),
(2, 'Mes Completo', '2026-05-31', 'Pagado', 22413.75, '2026-05-21 18:03:02'),
(3, 'Mes Completo', '2026-05-31', 'Pagado', 2682.50, '2026-05-22 23:45:05');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `payroll_details`
--

CREATE TABLE `payroll_details` (
  `id` int(11) NOT NULL,
  `payroll_id` int(11) NOT NULL,
  `personnel_id` int(10) UNSIGNED NOT NULL,
  `salario_base_aplicado` decimal(15,2) NOT NULL DEFAULT 0.00,
  `horas_trabajadas` int(11) NOT NULL DEFAULT 0,
  `horas_extras` int(11) NOT NULL DEFAULT 0,
  `monto_horas_extras` decimal(15,2) NOT NULL DEFAULT 0.00,
  `bonificaciones` decimal(15,2) NOT NULL DEFAULT 0.00,
  `deducciones` decimal(15,2) NOT NULL DEFAULT 0.00,
  `total_neto` decimal(15,2) NOT NULL DEFAULT 0.00,
  `observaciones` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `payroll_details`
--

INSERT INTO `payroll_details` (`id`, `payroll_id`, `personnel_id`, `salario_base_aplicado`, `horas_trabajadas`, `horas_extras`, `monto_horas_extras`, `bonificaciones`, `deducciones`, `total_neto`, `observaciones`, `created_at`) VALUES
(1, 1, 1, 5000.00, 160, 0, 0.00, 0.00, 0.00, 5000.00, NULL, '2026-05-21 18:02:13'),
(2, 2, 1, 5000.00, 160, 50, 2343.75, 100.00, 30.00, 7413.75, 'Prueba1', '2026-05-21 18:03:02'),
(3, 2, 2, 15000.00, 160, 0, 0.00, 0.00, 0.00, 15000.00, NULL, '2026-05-21 18:03:02'),
(4, 3, 3, 6000.00, 60, 10, 562.50, 20.00, 150.00, 2682.50, 'Prueba para saber si funciona todo correctamente y si tambien carga todo correctamente', '2026-05-22 23:45:05');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `personnel`
--

CREATE TABLE `personnel` (
  `id` int(10) UNSIGNED NOT NULL,
  `tipo_empleado` enum('Administrativo','Operador','Piloto','Contratista') NOT NULL,
  `nombres` varchar(150) NOT NULL,
  `apellidos` varchar(150) NOT NULL,
  `dpi` varchar(13) NOT NULL,
  `nit` varchar(20) DEFAULT NULL,
  `telefono` varchar(9) DEFAULT NULL,
  `direccion` text DEFAULT NULL,
  `puesto` varchar(100) NOT NULL,
  `tipo_planilla` enum('Quincenal','Mensual','Semanal','Diario') NOT NULL,
  `salario_base` decimal(10,2) NOT NULL DEFAULT 0.00,
  `tarifa_hora_extra` decimal(10,2) DEFAULT NULL,
  `fecha_contratacion` date NOT NULL,
  `fecha_baja` date DEFAULT NULL,
  `numero_cuenta` varchar(50) DEFAULT NULL,
  `nombre_banco` varchar(100) DEFAULT NULL,
  `foto_path` varchar(255) DEFAULT NULL,
  `proyecto_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `personnel`
--

INSERT INTO `personnel` (`id`, `tipo_empleado`, `nombres`, `apellidos`, `dpi`, `nit`, `telefono`, `direccion`, `puesto`, `tipo_planilla`, `salario_base`, `tarifa_hora_extra`, `fecha_contratacion`, `fecha_baja`, `numero_cuenta`, `nombre_banco`, `foto_path`, `proyecto_id`, `created_at`, `updated_at`) VALUES
(1, 'Operador', 'Juan', 'Perez', '6546516510650', '9874', '9878-4651', 'Prueba de direccion', 'Programador', 'Mensual', 5000.00, 25.00, '2026-05-01', NULL, '987415', 'sdfsdf', 'Uploads/Personal/1/foto.jpg', 1, '2026-05-20 18:43:35', '2026-05-21 17:57:18'),
(2, 'Contratista', 'Karla', 'Archila', '8746135030356', '6845102', '6841-6515', 'Prueba', 'Contratista', 'Quincenal', 15000.00, 100.00, '2026-05-02', NULL, '68415302', 'asdf', 'Uploads/Personal/2/foto.jpg', 1, '2026-05-21 16:24:43', '2026-05-21 17:57:01'),
(3, 'Piloto', 'Santiago', 'Hernandez', '5481560332023', '984150', '9841-0320', 'Prueba para saber si carga bien', 'Programador', 'Semanal', 6000.00, 25.00, '2026-05-24', '2026-06-07', '5120', 'Prueba', 'Uploads/Personal/3/foto.jpg', 1, '2026-05-22 22:24:30', '2026-05-29 16:33:07');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `projects`
--

CREATE TABLE `projects` (
  `id` int(11) NOT NULL,
  `codigo` varchar(20) NOT NULL,
  `nombre` varchar(255) NOT NULL,
  `cliente_id` int(11) DEFAULT NULL,
  `ubicacion` varchar(255) DEFAULT NULL,
  `coordenadas` varchar(100) DEFAULT NULL,
  `presupuesto` decimal(15,2) NOT NULL DEFAULT 0.00,
  `fecha_inicio` date NOT NULL,
  `fecha_fin_estimada` date DEFAULT NULL,
  `fecha_fin_real` date DEFAULT NULL,
  `estado` enum('Borrador','Activo','Pausado','Completado','Cancelado') NOT NULL DEFAULT 'Borrador',
  `numero_contrato` varchar(100) DEFAULT NULL,
  `contratos_archivos` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`contratos_archivos`)),
  `foto` varchar(255) DEFAULT NULL,
  `descripcion` text DEFAULT NULL,
  `contactos` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`contactos`)),
  `gerente_id` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `projects`
--

INSERT INTO `projects` (`id`, `codigo`, `nombre`, `cliente_id`, `ubicacion`, `coordenadas`, `presupuesto`, `fecha_inicio`, `fecha_fin_estimada`, `fecha_fin_real`, `estado`, `numero_contrato`, `contratos_archivos`, `foto`, `descripcion`, `contactos`, `gerente_id`, `created_at`, `updated_at`) VALUES
(1, '2026-001', 'Puente', 1, 'Zona 14', '14.600518, -90.509191', 20000.00, '2026-05-01', '2026-05-14', '2026-05-20', 'Activo', '0000', '[\"Uploads\\/Projects\\/1\\/docs\\/FODA_de_trabajo_de_graduaci__n.pdf\",\"Uploads\\/Projects\\/1\\/docs\\/Propuesta_te__rica_y_dise__o_de_la_propuesta_te__rica.pdf\",\"Uploads\\/Projects\\/1\\/docs\\/Evaluaci__n_Final_____Pregunta_1.pdf\"]', 'Uploads/Projects/1/foto_1779738193.webp', 'Prueba para saber si todo funciona correctamente y sin problema', '[{\"tipo\":\"Proveedor\",\"nombre\":\"Prueba 1\",\"telefono\":\"98754132\",\"email\":\"prueba1@gmail.com\"}]', 2, '2026-05-20 18:18:29', '2026-05-26 14:44:05'),
(2, 'PRY-001-2026', 'ASFALTO', 1, 'Zona 16', '14.628476, -90.510521', 250000.00, '2026-05-01', '2026-05-10', '2026-05-23', 'Activo', '89415615', '[\"Uploads\\/Projects\\/2\\/docs\\/PROYECTO_FINAL.pdf\",\"Uploads\\/Projects\\/2\\/docs\\/Evaluaci__n_Final_____Pregunta_4.pdf\"]', 'Uploads/Projects/2/foto_1779738092.png', 'Prueba 2 para saber si funciona todo correctamente', '[{\"tipo\":\"Supervisor\",\"nombre\":\"Rodrigo\",\"telefono\":\"8798-9874\",\"email\":\"prueba@gmail.com\"}]', 2, '2026-05-22 22:40:16', '2026-05-26 14:43:48');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `project_incomes`
--

CREATE TABLE `project_incomes` (
  `id` int(11) NOT NULL,
  `project_id` int(11) NOT NULL,
  `tipo_cobro` enum('Anticipo','Estimacion','Pago Final') NOT NULL,
  `numero_estimacion` int(11) DEFAULT NULL COMMENT 'Solo aplicable si tipo_cobro es Estimacion (1 al 8)',
  `monto_total` decimal(15,2) NOT NULL DEFAULT 0.00,
  `porcentaje_contrato` decimal(5,2) NOT NULL DEFAULT 0.00,
  `fecha_registro` date NOT NULL,
  `observaciones` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `project_incomes`
--

INSERT INTO `project_incomes` (`id`, `project_id`, `tipo_cobro`, `numero_estimacion`, `monto_total`, `porcentaje_contrato`, `fecha_registro`, `observaciones`, `created_at`, `updated_at`) VALUES
(1, 2, 'Anticipo', NULL, 50000.00, 20.00, '2026-05-27', 'Prueba 1', '2026-05-27 21:51:18', '2026-05-27 21:51:18'),
(2, 2, 'Estimacion', 1, 70000.00, 28.00, '2026-05-27', 'Prueba 1', '2026-05-27 22:09:47', '2026-05-27 22:09:47'),
(3, 2, 'Estimacion', 2, 25000.00, 10.00, '2026-05-27', 'Prueba 2', '2026-05-27 22:13:42', '2026-05-27 22:13:42'),
(4, 1, 'Anticipo', NULL, 4000.00, 20.00, '2026-05-28', 'Prueba 2 de Anticipo', '2026-05-28 14:14:53', '2026-05-28 14:14:53'),
(5, 1, 'Estimacion', 1, 15000.00, 75.00, '2026-05-28', 'Prueba 1 de estimacion proyecto 1', '2026-05-28 14:21:02', '2026-05-28 14:21:02'),
(6, 1, 'Estimacion', 2, 1000.00, 5.00, '2026-05-28', 'Prueba 3', '2026-05-28 14:37:00', '2026-05-28 14:37:00');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `project_income_sources`
--

CREATE TABLE `project_income_sources` (
  `id` int(11) NOT NULL,
  `project_income_id` int(11) NOT NULL,
  `fuente` enum('Consejo de Desarrollo','Municipalidad','COCODE') NOT NULL,
  `porcentaje_aporte` decimal(5,2) NOT NULL DEFAULT 0.00 COMMENT 'La suma de las 3 fuentes debe ser 100%',
  `monto_aportado` decimal(15,2) NOT NULL DEFAULT 0.00,
  `fecha_cobro` date DEFAULT NULL,
  `numero_documento` varchar(100) DEFAULT NULL,
  `bank_account_id` int(11) DEFAULT NULL,
  `comprobante_path` varchar(255) DEFAULT NULL,
  `estado` enum('Pendiente','Recibido') NOT NULL DEFAULT 'Pendiente',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `project_income_sources`
--

INSERT INTO `project_income_sources` (`id`, `project_income_id`, `fuente`, `porcentaje_aporte`, `monto_aportado`, `fecha_cobro`, `numero_documento`, `bank_account_id`, `comprobante_path`, `estado`, `created_at`, `updated_at`) VALUES
(1, 1, 'Consejo de Desarrollo', 25.00, 12500.00, '0000-00-00', '', NULL, NULL, 'Pendiente', '2026-05-27 21:51:18', '2026-05-27 21:51:18'),
(2, 1, 'Municipalidad', 25.00, 12500.00, '0000-00-00', '', NULL, NULL, 'Pendiente', '2026-05-27 21:51:18', '2026-05-27 21:51:18'),
(3, 1, 'COCODE', 50.00, 25000.00, '0000-00-00', '', NULL, NULL, 'Pendiente', '2026-05-27 21:51:18', '2026-05-27 21:51:18'),
(4, 2, 'Consejo de Desarrollo', 25.00, 17500.00, '0000-00-00', '', 1, NULL, 'Pendiente', '2026-05-27 22:09:47', '2026-05-27 22:09:47'),
(5, 2, 'Municipalidad', 25.00, 17500.00, '0000-00-00', '', NULL, NULL, 'Pendiente', '2026-05-27 22:09:47', '2026-05-27 22:09:47'),
(6, 2, 'COCODE', 50.00, 35000.00, '0000-00-00', '', NULL, NULL, 'Pendiente', '2026-05-27 22:09:47', '2026-05-27 22:09:47'),
(7, 3, 'Consejo de Desarrollo', 98.00, 24500.00, '2026-05-29', '978410', 2, NULL, 'Recibido', '2026-05-27 22:13:42', '2026-05-27 22:13:42'),
(8, 3, 'Municipalidad', 1.00, 250.00, '0000-00-00', '', NULL, NULL, 'Pendiente', '2026-05-27 22:13:42', '2026-05-27 22:13:42'),
(9, 3, 'COCODE', 1.00, 250.00, '0000-00-00', '', NULL, NULL, 'Pendiente', '2026-05-27 22:13:42', '2026-05-27 22:13:42'),
(10, 4, 'Consejo de Desarrollo', 25.00, 1000.00, '2026-05-31', '845120', 3, 'Uploads/ProjectIncomes/Proyecto_1/comprobante_1779977693_0.png', 'Recibido', '2026-05-28 14:14:53', '2026-05-28 14:14:53'),
(11, 4, 'Municipalidad', 50.00, 2000.00, '2026-06-05', '87451320', 2, 'Uploads/ProjectIncomes/Proyecto_1/comprobante_1779977693_1.png', 'Recibido', '2026-05-28 14:14:53', '2026-05-28 14:14:53'),
(12, 4, 'COCODE', 25.00, 1000.00, '2026-05-31', '89746150', 1, 'Uploads/ProjectIncomes/Proyecto_1/comprobante_1779977693_2.png', 'Recibido', '2026-05-28 14:14:53', '2026-05-28 14:14:53'),
(13, 5, 'Consejo de Desarrollo', 66.67, 10000.00, '2026-05-19', '784510', 3, 'Uploads/ProjectIncomes/Proyecto_1/comprobante_1779978062_0.png', 'Recibido', '2026-05-28 14:21:02', '2026-05-28 14:21:02'),
(14, 5, 'Municipalidad', 20.00, 3000.00, '2026-06-07', '9874150', 1, 'Uploads/ProjectIncomes/Proyecto_1/comprobante_1779978062_1.png', 'Recibido', '2026-05-28 14:21:02', '2026-05-28 14:21:02'),
(15, 5, 'COCODE', 13.33, 2000.00, '2026-06-05', '897746150', 2, 'Uploads/ProjectIncomes/Proyecto_1/comprobante_1779978062_2.png', 'Recibido', '2026-05-28 14:21:02', '2026-05-28 14:21:02'),
(16, 6, 'Consejo de Desarrollo', 100.00, 1000.00, '2026-06-07', '987150', 1, 'Uploads/ProjectIncomes/Proyecto_1/Estimaciones/comprobante_1779979020_0.png', 'Recibido', '2026-05-28 14:37:00', '2026-05-28 14:37:00'),
(17, 6, 'Municipalidad', 0.00, 0.00, NULL, NULL, NULL, NULL, 'Pendiente', '2026-05-28 14:37:00', '2026-05-28 14:37:00'),
(18, 6, 'COCODE', 0.00, 0.00, NULL, NULL, NULL, NULL, 'Pendiente', '2026-05-28 14:37:00', '2026-05-28 14:37:00');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `purchase_orders`
--

CREATE TABLE `purchase_orders` (
  `id` int(11) NOT NULL,
  `proveedor_id` int(11) NOT NULL,
  `proyecto_id` int(11) NOT NULL,
  `fecha_orden` date NOT NULL,
  `condicion_pago` enum('Contado','Crédito') NOT NULL DEFAULT 'Contado',
  `observaciones` text DEFAULT NULL,
  `archivo_adjunto` varchar(255) DEFAULT NULL,
  `total` decimal(15,2) NOT NULL DEFAULT 0.00,
  `estado` enum('Pendiente','Aprobada','Rechazada') NOT NULL DEFAULT 'Pendiente',
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `purchase_orders`
--

INSERT INTO `purchase_orders` (`id`, `proveedor_id`, `proyecto_id`, `fecha_orden`, `condicion_pago`, `observaciones`, `archivo_adjunto`, `total`, `estado`, `created_at`) VALUES
(2, 2, 1, '2026-05-20', 'Crédito', 'Prueba 1', 'Uploads/Purchases/2/1779312036_paisaje1.jpg', 85.00, 'Pendiente', '2026-05-20 21:20:36'),
(3, 3, 2, '2026-05-22', 'Contado', 'Prueba 2 para saber si todo carga bien', 'Uploads/Purchases/3/1779490442_Evaluación Final – Pregunta 6.pdf', 120.00, 'Pendiente', '2026-05-22 22:54:02');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `purchase_order_items`
--

CREATE TABLE `purchase_order_items` (
  `id` int(11) NOT NULL,
  `purchase_order_id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `cantidad` decimal(12,2) NOT NULL,
  `precio_unitario` decimal(12,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `purchase_order_items`
--

INSERT INTO `purchase_order_items` (`id`, `purchase_order_id`, `item_id`, `cantidad`, `precio_unitario`, `created_at`) VALUES
(3, 2, 3, 2.00, 20.00, '2026-05-20 21:20:36'),
(4, 2, 3, 3.00, 15.00, '2026-05-20 21:20:36'),
(5, 3, 4, 6.00, 20.00, '2026-05-22 22:54:02');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `recurrents`
--

CREATE TABLE `recurrents` (
  `id` int(11) NOT NULL,
  `concepto` varchar(255) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `monto` decimal(15,2) DEFAULT NULL,
  `dia_pago` int(11) DEFAULT NULL,
  `creado_por` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Volcado de datos para la tabla `recurrents`
--

INSERT INTO `recurrents` (`id`, `concepto`, `descripcion`, `monto`, `dia_pago`, `creado_por`, `created_at`, `updated_at`) VALUES
(2, 'Pago de luz', 'Prueba 1 para saber si todo carga correctamente', 300.00, 16, 'admin_pro', '2026-06-04 23:58:55', '2026-06-05 00:15:31'),
(3, 'Luz', 'Prueba', 2000.00, NULL, 'admin_pro', '2026-06-05 00:10:18', '2026-06-05 00:16:08');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `suppliers`
--

CREATE TABLE `suppliers` (
  `id` int(11) NOT NULL,
  `razon_social` varchar(255) NOT NULL,
  `nit` varchar(50) NOT NULL,
  `direccion` varchar(255) NOT NULL,
  `telefono` varchar(20) NOT NULL,
  `correo_electronico` varchar(255) DEFAULT NULL,
  `contacto_principal` varchar(255) DEFAULT NULL,
  `condicion_pago` enum('Contado','Crédito') NOT NULL DEFAULT 'Contado',
  `dias_credito` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `suppliers`
--

INSERT INTO `suppliers` (`id`, `razon_social`, `nit`, `direccion`, `telefono`, `correo_electronico`, `contacto_principal`, `condicion_pago`, `dias_credito`, `created_at`, `updated_at`) VALUES
(2, 'Prueba 1', '984150', 'Prueba 1 para direccion', '5641-6510', 'prueba1@gmail.com', 'Sergio', 'Crédito', 6, '2026-05-20 21:20:12', '2026-05-20 21:20:12'),
(3, 'Prueba 2', '876410', 'Prueba 2 de direccion', '9876-1302', 'prueba2@gmail.com', 'Admin', 'Contado', NULL, '2026-05-22 22:53:16', '2026-05-22 22:53:16');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `nombre` varchar(150) NOT NULL,
  `usuario` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `rol` varchar(50) NOT NULL DEFAULT 'admin',
  `estado` enum('Activo','Inactivo') NOT NULL DEFAULT 'Activo',
  `foto` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `users`
--

INSERT INTO `users` (`id`, `nombre`, `usuario`, `password`, `rol`, `estado`, `foto`, `created_at`, `updated_at`) VALUES
(2, 'Admin', 'admin', '$2y$10$bU4xCoXE5sAwb6y7yS1Wl.N9xVsyq7SwY5nVWVVquWVL.5Ehc2Qly', 'admin', 'Activo', 'Uploads/Users/2/foto_1779295276.jpg', '2026-05-20 16:41:16', '2026-05-20 16:41:51'),
(3, 'Maria Gomez', 'mgomez', '$2y$10$wODjihBDEAXDuRb0kNXQpeziPnvLp1Oo5RC3HLgD4lKhhYWuQV0Dq', 'tecnico', 'Activo', 'Uploads/Users/3/foto_1779491227.jpg', '2026-05-22 23:07:07', '2026-05-22 23:07:07');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `vehicles`
--

CREATE TABLE `vehicles` (
  `id` int(11) NOT NULL,
  `placa` varchar(20) NOT NULL,
  `kilometraje` decimal(10,2) NOT NULL DEFAULT 0.00,
  `marca` varchar(100) NOT NULL,
  `modelo` varchar(100) NOT NULL,
  `piloto_id` int(10) UNSIGNED DEFAULT NULL,
  `observaciones` text DEFAULT NULL,
  `foto_frontal` varchar(255) DEFAULT NULL,
  `foto_trasera` varchar(255) DEFAULT NULL,
  `frecuencia_prioridad` enum('Mantenimiento Inicial','Normal','Preventiva','Crítica') NOT NULL DEFAULT 'Normal',
  `estatus` enum('draft','active','maintenance','inactive') NOT NULL DEFAULT 'draft',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `vehicles`
--

INSERT INTO `vehicles` (`id`, `placa`, `kilometraje`, `marca`, `modelo`, `piloto_id`, `observaciones`, `foto_frontal`, `foto_trasera`, `frecuencia_prioridad`, `estatus`, `created_at`, `updated_at`) VALUES
(1, 'SAD-123', 1600.00, 'Toyota', '2026', 3, 'Prueba 1 para saber si todo carga bien incluyendo las fotos', 'Uploads/Vehicles/1/foto_frontal.jpg', 'Uploads/Vehicles/1/foto_trasera.jpg', 'Normal', 'active', '2026-05-29 16:50:17', '2026-05-29 17:01:41'),
(2, 'WERF-123', 20.00, 'Mitsubishi', '2025', 3, 'Prueba 2', 'Uploads/Vehicles/2/foto_frontal.jpg', 'Uploads/Vehicles/2/foto_trasera.jpg', 'Crítica', 'inactive', '2026-05-29 17:04:34', '2026-05-29 17:45:43');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `vehicle_logs`
--

CREATE TABLE `vehicle_logs` (
  `id` int(11) NOT NULL,
  `vehiculo_id` int(11) NOT NULL,
  `piloto_id` int(10) UNSIGNED DEFAULT NULL,
  `estatus_vehiculo` enum('active','maintenance','inactive','draft') NOT NULL DEFAULT 'active',
  `envio_servicio` text DEFAULT NULL,
  `reportar_averia` text DEFAULT NULL,
  `observaciones` text DEFAULT NULL,
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `vehicle_logs`
--

INSERT INTO `vehicle_logs` (`id`, `vehiculo_id`, `piloto_id`, `estatus_vehiculo`, `envio_servicio`, `reportar_averia`, `observaciones`, `fecha_registro`) VALUES
(1, 2, 3, 'maintenance', 'Prueba 1 para saber si cambio todo correctamente a taller', 'Prueba 1 para saber si cambio todo correctamente a taller', 'Prueba 1 para saber si cambio todo correctamente a taller', '2026-05-29 17:43:18'),
(2, 2, 3, 'inactive', 'Prueba 2 para ver si cambia de estado', 'Prueba 2 para ver si cambia de estado', 'Prueba 2 para ver si cambia de estado', '2026-05-29 17:45:43');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `alerts_config`
--
ALTER TABLE `alerts_config`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `alerts_history`
--
ALTER TABLE `alerts_history`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `bank_accounts`
--
ALTER TABLE `bank_accounts`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `budget_items`
--
ALTER TABLE `budget_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `project_id` (`project_id`);

--
-- Indices de la tabla `clients`
--
ALTER TABLE `clients`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `credits`
--
ALTER TABLE `credits`
  ADD PRIMARY KEY (`id`),
  ADD KEY `supplier_id` (`supplier_id`),
  ADD KEY `project_id` (`project_id`);

--
-- Indices de la tabla `credit_payments`
--
ALTER TABLE `credit_payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `credit_id` (`credit_id`),
  ADD KEY `bank_account_id` (`bank_account_id`);

--
-- Indices de la tabla `digital_documents`
--
ALTER TABLE `digital_documents`
  ADD PRIMARY KEY (`id`),
  ADD KEY `project_id` (`project_id`);

--
-- Indices de la tabla `estimations`
--
ALTER TABLE `estimations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `project_id` (`project_id`);

--
-- Indices de la tabla `estimation_items`
--
ALTER TABLE `estimation_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `estimation_id` (`estimation_id`),
  ADD KEY `budget_item_id` (`budget_item_id`);

--
-- Indices de la tabla `expenses`
--
ALTER TABLE `expenses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_expense_project` (`proyecto_id`);

--
-- Indices de la tabla `expense_records`
--
ALTER TABLE `expense_records`
  ADD PRIMARY KEY (`id`),
  ADD KEY `expense_id` (`expense_id`);

--
-- Indices de la tabla `incomes`
--
ALTER TABLE `incomes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_income_project` (`proyecto_id`);

--
-- Indices de la tabla `income_records`
--
ALTER TABLE `income_records`
  ADD PRIMARY KEY (`id`),
  ADD KEY `income_id` (`income_id`);

--
-- Indices de la tabla `inventory_items`
--
ALTER TABLE `inventory_items`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_sku` (`codigo_sku`);

--
-- Indices de la tabla `inventory_kardex`
--
ALTER TABLE `inventory_kardex`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_kardex_item` (`item_id`),
  ADD KEY `fk_kardex_origen` (`proyecto_origen_id`),
  ADD KEY `fk_kardex_destino` (`proyecto_destino_id`);

--
-- Indices de la tabla `machinery`
--
ALTER TABLE `machinery`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_machinery_operador` (`operador_id`),
  ADD KEY `fk_machinery_proyecto` (`proyecto_id`);

--
-- Indices de la tabla `machinery_log`
--
ALTER TABLE `machinery_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_log_maquina` (`maquina_id`),
  ADD KEY `fk_log_proyecto` (`proyecto_id`),
  ADD KEY `fk_log_operador` (`operador_id`);

--
-- Indices de la tabla `maintenance_logs`
--
ALTER TABLE `maintenance_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `machinery_id` (`machinery_id`),
  ADD KEY `responsable_id` (`responsable_id`);

--
-- Indices de la tabla `maintenance_parts`
--
ALTER TABLE `maintenance_parts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `maintenance_log_id` (`maintenance_log_id`);

--
-- Indices de la tabla `payrolls`
--
ALTER TABLE `payrolls`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `payroll_details`
--
ALTER TABLE `payroll_details`
  ADD PRIMARY KEY (`id`),
  ADD KEY `payroll_id` (`payroll_id`),
  ADD KEY `personnel_id` (`personnel_id`);

--
-- Indices de la tabla `personnel`
--
ALTER TABLE `personnel`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_dpi` (`dpi`),
  ADD KEY `idx_proyecto_id` (`proyecto_id`);

--
-- Indices de la tabla `projects`
--
ALTER TABLE `projects`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `codigo` (`codigo`),
  ADD KEY `fk_projects_client` (`cliente_id`);

--
-- Indices de la tabla `project_incomes`
--
ALTER TABLE `project_incomes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_project_incomes_project` (`project_id`);

--
-- Indices de la tabla `project_income_sources`
--
ALTER TABLE `project_income_sources`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_income_sources_income` (`project_income_id`),
  ADD KEY `fk_income_sources_bank` (`bank_account_id`);

--
-- Indices de la tabla `purchase_orders`
--
ALTER TABLE `purchase_orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_po_proveedor` (`proveedor_id`),
  ADD KEY `fk_po_proyecto` (`proyecto_id`);

--
-- Indices de la tabla `purchase_order_items`
--
ALTER TABLE `purchase_order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_poi_orden` (`purchase_order_id`),
  ADD KEY `fk_poi_item` (`item_id`);

--
-- Indices de la tabla `recurrents`
--
ALTER TABLE `recurrents`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `suppliers`
--
ALTER TABLE `suppliers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_nit` (`nit`);

--
-- Indices de la tabla `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `usuario` (`usuario`);

--
-- Indices de la tabla `vehicles`
--
ALTER TABLE `vehicles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `placa_unica` (`placa`),
  ADD KEY `fk_vehicle_pilot` (`piloto_id`);

--
-- Indices de la tabla `vehicle_logs`
--
ALTER TABLE `vehicle_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_vehicle_log_vehicle` (`vehiculo_id`),
  ADD KEY `fk_vehicle_log_pilot` (`piloto_id`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `alerts_config`
--
ALTER TABLE `alerts_config`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `alerts_history`
--
ALTER TABLE `alerts_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `bank_accounts`
--
ALTER TABLE `bank_accounts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `budget_items`
--
ALTER TABLE `budget_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `clients`
--
ALTER TABLE `clients`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `credits`
--
ALTER TABLE `credits`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `credit_payments`
--
ALTER TABLE `credit_payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `digital_documents`
--
ALTER TABLE `digital_documents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `estimations`
--
ALTER TABLE `estimations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `estimation_items`
--
ALTER TABLE `estimation_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `expenses`
--
ALTER TABLE `expenses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `expense_records`
--
ALTER TABLE `expense_records`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `incomes`
--
ALTER TABLE `incomes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `income_records`
--
ALTER TABLE `income_records`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `inventory_items`
--
ALTER TABLE `inventory_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `inventory_kardex`
--
ALTER TABLE `inventory_kardex`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `machinery`
--
ALTER TABLE `machinery`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `machinery_log`
--
ALTER TABLE `machinery_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `maintenance_logs`
--
ALTER TABLE `maintenance_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de la tabla `maintenance_parts`
--
ALTER TABLE `maintenance_parts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT de la tabla `payrolls`
--
ALTER TABLE `payrolls`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `payroll_details`
--
ALTER TABLE `payroll_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `personnel`
--
ALTER TABLE `personnel`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `projects`
--
ALTER TABLE `projects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `project_incomes`
--
ALTER TABLE `project_incomes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `project_income_sources`
--
ALTER TABLE `project_income_sources`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT de la tabla `purchase_orders`
--
ALTER TABLE `purchase_orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `purchase_order_items`
--
ALTER TABLE `purchase_order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `recurrents`
--
ALTER TABLE `recurrents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `suppliers`
--
ALTER TABLE `suppliers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `vehicles`
--
ALTER TABLE `vehicles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `vehicle_logs`
--
ALTER TABLE `vehicle_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `budget_items`
--
ALTER TABLE `budget_items`
  ADD CONSTRAINT `budget_items_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `credits`
--
ALTER TABLE `credits`
  ADD CONSTRAINT `credits_ibfk_1` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`),
  ADD CONSTRAINT `credits_ibfk_2` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`);

--
-- Filtros para la tabla `credit_payments`
--
ALTER TABLE `credit_payments`
  ADD CONSTRAINT `credit_payments_ibfk_1` FOREIGN KEY (`credit_id`) REFERENCES `credits` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `credit_payments_ibfk_2` FOREIGN KEY (`bank_account_id`) REFERENCES `bank_accounts` (`id`);

--
-- Filtros para la tabla `digital_documents`
--
ALTER TABLE `digital_documents`
  ADD CONSTRAINT `digital_documents_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `estimations`
--
ALTER TABLE `estimations`
  ADD CONSTRAINT `estimations_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `estimation_items`
--
ALTER TABLE `estimation_items`
  ADD CONSTRAINT `estimation_items_ibfk_1` FOREIGN KEY (`estimation_id`) REFERENCES `estimations` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `estimation_items_ibfk_2` FOREIGN KEY (`budget_item_id`) REFERENCES `budget_items` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `expenses`
--
ALTER TABLE `expenses`
  ADD CONSTRAINT `fk_expense_project` FOREIGN KEY (`proyecto_id`) REFERENCES `projects` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `expense_records`
--
ALTER TABLE `expense_records`
  ADD CONSTRAINT `expense_records_ibfk_1` FOREIGN KEY (`expense_id`) REFERENCES `expenses` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `incomes`
--
ALTER TABLE `incomes`
  ADD CONSTRAINT `fk_income_project` FOREIGN KEY (`proyecto_id`) REFERENCES `projects` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `income_records`
--
ALTER TABLE `income_records`
  ADD CONSTRAINT `income_records_ibfk_1` FOREIGN KEY (`income_id`) REFERENCES `incomes` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `inventory_kardex`
--
ALTER TABLE `inventory_kardex`
  ADD CONSTRAINT `fk_kardex_destino` FOREIGN KEY (`proyecto_destino_id`) REFERENCES `projects` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_kardex_item` FOREIGN KEY (`item_id`) REFERENCES `inventory_items` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_kardex_origen` FOREIGN KEY (`proyecto_origen_id`) REFERENCES `projects` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `machinery`
--
ALTER TABLE `machinery`
  ADD CONSTRAINT `fk_machinery_operador` FOREIGN KEY (`operador_id`) REFERENCES `personnel` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_machinery_proyecto` FOREIGN KEY (`proyecto_id`) REFERENCES `projects` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `machinery_log`
--
ALTER TABLE `machinery_log`
  ADD CONSTRAINT `fk_log_maquina` FOREIGN KEY (`maquina_id`) REFERENCES `machinery` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_log_operador` FOREIGN KEY (`operador_id`) REFERENCES `personnel` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_log_proyecto` FOREIGN KEY (`proyecto_id`) REFERENCES `projects` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `maintenance_logs`
--
ALTER TABLE `maintenance_logs`
  ADD CONSTRAINT `maintenance_logs_ibfk_1` FOREIGN KEY (`machinery_id`) REFERENCES `machinery` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `maintenance_logs_ibfk_2` FOREIGN KEY (`responsable_id`) REFERENCES `personnel` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `maintenance_parts`
--
ALTER TABLE `maintenance_parts`
  ADD CONSTRAINT `maintenance_parts_ibfk_1` FOREIGN KEY (`maintenance_log_id`) REFERENCES `maintenance_logs` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `payroll_details`
--
ALTER TABLE `payroll_details`
  ADD CONSTRAINT `payroll_details_ibfk_1` FOREIGN KEY (`payroll_id`) REFERENCES `payrolls` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `payroll_details_ibfk_2` FOREIGN KEY (`personnel_id`) REFERENCES `personnel` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `personnel`
--
ALTER TABLE `personnel`
  ADD CONSTRAINT `fk_personnel_proyecto` FOREIGN KEY (`proyecto_id`) REFERENCES `projects` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Filtros para la tabla `projects`
--
ALTER TABLE `projects`
  ADD CONSTRAINT `fk_projects_client` FOREIGN KEY (`cliente_id`) REFERENCES `clients` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Filtros para la tabla `project_incomes`
--
ALTER TABLE `project_incomes`
  ADD CONSTRAINT `fk_project_incomes_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `project_income_sources`
--
ALTER TABLE `project_income_sources`
  ADD CONSTRAINT `fk_income_sources_bank` FOREIGN KEY (`bank_account_id`) REFERENCES `bank_accounts` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_income_sources_income` FOREIGN KEY (`project_income_id`) REFERENCES `project_incomes` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `purchase_orders`
--
ALTER TABLE `purchase_orders`
  ADD CONSTRAINT `fk_po_proveedor` FOREIGN KEY (`proveedor_id`) REFERENCES `suppliers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_po_proyecto` FOREIGN KEY (`proyecto_id`) REFERENCES `projects` (`id`);

--
-- Filtros para la tabla `purchase_order_items`
--
ALTER TABLE `purchase_order_items`
  ADD CONSTRAINT `fk_poi_item` FOREIGN KEY (`item_id`) REFERENCES `inventory_items` (`id`),
  ADD CONSTRAINT `fk_poi_orden` FOREIGN KEY (`purchase_order_id`) REFERENCES `purchase_orders` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `vehicles`
--
ALTER TABLE `vehicles`
  ADD CONSTRAINT `fk_vehicle_pilot` FOREIGN KEY (`piloto_id`) REFERENCES `personnel` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Filtros para la tabla `vehicle_logs`
--
ALTER TABLE `vehicle_logs`
  ADD CONSTRAINT `fk_vehicle_log_pilot` FOREIGN KEY (`piloto_id`) REFERENCES `personnel` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_vehicle_log_vehicle` FOREIGN KEY (`vehiculo_id`) REFERENCES `vehicles` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
