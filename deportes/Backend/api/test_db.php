<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: text/html; charset=UTF-8");

echo "<h1>Prueba de conexión PDO</h1>";

// Intenta obtener host por parámetro GET o usa localhost
$host = isset($_GET['host']) ? $_GET['host'] : "localhost";
$db_name = "visionwe_deportes";
$username = "visionwe_deportes";
$password = "deportes2026";

echo "<ul>";
echo "<li><strong>Host:</strong> $host</li>";
echo "<li><strong>DB:</strong> $db_name</li>";
echo "<li><strong>Usuario:</strong> $username</li>";
echo "<li><strong>Password:</strong> $password</li>";
echo "</ul>";

try {
    $conn = new PDO("mysql:host=" . $host . ";dbname=" . $db_name, $username, $password);
    $conn->exec("set names utf8");
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "<h2 style='color: green;'>¡Conexión exitosa a la base de datos!</h2>";
    
    // Probar tabla equipos
    $stmt = $conn->query("SHOW TABLES LIKE 'equipos'");
    if($stmt->rowCount() > 0) {
        echo "<p style='color: green;'>La tabla 'equipos' existe.</p>";
    } else {
        echo "<p style='color: red;'>La tabla 'equipos' NO existe. Debes importar el schema.</p>";
    }

} catch(PDOException $exception) {
    echo "<h2 style='color: red;'>Error de conexión:</h2>";
    echo "<pre>" . $exception->getMessage() . "</pre>";
    echo "<p><strong>Sugerencias:</strong></p>";
    echo "<ul>";
    echo "<li>Verifica que el usuario haya sido añadido a la base de datos en cPanel (no solo creado, sino ASIGNADO a la base de datos).</li>";
    echo "<li>Verifica que la IP del servidor web tenga permiso para acceder a la base de datos.</li>";
    echo "<li>Prueba usar <code>127.0.0.1</code> en lugar de <code>localhost</code>. Puedes hacerlo abriendo: <code>test_db.php?host=127.0.0.1</code></li>";
    echo "</ul>";
}
