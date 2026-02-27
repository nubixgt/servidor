<?php
require_once 'config.php';

try {
    $db = getDB();
    
    // Crear tabla de bitácora
    $db->exec("
        CREATE TABLE IF NOT EXISTS bitacora_vales (
            id INT AUTO_INCREMENT PRIMARY KEY,
            vale_id INT NOT NULL,
            numero_vale VARCHAR(50),
            usuario VARCHAR(100) DEFAULT 'Sistema',
            accion VARCHAR(50) NOT NULL,
            estado_anterior VARCHAR(50),
            estado_nuevo VARCHAR(50),
            campo_modificado VARCHAR(100),
            valor_anterior TEXT,
            valor_nuevo TEXT,
            observacion TEXT,
            fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (vale_id) REFERENCES vales(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    
    // Registrar vales existentes en la bitácora
    $stmt = $db->query("SELECT id, numero_vale, estado, fecha_creacion FROM vales");
    $vales_existentes = $stmt->fetchAll();
    
    foreach ($vales_existentes as $vale) {
        $db->prepare("
            INSERT INTO bitacora_vales (vale_id, numero_vale, usuario, accion, estado_nuevo, observacion, fecha_registro)
            VALUES (?, ?, 'Sistema', 'CREADO', ?, 'Vale registrado en el sistema', ?)
        ")->execute([$vale['id'], $vale['numero_vale'], $vale['estado'], $vale['fecha_creacion']]);
    }
    
    echo "<!DOCTYPE html>
    <html lang='es'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>Bitácora Creada - MAGA</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                background: linear-gradient(135deg, #1e3a5f 0%, #0abde3 100%);
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                margin: 0;
                padding: 20px;
            }
            .success-box {
                background: white;
                padding: 40px;
                border-radius: 16px;
                box-shadow: 0 10px 40px rgba(0,0,0,0.2);
                text-align: center;
                max-width: 500px;
            }
            .success-icon {
                font-size: 64px;
                margin-bottom: 20px;
            }
            h1 {
                color: #10b981;
                margin-bottom: 10px;
            }
            p {
                color: #6b7280;
                margin-bottom: 30px;
            }
            .stats {
                background: #f0fdf4;
                padding: 15px;
                border-radius: 8px;
                margin: 20px 0;
                color: #065f46;
            }
            .btn {
                background: linear-gradient(135deg, #0abde3 0%, #48d1ff 100%);
                color: white;
                padding: 12px 30px;
                border-radius: 8px;
                text-decoration: none;
                font-weight: bold;
                display: inline-block;
                transition: all 0.3s ease;
            }
            .btn:hover {
                transform: translateY(-2px);
                box-shadow: 0 6px 20px rgba(10, 189, 227, 0.4);
            }
        </style>
    </head>
    <body>
        <div class='success-box'>
            <div class='success-icon'>📋</div>
            <h1>¡Bitácora Creada Exitosamente!</h1>
            <p>Se ha creado la tabla de bitácora y se han registrado todos los vales existentes.</p>
            <div class='stats'>
                <strong>Vales registrados en bitácora: " . count($vales_existentes) . "</strong>
            </div>
            <a href='listar_vales.php' class='btn'>Ir al Listado de Vales</a>
        </div>
    </body>
    </html>";
    
} catch(Exception $e) {
    echo "<!DOCTYPE html>
    <html lang='es'>
    <head>
        <meta charset='UTF-8'>
        <title>Error - MAGA</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                background: linear-gradient(135deg, #1e3a5f 0%, #0abde3 100%);
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 20px;
            }
            .error-box {
                background: white;
                padding: 40px;
                border-radius: 16px;
                box-shadow: 0 10px 40px rgba(0,0,0,0.2);
                text-align: center;
                max-width: 500px;
            }
            .error-icon { font-size: 64px; margin-bottom: 20px; }
            h1 { color: #ef4444; margin-bottom: 10px; }
            p { color: #6b7280; margin-bottom: 30px; word-break: break-word; }
        </style>
    </head>
    <body>
        <div class='error-box'>
            <div class='error-icon'>❌</div>
            <h1>Error al Crear Bitácora</h1>
            <p>" . htmlspecialchars($e->getMessage()) . "</p>
        </div>
    </body>
    </html>";
}
?>