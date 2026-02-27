<?php
require_once 'config.php';

try {
    $db = getDB();
    
    // Agregar columna estado si no existe
    $db->exec("ALTER TABLE vales ADD COLUMN estado VARCHAR(20) DEFAULT 'PENDIENTE'");
    
    // Actualizar vales existentes que no tengan estado
    $db->exec("UPDATE vales SET estado = 'PENDIENTE' WHERE estado IS NULL OR estado = ''");
    
    echo "<!DOCTYPE html>
    <html lang='es'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>Tabla Modificada - MAGA</title>
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
            <div class='success-icon'>✅</div>
            <h1>¡Tabla Modificada Exitosamente!</h1>
            <p>Se agregó la columna 'estado' a la tabla de vales.<br>Todos los vales existentes se marcaron como PENDIENTE.</p>
            <a href='listar_vales.php' class='btn'>Ir al Listado de Vales</a>
        </div>
    </body>
    </html>";
    
} catch(Exception $e) {
    echo "<!DOCTYPE html>
    <html lang='es'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>Error - MAGA</title>
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
            .error-box {
                background: white;
                padding: 40px;
                border-radius: 16px;
                box-shadow: 0 10px 40px rgba(0,0,0,0.2);
                text-align: center;
                max-width: 500px;
            }
            .error-icon {
                font-size: 64px;
                margin-bottom: 20px;
            }
            h1 {
                color: #ef4444;
                margin-bottom: 10px;
            }
            p {
                color: #6b7280;
                margin-bottom: 30px;
            }
            .btn {
                background: linear-gradient(135deg, #95a5a6 0%, #7f8c8d 100%);
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
                box-shadow: 0 6px 20px rgba(149, 165, 166, 0.4);
            }
        </style>
    </head>
    <body>
        <div class='error-box'>
            <div class='error-icon'>❌</div>
            <h1>Error al Modificar la Tabla</h1>
            <p>" . htmlspecialchars($e->getMessage()) . "</p>
            <a href='index.php' class='btn'>Volver al Inicio</a>
        </div>
    </body>
    </html>";
}
?>