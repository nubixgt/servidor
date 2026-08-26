<?php
require_once __DIR__ . '/../Backend/src/Utils/Database.php';

$db = \App\Utils\Database::getInstance()->getConnection();

$sql = "SELECT c.tipo, COUNT(*) as qty, SUM(e.vigente) as total_vigente FROM presupuesto_ejecucion e 
        JOIN presupuesto_categorias c ON e.categoria_id = c.id 
        WHERE e.ejercicio_fiscal = 2026
        GROUP BY c.tipo";
try {
    $stmt = $db->query($sql);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Summary for 2026:\n";
    foreach ($rows as $row) {
        echo "Tipo: " . $row['tipo'] . " | Qty: " . $row['qty'] . " | Total Vigente: " . $row['total_vigente'] . "\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
