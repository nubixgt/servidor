<?php
require_once __DIR__ . '/../Backend/autoload.php';
require_once __DIR__ . '/../Backend/src/Utils/Database.php';

$db = \App\Utils\Database::getInstance()->getConnection();

$sql = "
    SELECT e.*, c.nombre, c.codigo, c.tipo
    FROM presupuesto_ejecucion e
    JOIN presupuesto_categorias c ON e.categoria_id = c.id
    WHERE 1=1 AND c.tipo = 'UNIDAD_EJECUTORA' AND e.ejercicio_fiscal = 2026
    ORDER BY c.codigo ASC
";
$stmt = $db->prepare($sql);
$stmt->execute();
$items = $stmt->fetchAll(\PDO::FETCH_ASSOC);

function mapItem($data) {
    return [
        'id' => $data['id'],
        'name' => ($data['codigo'] ?? '') . ' "' . ($data['nombre'] ?? '') . '"'
    ];
}

$mapped = array_map('mapItem', $items);
echo json_encode($mapped);
