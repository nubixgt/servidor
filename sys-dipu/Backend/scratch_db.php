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
    
    $sql = "CREATE TABLE IF NOT EXISTS fiscalizacion_ministros (
        id INT AUTO_INCREMENT PRIMARY KEY,
        ministerio_id INT NOT NULL UNIQUE,
        nombre_ministro VARCHAR(255) DEFAULT 'Pendiente',
        foto_url VARCHAR(255) NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    
    $db->exec($sql);
    echo "Table fiscalizacion_ministros created successfully!\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
