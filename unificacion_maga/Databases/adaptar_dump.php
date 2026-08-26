<?php
$content = file_get_contents('c:/xampp/htdocs/unificacion_maga/Databases/dump-gestionesmaga_db-202605281047.sql');

// Rename tables
$content = str_replace('`exportaciones`', '`visar_exportaciones`', $content);
$content = str_replace('`importaciones`', '`visar_importaciones`', $content);
$content = str_replace('`licencias_fitosanitarias`', '`visar_licencias_fitosanitarias`', $content);
$content = str_replace('`licencias_transporte`', '`visar_licencias_transporte`', $content);
$content = str_replace('`lv_siia`', '`visar_libre_venta`', $content);

// Remove problematic SET statements (GTID and BINLOG)
$content = preg_replace('/SET @MYSQLDUMP_TEMP_LOG_BIN.*?;/i', '', $content);
$content = preg_replace('/SET @@SESSION\.SQL_LOG_BIN.*?;/i', '', $content);
$content = preg_replace('/SET @@GLOBAL\.GTID_PURGED.*?;/i', '', $content);

file_put_contents('c:/xampp/htdocs/unificacion_maga/Databases/datos_visar_adaptados.sql', $content);
echo "Archivo adaptado generado con éxito, y se han removido los bloqueos de BINLOG finales.";
