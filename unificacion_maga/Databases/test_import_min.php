<?php
$file = 'C:/Users/ANTHONY/Documents/ejecucion-presupuestaria/Copia de EP.xlsx';

$zip = new ZipArchive();
if ($zip->open($file) !== TRUE) {
    die("Cannot open file\n");
}

$sharedStrings = [];
$ssXml = $zip->getFromName('xl/sharedStrings.xml');
if ($ssXml) {
    $dom = new DOMDocument();
    $dom->loadXML($ssXml);
    foreach ($dom->getElementsByTagName('si') as $si) {
        $ts = $si->getElementsByTagName('t');
        $val = '';
        foreach ($ts as $t) $val .= $t->nodeValue;
        $sharedStrings[] = $val;
    }
}

$wbXml = $zip->getFromName('xl/workbook.xml');
$dom = new DOMDocument();
$dom->loadXML($wbXml);
$sheets = [];
foreach ($dom->getElementsByTagName('sheet') as $s) {
    $rid = $s->getAttribute('r:id');
    if (!$rid) {
        foreach ($s->attributes as $attr) {
            if (strpos($attr->name, ':id') !== false) { $rid = $attr->value; break; }
        }
    }
    $sheets[] = ['name' => $s->getAttribute('name'), 'rid' => $rid];
}

$relsXml = $zip->getFromName('xl/_rels/workbook.xml.rels');
$dom = new DOMDocument();
$dom->loadXML($relsXml);
$rels = [];
foreach ($dom->getElementsByTagName('Relationship') as $rel) {
    $rels[$rel->getAttribute('Id')] = $rel->getAttribute('Target');
}

$minSheet = null;
foreach ($sheets as $sh) {
    if (strcasecmp($sh['name'], 'MINISTERIOS') === 0) {
        $minSheet = $sh;
        break;
    }
}

$target = 'xl/' . $rels[$minSheet['rid']];
$wsXml = $zip->getFromName($target);
$dom = new DOMDocument();
$dom->loadXML($wsXml);

$rows = $dom->getElementsByTagName('row');

function colValue(array $row, array $keys) {
    foreach ($keys as $key) {
        $keyL = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $key));
        if (isset($row[$keyL]) && $row[$keyL] !== null && $row[$keyL] !== '') {
            return $row[$keyL];
        }
        if (strlen($keyL) > 2) {
            foreach ($row as $rKey => $rVal) {
                if (strpos($rKey, $keyL) !== false && $rVal !== null && $rVal !== '') {
                    return $rVal;
                }
            }
        }
    }
    return null;
}

function numCol(array $row, array $keys): float {
    $val = colValue($row, $keys);
    if ($val === null || $val === '') return 0.0;
    if (is_numeric($val)) return (float)$val;
    $clean = preg_replace('/[^\d\.\-]/', '', (string)$val);
    return (float)$clean;
}

function getMinistryCode($name) {
    $clean = mb_strtoupper(trim(preg_replace('/\s+/', ' ', $name)), 'UTF-8');
    
    $map = [
        'DEFENSA' => 'MDN',
        'DESARROLLO' => 'MDS',
        'RELACIONES' => 'MRE',
        'GOBERNACION' => 'MG',
        'GOBERNACIÓN' => 'MG',
        'TRABAJO' => 'MTPS',
        'EDUCACION' => 'ME',
        'EDUCACIÓN' => 'ME',
        'FINANZAS' => 'MFP',
        'ECONOMIA' => 'ME',
        'ECONOMÍA' => 'ME',
        'SALUD' => 'MSPAS',
        'AMBIENTE' => 'MARN',
        'AGRICULTURA' => 'MAGA',
        'ENERGIA' => 'MEM',
        'ENERGÍA' => 'MEM',
        'COMUNICACIONES' => 'MCIV',
        'CULTURA' => 'MCD'
    ];

    foreach ($map as $key => $code) {
        if (strpos($clean, $key) !== false) {
            return $code;
        }
    }

    return 'MIN-' . strtoupper(substr(preg_replace('/[^A-Z0-9]/i', '', $clean), 0, 10));
}

$headerRow = $rows->item(0);
$headers = [];
foreach ($headerRow->getElementsByTagName('c') as $cell) {
    $ref = preg_replace('/[0-9]/', '', $cell->getAttribute('r'));
    $t = $cell->getAttribute('t');
    $vEl = $cell->getElementsByTagName('v')->item(0);
    $val = '';
    if ($vEl) {
        $raw = $vEl->nodeValue;
        if ($t === 's') {
            $val = $sharedStrings[(int)$raw] ?? $raw;
        } else {
            $val = $raw;
        }
    }
    $headers[$ref] = $val;
}

foreach ($rows as $index => $row) {
    if ($index === 0) continue;
    
    $cells = [];
    foreach ($row->getElementsByTagName('c') as $cell) {
        $ref = preg_replace('/[0-9]/', '', $cell->getAttribute('r'));
        $t = $cell->getAttribute('t');
        $vEl = $cell->getElementsByTagName('v')->item(0);
        $val = '';
        if ($vEl) {
            $raw = $vEl->nodeValue;
            if ($t === 's') {
                $val = $sharedStrings[(int)$raw] ?? $raw;
            } else {
                $val = $raw;
            }
        }
        $cells[$ref] = $val;
    }
    
    if (!$cells || implode('', $cells) === '') continue;
    
    $rowJson = [];
    foreach ($cells as $ref => $val) {
        $hName = $headers[$ref] ?? $ref;
        $rowJson[$hName] = $val;
    }
    
    $rowLower = [];
    foreach ($rowJson as $k => $v) {
        $cleanKey = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', (string)$k));
        $rowLower[$cleanKey] = $v;
    }
    
    $rawName = colValue($rowLower, ['f', 'ministerio', 'nombre', 'descripcion']);
    if (!$rawName || is_numeric($rawName)) {
        echo "Row " . ($index + 1) . ": Skipped because rawName is empty or numeric. rawName='$rawName'\n";
        continue;
    }
    $rawName = trim($rawName);
    if (stripos($rawName, 'total') !== false && strlen($rawName) < 20) {
        echo "Row " . ($index + 1) . ": Skipped because name has 'total'. Name='$rawName'\n";
        continue;
    }

    $nombre = $rawName;
    $codigo = getMinistryCode($rawName);

    $asignado  = numCol($rowLower, [' asignado ', 'asignado']);
    $modificado = numCol($rowLower, [' modificado ', 'modificado']);
    $vigente   = numCol($rowLower, [' vigente ', 'vigente']);
    $devengado = numCol($rowLower, [' devengado ', 'devengado']);
    $saldo     = numCol($rowLower, [' saldo por devengar ', 'saldo']);

    echo "Row " . ($index + 1) . ": Name='$nombre' | Codigo='$codigo'\n";
    echo "  -> Asignado: $asignado | Modificado: $modificado | Vigente: $vigente | Devengado: $devengado | Saldo: $saldo\n\n";
}

$zip->close();
