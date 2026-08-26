<?php
$sql_file = __DIR__ . '/Databases/vider_maga.sql';

$content = file_get_contents($sql_file);

$pattern = '/CREATE TABLE `([^`]+)` \(/';
$replacement = "DROP TABLE IF EXISTS `$1`;\nCREATE TABLE `$1` (";

$new_content = preg_replace($pattern, $replacement, $content);

file_put_contents($sql_file, $new_content);

echo "Archivo SQL actualizado con DROP TABLE IF EXISTS.";
