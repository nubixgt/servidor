<?php

$sql = file_get_contents('c:\\xampp\\htdocs\\unificacion_maga\\Databases\\dump-EjecucionPresupuestaria-202605251318.sql');

function parseInserts($sql, $tableName) {
    if (preg_match("/INSERT INTO `$tableName` VALUES \((.*?)\);/i", $sql, $match)) {
        // Splitting by ),( 
        $values = $match[1];
        // replace null with empty string to avoid issues, or handle it
        $values = str_replace('NULL', "''", $values);
        $rows = explode('),(', $values);
        $result = [];
        foreach($rows as $row) {
            $row = trim($row, "()");
            $fields = str_getcsv($row, ",", "'");
            $result[] = $fields;
        }
        return $result;
    }
    return [];
}

$ministerios = parseInserts($sql, 'ministerios');
$unidades = parseInserts($sql, 'unidades_ejecutoras');
$grupos = parseInserts($sql, 'grupos_gasto');
$fuentes = parseInserts($sql, 'fuentes_financiamiento');

$ej_ministerios = parseInserts($sql, 'ejecucion_ministerios');
$ej_principal = parseInserts($sql, 'ejecucion_principal');
$ej_detalle = parseInserts($sql, 'ejecucion_detalle');

$out = "-- Migracion de Presupuesto\n\n";

$out .= "DELETE FROM presupuesto_ejecucion;\n";
$out .= "DELETE FROM presupuesto_categorias;\n\n";

// Map IDs to avoid collisions
// ministerios: 1000+, unidades: 2000+, grupos: 3000+, fuentes: 4000+
foreach($ministerios as $m) {
    $id = intval($m[0]) + 1000;
    $codigo = addslashes($m[2]);
    $nombre = addslashes($m[1]);
    $out .= "INSERT INTO presupuesto_categorias (id, tipo, codigo, nombre) VALUES ($id, 'MINISTERIO', '$codigo', '$nombre');\n";
}

foreach($unidades as $u) {
    $id = intval($u[0]) + 2000;
    $codigo = addslashes($u[1]);
    $nombre = addslashes($u[2]);
    $out .= "INSERT INTO presupuesto_categorias (id, tipo, codigo, nombre) VALUES ($id, 'UNIDAD_EJECUTORA', '$codigo', '$nombre');\n";
}

foreach($grupos as $g) {
    $id = intval($g[0]) + 3000;
    $codigo = addslashes($g[1]);
    $nombre = addslashes($g[2]);
    $out .= "INSERT INTO presupuesto_categorias (id, tipo, codigo, nombre) VALUES ($id, 'GRUPO_GASTO', '$codigo', '$nombre');\n";
}

foreach($fuentes as $f) {
    $id = intval($f[0]) + 4000;
    $codigo = addslashes($f[1]);
    $nombre = addslashes($f[2]);
    $out .= "INSERT INTO presupuesto_categorias (id, tipo, codigo, nombre) VALUES ($id, 'FUENTE_FINANCIAMIENTO', '$codigo', '$nombre');\n";
}

$out .= "\n";

// Ejecucion Ministerios
foreach($ej_ministerios as $em) {
    $cat_id = intval($em[1]) + 1000;
    $anio = intval($em[2]);
    $asignado = floatval($em[3]);
    $modificado = floatval($em[4]);
    $vigente = floatval($em[5]);
    $devengado = floatval($em[6]);
    $saldo = floatval($em[7]);
    $pct = floatval($em[8]);
    
    $out .= "INSERT INTO presupuesto_ejecucion (categoria_id, ejercicio_fiscal, asignado, modificado, vigente, devengado, saldo, pct_ejec) VALUES ($cat_id, $anio, $asignado, $modificado, $vigente, $devengado, $saldo, $pct);\n";
}

