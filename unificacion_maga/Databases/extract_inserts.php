<?php
$file = file_get_contents('c:\\xampp\\htdocs\\unificacion_maga\\Databases\\dump-EjecucionPresupuestaria-202605251318.sql');
preg_match_all("/INSERT INTO `([^`]+)`/i", $file, $matches);
$tables = array_unique($matches[1]);
foreach($tables as $table) {
    echo "Found INSERT for table: $table\n";
}
