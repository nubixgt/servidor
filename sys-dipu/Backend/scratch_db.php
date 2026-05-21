<?php
try {
    $isLocal = (str_contains(__DIR__, 'di3go') || str_contains(__DIR__, 'OneDrive'));
    $host = 'localhost';
    $dbname = 'visionwe_SysDipu';
    $username = $isLocal ? 'root' : 'visionwe';
    $password = $isLocal ? '' : 'Guate25#';
    
    $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";
    $db = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false
    ]);
    
    echo "CONNECTED SUCCESSFUL\n";
    
    // Add column if not exists
    $db->exec("ALTER TABLE fiscalizacion_personal ADD COLUMN titulo_puesto VARCHAR(255) NULL AFTER tipo_puesto");
    echo "Column titulo_puesto added successfully!\n";
    
    // DESCRIBE fiscalizacion_personal
    $stmt = $db->query("DESCRIBE fiscalizacion_personal");
    $columns = $stmt->fetchAll();
    echo "COLUMNS IN fiscalizacion_personal:\n";
    foreach ($columns as $col) {
        echo " - " . $col['Field'] . " (" . $col['Type'] . ")\n";
    }
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}


