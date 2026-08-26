<?php
$file = file_get_contents('c:\\xampp\\htdocs\\unificacion_maga\\Databases\\dump-EjecucionPresupuestaria-202605251318.sql');
$tables = [
    'ministerios', 'unidades_ejecutoras', 'grupos_gasto', 'fuentes_financiamiento',
    'ejecucion_ministerios', 'ejecucion_principal', 'ejecucion_detalle'
];
foreach($tables as $t) {
    if (preg_match("/INSERT INTO `$t` VALUES \((.*?)\);/i", $file, $m)) {
        echo "Table $t:\n" . substr($m[1], 0, 150) . "...\n\n";
    }
}
