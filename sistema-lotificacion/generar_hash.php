<?php
/**
 * Generador de Hash para Contraseñas
 * 
 * Este archivo es solo para uso administrativo
 * Elimínalo después de crear tus usuarios
 */

// Cambia esta contraseña por la que desees
$password = "recepcion123";

// Generar el hash
$hash = password_hash($password, PASSWORD_DEFAULT);

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generador de Hash</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        .container {
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            padding: 40px;
            max-width: 600px;
            width: 100%;
        }
        h1 {
            color: #333;
            margin-bottom: 10px;
        }
        .warning {
            background: #fff3cd;
            border: 1px solid #ffc107;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
            color: #856404;
        }
        .result {
            background: #f8f9fa;
            border: 2px solid #e0e0e0;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
            word-break: break-all;
        }
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }
        code {
            background: #333;
            color: #0f0;
            padding: 2px 6px;
            border-radius: 4px;
            font-family: monospace;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔐 Generador de Hash para Contraseñas</h1>
        
        <div class="warning">
            <strong>⚠️ ADVERTENCIA DE SEGURIDAD:</strong><br>
            Elimina este archivo después de crear tus usuarios. No debe estar accesible en producción.
        </div>

        <div>
            <label>Contraseña original:</label>
            <div class="result">
                <code><?php echo htmlspecialchars($password); ?></code>
            </div>
        </div>

        <div>
            <label>Hash generado (para insertar en la BD):</label>
            <div class="result">
                <code><?php echo $hash; ?></code>
            </div>
        </div>

        <div>
            <label>Query SQL de ejemplo:</label>
            <div class="result">
                <code style="white-space: pre-wrap;">INSERT INTO usuarios (usuario, password, nombre_completo) 
VALUES ('mi_usuario', '<?php echo $hash; ?>', 'Mi Nombre Completo');</code>
            </div>
        </div>

        <div class="warning">
            <strong>Instrucciones:</strong>
            <ol>
                <li>Cambia la variable <code>$password</code> en este archivo</li>
                <li>Recarga la página</li>
                <li>Copia el hash generado</li>
                <li>Úsalo en tu query SQL</li>
                <li><strong>Elimina este archivo después</strong></li>
            </ol>
        </div>
    </div>
</body>
</html>