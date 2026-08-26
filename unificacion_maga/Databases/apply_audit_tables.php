<?php
try {
    $db = new PDO('mysql:host=localhost;dbname=u991565456_maga_un', 'u991565456_maga_un', 'NR4bWu~u7B&o');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $sql = file_get_contents('add_audit_notif_tables.sql');
    $db->exec($sql);
    echo "Tablas creadas exitosamente.\n";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
