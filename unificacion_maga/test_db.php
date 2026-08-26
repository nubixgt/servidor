<?php
require 'Backend/autoload.php';
$db = \App\Utils\Database::getInstance()->getConnection();
try {
    $stmt = $db->query('SELECT r.*, u.NombreCompleto as usuario_nombre FROM clima_registros r LEFT JOIN clima_usuarios u ON r.id_usuario = u.id');
    print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
} catch (Exception $e) {
    echo $e->getMessage();
}
