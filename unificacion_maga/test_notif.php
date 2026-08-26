<?php
require_once __DIR__ . '/Backend/autoload.php';
$db = \App\Utils\Database::getInstance()->getConnection();

try {
    // Check maga_usuarios
    $stmt = $db->query("SELECT id, activo FROM maga_usuarios LIMIT 1");
    if ($stmt) {
        echo "maga_usuarios exists.\n";
    } else {
        echo "maga_usuarios error: " . print_r($db->errorInfo(), true) . "\n";
    }

    // Check maga_notificaciones
    $stmt = $db->query("SELECT * FROM maga_notificaciones LIMIT 1");
    if ($stmt) {
        echo "maga_notificaciones exists.\n";
    } else {
        echo "maga_notificaciones error: " . print_r($db->errorInfo(), true) . "\n";
    }

    // Try inserting global
    $repo = new \App\Repositories\Notifications\NotificationRepository();
    $result = $repo->createGlobal('Test Title', 'Test Message', 'info');
    echo "Insert global result: " . ($result ? 'success' : 'fail') . "\n";

} catch (Exception $e) {
    echo "Exception: " . $e->getMessage() . "\n";
}
