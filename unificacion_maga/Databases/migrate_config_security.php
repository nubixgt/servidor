<?php
require_once __DIR__ . '/../Backend/autoload.php';

use App\Utils\Database;

try {
    $db = Database::getInstance()->getConnection();
    
    // Añadir columnas de intentos fallidos
    echo "Actualizando tabla maga_usuarios...\n";
    $db->exec("ALTER TABLE maga_usuarios ADD COLUMN IF NOT EXISTS intentos_fallidos INT DEFAULT 0;");
    $db->exec("ALTER TABLE maga_usuarios ADD COLUMN IF NOT EXISTS bloqueado_hasta DATETIME NULL;");

    // Crear tabla de settings
    echo "Creando tabla maga_settings...\n";
    $db->exec("CREATE TABLE IF NOT EXISTS maga_settings (
        `key` VARCHAR(50) PRIMARY KEY,
        `value` TEXT NOT NULL,
        `description` VARCHAR(255) NULL,
        `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    );");

    // Insertar default settings
    echo "Insertando valores por defecto...\n";
    $stmt = $db->prepare("INSERT IGNORE INTO maga_settings (`key`, `value`, `description`) VALUES 
        ('session_timeout', '480', 'Tiempo máximo de sesión en minutos'),
        ('maintenance_mode', 'false', 'Activar modo mantenimiento'),
        ('openweather_api_key', 'YOUR_API_KEY_HERE', 'Llave de API para OpenWeather')
    ");
    $stmt->execute();

    echo "Migración completada exitosamente.\n";
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
