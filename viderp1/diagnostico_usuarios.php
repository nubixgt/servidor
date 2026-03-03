<?php
/**
 * Diagnóstico y Reparación de Tabla Usuarios - VIDER
 * ELIMINAR DESPUÉS DE USAR
 */

require_once 'includes/config.php';

echo "<html><head><title>Diagnóstico Usuarios - VIDER</title>";
echo "<style>
    body { font-family: Arial, sans-serif; padding: 20px; background: #1a1a2e; color: #eee; }
    h1, h2 { color: #4ade80; }
    .success { color: #4ade80; }
    .error { color: #f87171; }
    .warning { color: #fbbf24; }
    .info { color: #60a5fa; }
    pre { background: #0d0d1a; padding: 15px; border-radius: 8px; overflow-x: auto; }
    table { border-collapse: collapse; width: 100%; margin: 10px 0; }
    th, td { border: 1px solid #333; padding: 8px; text-align: left; }
    th { background: #2d2d4a; }
    .btn { display: inline-block; padding: 10px 20px; background: #4ade80; color: #000; text-decoration: none; border-radius: 8px; margin: 5px; font-weight: bold; }
    .btn:hover { background: #22c55e; }
    .btn-danger { background: #f87171; }
    .btn-danger:hover { background: #ef4444; }
</style></head><body>";

echo "<h1>🔧 Diagnóstico de Tabla Usuarios - VIDER</h1>";

try {
    $db = Database::getInstance();
    echo "<p class='success'>✅ Conexión a base de datos exitosa</p>";

    // 1. Verificar si existe la tabla
    echo "<h2>1. Verificar existencia de tabla</h2>";
    $tables = $db->fetchAll("SHOW TABLES LIKE 'usuarios'");
    
    if (empty($tables)) {
        echo "<p class='error'>❌ La tabla 'usuarios' NO existe</p>";
        echo "<p class='warning'>⚠️ Creando tabla usuarios...</p>";
        
        $createSql = "CREATE TABLE usuarios (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            nombre_completo VARCHAR(150) NULL,
            email VARCHAR(150) NULL,
            rol ENUM('admin', 'tecnico') DEFAULT 'tecnico',
            activo TINYINT(1) DEFAULT 1,
            ultimo_acceso DATETIME NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $db->query($createSql);
        echo "<p class='success'>✅ Tabla usuarios creada correctamente</p>";
    } else {
        echo "<p class='success'>✅ La tabla 'usuarios' existe</p>";
    }

    // 2. Verificar estructura de la tabla
    echo "<h2>2. Estructura de la tabla</h2>";
    $columns = $db->fetchAll("DESCRIBE usuarios");
    
    echo "<table><tr><th>Campo</th><th>Tipo</th><th>Nulo</th><th>Key</th><th>Default</th></tr>";
    $columnNames = [];
    foreach ($columns as $col) {
        $columnNames[] = $col['Field'];
        echo "<tr><td>{$col['Field']}</td><td>{$col['Type']}</td><td>{$col['Null']}</td><td>{$col['Key']}</td><td>{$col['Default']}</td></tr>";
    }
    echo "</table>";

    // 3. Verificar columnas necesarias
    echo "<h2>3. Verificar columnas necesarias</h2>";
    $requiredColumns = ['id', 'username', 'password', 'nombre_completo', 'email', 'rol', 'activo', 'created_at'];
    $missingColumns = [];
    
    foreach ($requiredColumns as $col) {
        if (in_array($col, $columnNames)) {
            echo "<p class='success'>✅ Columna '$col' existe</p>";
        } else {
            echo "<p class='error'>❌ Columna '$col' NO existe</p>";
            $missingColumns[] = $col;
        }
    }

    // 4. Agregar columnas faltantes
    if (!empty($missingColumns)) {
        echo "<h2>4. Agregando columnas faltantes</h2>";
        
        foreach ($missingColumns as $col) {
            $alterSql = "";
            switch ($col) {
                case 'nombre_completo':
                    $alterSql = "ALTER TABLE usuarios ADD COLUMN nombre_completo VARCHAR(150) NULL";
                    break;
                case 'email':
                    $alterSql = "ALTER TABLE usuarios ADD COLUMN email VARCHAR(150) NULL";
                    break;
                case 'rol':
                    $alterSql = "ALTER TABLE usuarios ADD COLUMN rol ENUM('admin', 'tecnico') DEFAULT 'tecnico'";
                    break;
                case 'activo':
                    $alterSql = "ALTER TABLE usuarios ADD COLUMN activo TINYINT(1) DEFAULT 1";
                    break;
                case 'created_at':
                    $alterSql = "ALTER TABLE usuarios ADD COLUMN created_at DATETIME DEFAULT CURRENT_TIMESTAMP";
                    break;
            }
            
            if ($alterSql) {
                try {
                    $db->query($alterSql);
                    echo "<p class='success'>✅ Columna '$col' agregada</p>";
                } catch (Exception $e) {
                    echo "<p class='error'>❌ Error agregando '$col': " . $e->getMessage() . "</p>";
                }
            }
        }
    }

    // 5. Verificar tipo de columna 'rol'
    echo "<h2>5. Verificar columna 'rol'</h2>";
    $rolColumn = $db->fetchOne("SHOW COLUMNS FROM usuarios WHERE Field = 'rol'");
    
    if ($rolColumn) {
        echo "<p class='info'>Tipo actual: {$rolColumn['Type']}</p>";
        
        // Verificar si incluye 'admin' y 'tecnico'
        if (strpos($rolColumn['Type'], 'admin') === false || strpos($rolColumn['Type'], 'tecnico') === false) {
            echo "<p class='warning'>⚠️ Actualizando tipo de columna rol...</p>";
            try {
                $db->query("ALTER TABLE usuarios MODIFY COLUMN rol VARCHAR(20) DEFAULT 'tecnico'");
                echo "<p class='success'>✅ Columna rol actualizada a VARCHAR(20)</p>";
            } catch (Exception $e) {
                echo "<p class='error'>❌ Error: " . $e->getMessage() . "</p>";
            }
        } else {
            echo "<p class='success'>✅ Columna rol tiene los valores correctos</p>";
        }
    }

    // 6. Mostrar usuarios existentes
    echo "<h2>6. Usuarios existentes</h2>";
    $usuarios = $db->fetchAll("SELECT id, username, nombre_completo, email, rol, activo FROM usuarios");
    
    if (empty($usuarios)) {
        echo "<p class='warning'>⚠️ No hay usuarios en la tabla</p>";
    } else {
        echo "<table><tr><th>ID</th><th>Username</th><th>Nombre</th><th>Email</th><th>Rol</th><th>Activo</th></tr>";
        foreach ($usuarios as $u) {
            echo "<tr><td>{$u['id']}</td><td>{$u['username']}</td><td>{$u['nombre_completo']}</td><td>{$u['email']}</td><td>{$u['rol']}</td><td>{$u['activo']}</td></tr>";
        }
        echo "</table>";
    }

    // 7. Crear usuario admin si no existe
    echo "<h2>7. Verificar usuario admin</h2>";
    $admin = $db->fetchOne("SELECT * FROM usuarios WHERE username = 'admin'");
    
    if (!$admin) {
        echo "<p class='warning'>⚠️ Usuario admin no existe. Creando...</p>";
        
        $hash = password_hash('admin123', PASSWORD_DEFAULT);
        $db->query(
            "INSERT INTO usuarios (username, password, nombre_completo, rol, activo, created_at) VALUES (:username, :password, :nombre, :rol, 1, NOW())",
            [
                ':username' => 'admin',
                ':password' => $hash,
                ':nombre' => 'Administrador VIDER',
                ':rol' => 'admin'
            ]
        );
        echo "<p class='success'>✅ Usuario admin creado</p>";
        echo "<p class='info'>Usuario: admin</p>";
        echo "<p class='info'>Contraseña: admin123</p>";
    } else {
        echo "<p class='success'>✅ Usuario admin existe (ID: {$admin['id']})</p>";
        
        // Actualizar contraseña si es necesario
        if (isset($_GET['reset_password'])) {
            $hash = password_hash('admin123', PASSWORD_DEFAULT);
            $db->query("UPDATE usuarios SET password = :password WHERE username = 'admin'", [':password' => $hash]);
            echo "<p class='success'>✅ Contraseña de admin restablecida a 'admin123'</p>";
        }
    }

    // 8. Probar inserción
    echo "<h2>8. Probar inserción de usuario</h2>";
    if (isset($_GET['test_insert'])) {
        $testUsername = 'test_user_' . time();
        $testHash = password_hash('test123', PASSWORD_DEFAULT);
        
        try {
            $db->query(
                "INSERT INTO usuarios (username, password, nombre_completo, email, rol, activo, created_at) VALUES (:username, :password, :nombre, :email, :rol, 1, NOW())",
                [
                    ':username' => $testUsername,
                    ':password' => $testHash,
                    ':nombre' => 'Usuario de Prueba',
                    ':email' => 'test@test.com',
                    ':rol' => 'tecnico'
                ]
            );
            echo "<p class='success'>✅ Inserción exitosa! Usuario: $testUsername</p>";
            
            // Eliminar usuario de prueba
            $db->query("DELETE FROM usuarios WHERE username = :username", [':username' => $testUsername]);
            echo "<p class='info'>Usuario de prueba eliminado</p>";
        } catch (Exception $e) {
            echo "<p class='error'>❌ Error en inserción: " . $e->getMessage() . "</p>";
        }
    } else {
        echo "<p><a href='?test_insert=1' class='btn'>Probar Inserción</a></p>";
    }

    echo "<hr>";
    echo "<h2>Acciones</h2>";
    echo "<p><a href='?reset_password=1' class='btn btn-danger'>Restablecer contraseña admin a 'admin123'</a></p>";
    echo "<p><a href='usuarios.php' class='btn'>Ir a Gestión de Usuarios</a></p>";
    echo "<p><a href='login.php' class='btn'>Ir a Login</a></p>";
    
    echo "<hr>";
    echo "<p class='warning'>⚠️ <strong>IMPORTANTE:</strong> Elimina este archivo (diagnostico_usuarios.php) después de usarlo por seguridad.</p>";

} catch (Exception $e) {
    echo "<p class='error'>❌ Error de conexión: " . $e->getMessage() . "</p>";
}

echo "</body></html>";
?>