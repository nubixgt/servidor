<?php
/**
 * SCRIPT PARA IMPORTAR vider_maga.sql A LA BASE DE DATOS DE HOSTINGER
 * 
 * Instrucciones:
 * 1. Sube este archivo (importar_hostinger.php) a la raíz de tu Hostinger (public_html).
 * 2. Asegúrate de que la carpeta Databases/ y el archivo vider_maga.sql también estén subidos.
 * 3. Visita en tu navegador: https://tusitio.com/importar_hostinger.php
 * 4. Una vez que diga "¡Importación exitosa!", ELIMINA este archivo por seguridad.
 */

$config = require __DIR__ . '/Backend/config/database.php';
$dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}";

echo "<h2>Importador de Base de Datos - Sistema MAGA</h2>";
echo "Conectando a la base de datos: " . $config['dbname'] . " en " . $config['host'] . "...<br>";

try {
    $db = new PDO($dsn, $config['username'], $config['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    
    echo "¡Conexión exitosa!<br><br>";
    
    $sqlFile = __DIR__ . '/Databases/vider_maga.sql';
    if (!file_exists($sqlFile)) {
        die("<strong style='color:red;'>Error:</strong> Archivo SQL no encontrado en la ruta esperada: $sqlFile<br>");
    }

    echo "Leyendo archivo SQL...<br>";
    $sql = file_get_contents($sqlFile);
    
    echo "Ejecutando importación (esto puede tomar unos segundos)...<br>";
    $db->setAttribute(PDO::ATTR_EMULATE_PREPARES, 1);
    
    $result = $db->exec($sql);
    
    if ($result !== false) {
        echo "<h3 style='color:green;'>¡Importación exitosa a la base de datos de Hostinger!</h3>";
        echo "<p>Las tablas de VIDER (vider_dependencias, vider_catalogos, vider_ejecucion, vider_tobanik) han sido importadas correctamente.</p>";
        echo "<strong style='color:red;'>IMPORTANTE: Por razones de seguridad, elimina este archivo (importar_hostinger.php) de tu servidor inmediatamente.</strong>";
    } else {
        echo "<h3 style='color:red;'>Error en la importación</h3>";
        echo "<pre>" . print_r($db->errorInfo(), true) . "</pre>";
    }
} catch (Exception $e) {
    echo "<h3 style='color:red;'>Excepción durante la importación:</h3>";
    echo "<p>" . $e->getMessage() . "</p>";
}
