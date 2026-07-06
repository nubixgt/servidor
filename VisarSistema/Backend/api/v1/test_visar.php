<?php
require_once 'Backend/autoload.php';
use App\Utils\Database;

try {
    $db = Database::getInstance()->getConnection();
    
    $stmtExp = $db->query("SELECT COUNT(*) as total, SUM(valor_fob) as fob FROM exportaciones");
    $rowExp = $stmtExp->fetch(\PDO::FETCH_ASSOC);
    print_r($rowExp);

    $stmtImp = $db->query("SELECT COUNT(*) as total, SUM(valor_dolares) as cif FROM importaciones");
    $rowImp = $stmtImp->fetch(\PDO::FETCH_ASSOC);
    print_r($rowImp);
    
} catch (\Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
