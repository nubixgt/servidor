-- ============================================
-- INSTRUCCIONES PARA CREAR LAS TABLAS
-- ============================================
-- 
-- Ejecuta estos scripts SQL en tu base de datos MySQL
-- en el orden indicado:
--
-- 1. Primero ejecuta crear_tabla_nota_envio.sql
-- 2. Luego ejecuta crear_tabla_detalle_nota_envio.sql
--
-- ============================================

-- PASO 1: Crear tabla nota_envio
-- Ejecuta el contenido de: crear_tabla_nota_envio.sql

-- PASO 2: Crear tabla detalle_nota_envio  
-- Ejecuta el contenido de: crear_tabla_detalle_nota_envio.sql

-- ============================================
-- VERIFICACIÓN
-- ============================================
-- Después de ejecutar los scripts, verifica que las tablas se crearon correctamente:

-- Ver estructura de nota_envio
DESCRIBE nota_envio;

-- Ver estructura de detalle_nota_envio
DESCRIBE detalle_nota_envio;

-- Verificar que no hay datos
SELECT COUNT(*) as total_notas FROM nota_envio;
SELECT COUNT(*) as total_detalles FROM detalle_nota_envio;

-- ============================================
-- PRUEBA MANUAL (OPCIONAL)
-- ============================================
-- Puedes probar manualmente insertando una nota de prueba:

/*
INSERT INTO nota_envio (
    numero_nota, fecha, vendedor, cliente_id, cliente_nombre,
    nit, direccion, tipo_venta, dias_credito, subtotal,
    descuento_total, total, usuario_id
) VALUES (
    '00001', '2026-01-23', 'Felipe Machán', 1, 'Cliente de Prueba',
    'CF', 'Dirección de prueba', 'Contado', NULL, 1000.00,
    0.00, 1000.00, 1
);

-- Obtener el ID de la nota recién creada
SET @nota_id = LAST_INSERT_ID();

-- Insertar un detalle de prueba
INSERT INTO detalle_nota_envio (
    nota_envio_id, producto, presentacion, precio_unitario,
    cantidad, descuento, total
) VALUES (
    @nota_id, 'EM1', '20 Litros', 550.00,
    2, 100.00, 1000.00
);

-- Verificar los datos
SELECT * FROM nota_envio WHERE id = @nota_id;
SELECT * FROM detalle_nota_envio WHERE nota_envio_id = @nota_id;

-- Limpiar datos de prueba
DELETE FROM detalle_nota_envio WHERE nota_envio_id = @nota_id;
DELETE FROM nota_envio WHERE id = @nota_id;
*/
