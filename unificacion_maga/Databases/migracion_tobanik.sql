-- Migración de la tabla vieja 'tobanik' a la nueva estructura 'vider_tobanik'

-- 1. Limpiamos la tabla nueva para no mezclar los datos de prueba
TRUNCATE TABLE vider_tobanik;

-- 2. Insertamos los datos reales de la tabla vieja a la nueva.
-- Usamos COALESCE con un JOIN para obtener el nombre real del departamento a partir de departamento_id.
-- En caso de que no exista el ID, usamos la columna 'sede' directamente.
INSERT INTO vider_tobanik (
    id, 
    departamento, 
    nombre_cooperativa, 
    productores, 
    monto_colocado, 
    monto_otorgado, 
    fecha_registro, 
    creado_at
)
SELECT 
    t.id, 
    COALESCE(vd.nombre, t.sede) AS departamento,
    t.nombre_cooperativa, 
    t.cantidad_productores, 
    t.monto_colocado, 
    t.monto_otorgado, 
    DATE(t.created_at) AS fecha_registro, 
    t.created_at AS creado_at
FROM tobanik t
LEFT JOIN vider_departamentos vd ON t.departamento_id = vd.id;

-- 3. Renombramos la tabla vieja para que sirva de respaldo y deje de interferir
RENAME TABLE tobanik TO tobanik_old;
