<?php
$content = file_get_contents('c:/xampp/htdocs/unificacion_maga/Databases/dump-gestionesmaga_db-202605281047.sql');
preg_match_all('/CREATE TABLE `([^`]+)`/', $content, $matches);
print_r($matches[1]);
