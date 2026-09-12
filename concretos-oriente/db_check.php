<?php
$pdo = new PDO('mysql:host=localhost;dbname=concretos_oriente', 'root', '');
$stmt = $pdo->query('SHOW CREATE TABLE vehicle_logs');
print_r($stmt->fetch(PDO::FETCH_ASSOC));
