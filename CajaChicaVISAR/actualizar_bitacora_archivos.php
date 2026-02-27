<?php
/**
 * Script para agregar soporte de archivos a la bitácora
 * Ejecutar UNA VEZ y luego eliminar
 */

require_once 'config.php';

try {
    $db = getDB();
    
    // Crear tabla de archivos de bitácora
    $db->exec("
        CREATE TABLE IF NOT EXISTS bitacora_archivos (
            id INT AUTO_INCREMENT PRIMARY KEY,
            bitacora_id INT NOT NULL,
            vale_id INT NOT NULL,
            nombre_original VARCHAR(255) NOT NULL,
            nombre_archivo VARCHAR(255) NOT NULL,
            tipo_archivo VARCHAR(100) NOT NULL,
            extension VARCHAR(10) NOT NULL,
            tamano INT NOT NULL,
            fecha_subida TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            subido_por VARCHAR(100) DEFAULT 'Sistema',
            FOREIGN KEY (bitacora_id) REFERENCES bitacora_vales(id) ON DELETE CASCADE,
            FOREIGN KEY (vale_id) REFERENCES vales(id) ON DELETE CASCADE,
            INDEX idx_bitacora (bitacora_id),
            INDEX idx_vale (vale_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    
    // Crear directorio de uploads si no existe
    $upload_dir = __DIR__ . '/uploads/bitacora';
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0755, true);
        
        // Crear .htaccess para seguridad
        file_put_contents($upload_dir . '/.htaccess', "Options -Indexes\n");
    }
    
    echo "<!DOCTYPE html>
    <html lang='es'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>Actualización Completada - MAGA</title>
        <style>
            body {
                font-family: 'Inter', Arial, sans-serif;
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
                margin-bottom: 20px;
            }
            .features {
                background: #f0fdf4;
                padding: 20px;
                border-radius: 8px;
                margin: 20px 0;
                text-align: left;
            }
            .features h3 {
                color: #065f46;
                margin-bottom: 10px;
            }
            .features ul {
                color: #047857;
                margin: 0;
                padding-left: 20px;
            }
            .features li {
                margin: 8px 0;
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
            .warning {
                background: #fef3c7;
                color: #92400e;
                padding: 12px;
                border-radius: 8px;
                margin-top: 20px;
                font-size: 14px;
            }
        </style>
    </head>
    <body>
        <div class='success-box'>
            <div class='success-icon'>📎</div>
            <h1>¡Actualización Exitosa!</h1>
            <p>Se ha agregado soporte para archivos adjuntos en la bitácora.</p>
            
            <div class='features'>
                <h3>Nuevas funcionalidades:</h3>
                <ul>
                    <li>📄 Subir archivos PDF</li>
                    <li>📝 Subir documentos Word (.doc, .docx)</li>
                    <li>📊 Subir hojas de Excel (.xls, .xlsx)</li>
                    <li>🖼️ Subir imágenes (JPG, PNG, GIF)</li>
                    <li>👁️ Vista previa en modal</li>
                    <li>⬇️ Descarga de archivos</li>
                </ul>
            </div>
            
            <a href='listar_vales.php' class='btn'>Ir al Sistema</a>
            
            <div class='warning'>
                ⚠️ <strong>Importante:</strong> Elimina este archivo (actualizar_bitacora_archivos.php) después de ejecutarlo.
            </div>
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
            p { color: #6b7280; word-break: break-word; }
        </style>
    </head>
    <body>
        <div class='error-box'>
            <div class='error-icon'>❌</div>
            <h1>Error en la Actualización</h1>
            <p>" . htmlspecialchars($e->getMessage()) . "</p>
        </div>
    </body>
    </html>";
}
?>