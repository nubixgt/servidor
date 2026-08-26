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

$uniSheet = null;
foreach ($sheets as $sh) {
    if ($sh['name'] === 'UNI EJE') {
        $uniSheet = $sh;
        break;
    }
}

if (!$uniSheet) {
    die("Sheet UNI EJE not found\n");
}

$target = 'xl/' . $rels[$uniSheet['rid']];
$wsXml = $zip->getFromName($target);
$dom = new DOMDocument();
$dom->loadXML($wsXml);

$rows = $dom->getElementsByTagName('row');
$parsedRows = [];

// Helper functions matching PresupuestoService
function colValue(array $row, array $keys) {
    foreach ($keys as $key) {
        $keyL = strtolower(trim($key));
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
    $clean = str_replace(['Q', 'q', ',', '$', ' '], '', (string)$val);
    return (float)$clean;
}

// Read header
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
    if ($index === 0) continue; // Skip header
    
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
    
    // Construct the row as sent by frontend JSON (using headers as keys)
    $rowJson = [];
    foreach ($cells as $ref => $val) {
        $hName = $headers[$ref] ?? $ref;
        $rowJson[$hName] = $val;
    }
    
    // Normalize keys like PresupuestoService does
    $rowLower = [];
    foreach ($rowJson as $k => $v) {
        $rowLower[strtolower(trim((string)$k))] = $v;
    }
    
    $rawName = colValue($rowLower, ['progra uni gasto finan', 'unidad ejecutora', 'programa', 'grupo de gasto', 'fuente de financiamiento']);
    if (!$rawName) continue;
    $rawName = trim($rawName);
    if (stripos($rawName, 'total') !== false && strlen($rawName) < 20) continue;

    $asignado   = numCol($rowLower, [' asignado ', 'asignado']);
    $modificado = numCol($rowLower, [' modificado ', 'modificado']);
    $vigente    = numCol($rowLower, [' vigente ', 'vigente']);
    $devengado  = numCol($rowLower, [' devengado ', 'devengado']);
    $saldo      = numCol($rowLower, [' saldo por devengar ', 'saldo']);
    $pct_ejec   = numCol($rowLower, ['% ejecución']);
    
    if ($index === 5) {
        echo "DEBUG Row 6 rowLower:\n";
        print_r($rowLower);
    }
    
    echo "Row " . ($index + 1) . ": Name='$rawName'\n";
    echo "  -> ASIGNADO   = Excel: '" . ($cells['E'] ?? 'N/A') . "' | Parsed: $asignado\n";
    echo "  -> MODIFICADO = Excel: '" . ($cells['F'] ?? 'N/A') . "' | Parsed: $modificado\n";
    echo "  -> VIGENTE    = Excel: '" . ($cells['G'] ?? 'N/A') . "' | Parsed: $vigente\n";
    echo "  -> DEVENGADO  = Excel: '" . ($cells['H'] ?? 'N/A') . "' | Parsed: $devengado\n\n";
}

$zip->close();
