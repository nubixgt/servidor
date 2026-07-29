<?php
require 'Backend/src/Core/Database.php';
$db = new \Core\Database();
$conn = $db->getConnection();

if (!$conn) {
    echo "Connection failed\n";
    exit;
}

try {
    $nombre = "Test";
    $representante = "Test Rep";
    $telefono = "55555555";
    $dpi = "1234567891011"; // Like the screenshot
    $foto_ruta = "";
    $foto_representante_ruta = null;
    $usuario = "1234567891011";
    $password_hash = "hash";

    $query = "INSERT INTO equipos (nombre, representante, telefono, dpi, foto_ruta, foto_representante_ruta, usuario, password_hash) VALUES (:nombre, :representante, :telefono, :dpi, :foto_ruta, :foto_representante_ruta, :usuario, :password_hash)";
    $stmt = $conn->prepare($query);

    $stmt->bindParam(":nombre", $nombre);
    $stmt->bindParam(":representante", $representante);
    $stmt->bindParam(":telefono", $telefono);
    $stmt->bindParam(":dpi", $dpi);
    $stmt->bindParam(":foto_ruta", $foto_ruta);
    $stmt->bindParam(":foto_representante_ruta", $foto_representante_ruta);
    $stmt->bindParam(":usuario", $usuario);
    $stmt->bindParam(":password_hash", $password_hash);

    $stmt->execute();
    echo "Inserted id: " . $conn->lastInsertId() . "\n";
} catch (PDOException $e) {
    echo "PDO Error: " . $e->getMessage() . "\n";
}
