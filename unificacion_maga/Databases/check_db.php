<?php
require_once 'Backend/src/Utils/Database.php';
$db = \App\Utils\Database::getInstance()->getConnection();

$sql = "SELECT e.*, c.codigo, c.nombre, c.tipo FROM presupuesto_ejecucion e 
        JOIN presupuesto_categorias c ON e.categoria_id = c.id 
        WHERE e.ejercicio_fiscal = 2026";
$stmt = $db->query($sql);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Total records in DB for 2026: " . count($rows) . "\n";
foreach ($rows as $row) {
    echo "ID: " . $row['id'] . " | Tipo: " . $row['tipo'] . " | Codigo: " . $row['codigo'] . " | Nombre: " . $row['nombre'] . "\n";
    echo "  -> Asignado: " . $row['asignado'] . " | Modificado: " . $row['modificado'] . " | Vigente: " . $row['vigente'] . " | Devengado: " . $row['devengado'] . " | Saldo: " . $row['saldo'] . "\n\n";
}
