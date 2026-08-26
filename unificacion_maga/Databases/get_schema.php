<?php
require_once __DIR__ . '/../Backend/src/Utils/Database.php';

$db = \App\Utils\Database::getInstance()->getConnection();

$tables = ['presupuesto_categorias', 'presupuesto_ejecucion', 'presupuesto_bitacora'];

foreach ($tables as $table) {
    try {
        $stmt = $db->query("SHOW CREATE TABLE $table");
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        echo "=====================================\n";
        echo "Table: $table\n";
        echo $row['Create Table'] . "\n\n";
    } catch (\Exception $e) {
        echo "Table $table not found.\n";
    }
}
