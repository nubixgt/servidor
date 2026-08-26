<?php
// backend_movil/scripts/hash_passwords.php
// Script para hashear las contraseñas existentes en la tabla usuarios
// EJECUTAR SOLO UNA VEZ

require_once __DIR__ . '/../config/Database.php';

echo "===========================================\n";
echo "Script de Migración de Contraseñas\n";
echo "===========================================\n\n";

try {
    // Conectar a la base de datos
    $database = new Database();
    $conn = $database->getConnection();

    echo "✓ Conexión a base de datos exitosa\n\n";

    // Obtener todos los usuarios
    $query = "SELECT id, Usuario, Contrasena FROM usuarios";
    $stmt = $conn->prepare($query);
    $stmt->execute();

    $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $total = count($usuarios);

    echo "Usuarios encontrados: {$total}\n\n";

    if ($total === 0) {
        echo "No hay usuarios para procesar.\n";
        exit();
    }

    // Confirmar antes de proceder
    echo "⚠️  ADVERTENCIA: Este script hasheará todas las contraseñas.\n";
    echo "¿Deseas continuar? (escribe 'SI' para confirmar): ";

    $handle = fopen("php://stdin", "r");
    $line = fgets($handle);
    $confirmacion = trim($line);
    fclose($handle);

    if ($confirmacion !== 'SI') {
        echo "\nOperación cancelada.\n";
        exit();
    }

    echo "\nIniciando migración...\n\n";

    $actualizados = 0;
    $errores = 0;

    // Preparar query de actualización
    $updateQuery = "UPDATE usuarios SET Contrasena = :hash WHERE id = :id";
    $updateStmt = $conn->prepare($updateQuery);

    foreach ($usuarios as $usuario) {
        $id = $usuario['id'];
        $nombreUsuario = $usuario['Usuario'];
        $contrasenaActual = $usuario['Contrasena'];

        // Verificar si ya está hasheada (las contraseñas hasheadas con bcrypt empiezan con $2y$)
        if (substr($contrasenaActual, 0, 4) === '$2y$') {
            echo "  ⊙ Usuario '{$nombreUsuario}' (ID: {$id}) - Ya tiene contraseña hasheada\n";
            continue;
        }

        try {
            // Hashear la contraseña
            $hash = password_hash($contrasenaActual, PASSWORD_BCRYPT, ['cost' => 12]);

            // Actualizar en la base de datos
            $updateStmt->bindParam(':hash', $hash);
            $updateStmt->bindParam(':id', $id);
            $updateStmt->execute();

            echo "  ✓ Usuario '{$nombreUsuario}' (ID: {$id}) - Contraseña hasheada exitosamente\n";
            $actualizados++;

        } catch (Exception $e) {
            echo "  ✗ Usuario '{$nombreUsuario}' (ID: {$id}) - Error: " . $e->getMessage() . "\n";
            $errores++;
        }
    }

    echo "\n===========================================\n";
    echo "Resumen de Migración\n";
    echo "===========================================\n";
    echo "Total de usuarios: {$total}\n";
    echo "Actualizados: {$actualizados}\n";
    echo "Errores: {$errores}\n";
    echo "Ya hasheados: " . ($total - $actualizados - $errores) . "\n";
    echo "===========================================\n\n";

    if ($actualizados > 0) {
        echo "✓ Migración completada exitosamente!\n";
        echo "\n⚠️  IMPORTANTE: Guarda las contraseñas originales en un lugar seguro\n";
        echo "antes de cerrar este script, ya que no podrás recuperarlas.\n\n";
    }

} catch (Exception $e) {
    echo "\n✗ Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>