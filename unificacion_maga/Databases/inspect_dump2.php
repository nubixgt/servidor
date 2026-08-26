<?php
$file = file_get_contents('c:\\xampp\\htdocs\\unificacion_maga\\Databases\\dump-EjecucionPresupuestaria-202605251318.sql');

preg_match_all('/CREATE TABLE `(.*?)` \((.*?)\) ENGINE=/s', $file, $matches);
foreach ($matches[1] as $idx => $table) {
    echo "TABLE: $table\n";
    echo $matches[2][$idx] . "\n\n";
}
