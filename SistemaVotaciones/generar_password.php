<?php
/**
 * 🔐 GENERADOR DE CONTRASEÑAS HASH
 * Ejecutar: http://localhost/congreso/generar_password.php
 * 
 * Usa esta herramienta para generar hashes de contraseñas
 * que puedes usar directamente en la base de datos
 */

$passwordGenerado = '';
$hashGenerado = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    if (!empty($password)) {
        $passwordGenerado = $password;
        $hashGenerado = password_hash($password, PASSWORD_DEFAULT);
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generador de Contraseñas Hash</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Inter', sans-serif;
        }
        .container {
            max-width: 600px;
        }
        .card {
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }
        .card-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 20px 20px 0 0 !important;
            padding: 2rem;
        }
        .form-control {
            border-radius: 10px;
            border: 2px solid #e2e8f0;
            padding: 0.75rem 1rem;
        }
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 10px;
            padding: 0.75rem 2rem;
            font-weight: 600;
        }
        .result-box {
            background: #f8fafc;
            border-radius: 10px;
            padding: 1.5rem;
            margin-top: 1.5rem;
        }
        .hash-output {
            background: white;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            padding: 1rem;
            font-family: 'Courier New', monospace;
            word-break: break-all;
            font-size: 0.85rem;
        }
        .copy-btn {
            border-radius: 8px;
        }
        .info-box {
            background: #dbeafe;
            border-left: 4px solid #3b82f6;
            padding: 1rem;
            border-radius: 8px;
            margin-top: 1.5rem;
        }
        .warning-box {
            background: #fef3c7;
            border-left: 4px solid #f59e0b;
            padding: 1rem;
            border-radius: 8px;
            margin-top: 1rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="card-header text-center">
                <i class="bi bi-key-fill" style="font-size: 3rem;"></i>
                <h3 class="mt-3 mb-1">Generador de Contraseñas Hash</h3>
                <p class="mb-0 opacity-75">Sistema de Votaciones del Congreso</p>
            </div>
            <div class="card-body p-4">
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label fw-bold">
                            <i class="bi bi-lock-fill me-2"></i>Ingresa la contraseña:
                        </label>
                        <input type="text" class="form-control" name="password" 
                               placeholder="Ej: MiContraseñaSegura123" required autofocus>
                        <small class="text-muted">Mínimo recomendado: 8 caracteres con letras, números y símbolos</small>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-cpu me-2"></i>Generar Hash
                    </button>
                </form>

                <?php if ($hashGenerado): ?>
                    <div class="result-box">
                        <h5 class="mb-3">
                            <i class="bi bi-check-circle-fill text-success me-2"></i>Hash Generado
                        </h5>
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold">Contraseña original:</label>
                            <div class="hash-output bg-light">
                                <?php echo htmlspecialchars($passwordGenerado); ?>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold">Hash (copiar para la BD):</label>
                            <div class="hash-output" id="hashOutput">
                                <?php echo htmlspecialchars($hashGenerado); ?>
                            </div>
                        </div>
                        
                        <button class="btn btn-success w-100 copy-btn" onclick="copiarHash()">
                            <i class="bi bi-clipboard-check me-2"></i>Copiar Hash
                        </button>

                        <div class="info-box mt-3">
                            <h6 class="mb-2">
                                <i class="bi bi-info-circle-fill me-2"></i>¿Cómo usar este hash?
                            </h6>
                            <p class="mb-2">Puedes actualizar la contraseña en phpMyAdmin con este SQL:</p>
                            <div class="hash-output">
UPDATE usuarios <br>
SET password = '<?php echo htmlspecialchars($hashGenerado); ?>' <br>
WHERE username = 'nombre_usuario';
                            </div>
                        </div>

                        <div class="warning-box">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i>
                            <strong>Importante:</strong> Guarda la contraseña original en un lugar seguro. 
                            Una vez guardado el hash, no podrás recuperar la contraseña original.
                        </div>
                    </div>
                <?php endif; ?>

                <div class="info-box">
                    <h6 class="mb-2">
                        <i class="bi bi-shield-check me-2"></i>Seguridad
                    </h6>
                    <ul class="mb-0 small">
                        <li>Los hashes se generan usando <strong>bcrypt</strong></li>
                        <li>Cada hash es único, incluso para la misma contraseña</li>
                        <li>No es posible revertir un hash a la contraseña original</li>
                        <li>Este generador no guarda ninguna información</li>
                    </ul>
                </div>

                <div class="text-center mt-4">
                    <a href="login.php" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left me-2"></i>Volver al Login
                    </a>
                </div>
            </div>
        </div>

        <div class="text-center mt-3">
            <small class="text-white">
                <i class="bi bi-shield-lock-fill me-1"></i>
                Herramienta de administración - Solo para uso autorizado
            </small>
        </div>
    </div>

    <script>
        function copiarHash() {
            const hashText = document.getElementById('hashOutput').textContent.trim();
            
            navigator.clipboard.writeText(hashText).then(function() {
                const btn = document.querySelector('.copy-btn');
                const originalText = btn.innerHTML;
                
                btn.innerHTML = '<i class="bi bi-check2 me-2"></i>¡Copiado!';
                btn.classList.remove('btn-success');
                btn.classList.add('btn-info');
                
                setTimeout(function() {
                    btn.innerHTML = originalText;
                    btn.classList.remove('btn-info');
                    btn.classList.add('btn-success');
                }, 2000);
            }).catch(function(err) {
                alert('Error al copiar: ' + err);
            });
        }
    </script>
</body>
</html>