// Ejecucion Unidades Ejecutoras (from ejecucion_principal where grupo_gasto and fuente are empty)
foreach($ej_principal as $ep) {
    // fields: 0=id, 1=unidad, 2=programa, 3=grupo, 4=fuente, 5=tipo, 6=anio, 7=asignado...
    $isNull = function($val) { return $val === "''" || $val === ""; };
    if ($isNull($ep[2]) && $isNull($ep[3]) && $isNull($ep[4])) {
        $cat_id = intval($ep[1]) + 2000;
        $anio = intval($ep[6]);
        $asignado = floatval($ep[7]);
        $modificado = floatval($ep[8]);
        $vigente = floatval($ep[9]);
        $devengado = floatval($ep[10]);
        $saldo = floatval($ep[11]);
        $pct = floatval($ep[12]);
        $out .= "INSERT INTO presupuesto_ejecucion (categoria_id, ejercicio_fiscal, asignado, modificado, vigente, devengado, saldo, pct_ejec) VALUES ($cat_id, $anio, $asignado, $modificado, $vigente, $devengado, $saldo, $pct);\n";
    }
}

// Ejecucion Grupos & Fuentes (we need to sum them up by grupo/fuente from ejecucion_detalle)
$grupos_sum = [];
$fuentes_sum = [];

// ejecucion_detalle: 0=id, 1=unidad, 2=grupo, 3=fuente, 4=tipo_registro, 5=anio, 6=vigente, 7=devengado, 8=saldo
foreach($ej_detalle as $ed) {
    $anio = intval($ed[5]);
    $vigente = floatval($ed[6]);
    $devengado = floatval($ed[7]);
    $saldo = floatval($ed[8]);
    
    // Grupo
    if ($ed[2] !== "''" && $ed[2] !== "") {
        $g_id = intval($ed[2]) + 3000;
        $key = "$g_id-$anio";
        if (!isset($grupos_sum[$key])) $grupos_sum[$key] = ['cat_id'=>$g_id, 'anio'=>$anio, 'vigente'=>0, 'devengado'=>0, 'saldo'=>0];
        $grupos_sum[$key]['vigente'] += $vigente;
        $grupos_sum[$key]['devengado'] += $devengado;
        $grupos_sum[$key]['saldo'] += $saldo;
    }
    
    // Fuente
    if ($ed[3] !== "''" && $ed[3] !== "") {
        $f_id = intval($ed[3]) + 4000;
        $key = "$f_id-$anio";
        if (!isset($fuentes_sum[$key])) $fuentes_sum[$key] = ['cat_id'=>$f_id, 'anio'=>$anio, 'vigente'=>0, 'devengado'=>0, 'saldo'=>0];
        $fuentes_sum[$key]['vigente'] += $vigente;
        $fuentes_sum[$key]['devengado'] += $devengado;
        $fuentes_sum[$key]['saldo'] += $saldo;
    }
}

foreach($grupos_sum as $gs) {
    $pct = $gs['vigente'] > 0 ? ($gs['devengado'] / $gs['vigente']) * 100 : 0;
    $pct = round($pct, 4);
    $out .= "INSERT INTO presupuesto_ejecucion (categoria_id, ejercicio_fiscal, asignado, modificado, vigente, devengado, saldo, pct_ejec) VALUES ({$gs['cat_id']}, {$gs['anio']}, 0, 0, {$gs['vigente']}, {$gs['devengado']}, {$gs['saldo']}, $pct);\n";
}

foreach($fuentes_sum as $fs) {
    $pct = $fs['vigente'] > 0 ? ($fs['devengado'] / $fs['vigente']) * 100 : 0;
    $pct = round($pct, 4);
    $out .= "INSERT INTO presupuesto_ejecucion (categoria_id, ejercicio_fiscal, asignado, modificado, vigente, devengado, saldo, pct_ejec) VALUES ({$fs['cat_id']}, {$fs['anio']}, 0, 0, {$fs['vigente']}, {$fs['devengado']}, {$fs['saldo']}, $pct);\n";
}

file_put_contents('c:\\xampp\\htdocs\\unificacion_maga\\Databases\\migracion_final.sql', $out);
echo "Migration script generated successfully in migracion_final.sql\n";
