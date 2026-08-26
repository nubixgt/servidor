<?php
$file = 'C:/Users/ANTHONY/Documents/ejecucion-presupuestaria/EP (2) (2).xlsx';

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

$target = 'xl/' . $rels[$uniSheet['rid']];
$wsXml = $zip->getFromName($target);
$dom = new DOMDocument();
$dom->loadXML($wsXml);

$rows = $dom->getElementsByTagName('row');

// Helpers
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

$mismatches = 0;
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
    
    $rawName = colValue($rowLower, ['progra uni gasto finan', 'unidad ejecutora', 'programa', 'grupo de gasto', 'fuente de financiamiento']);
    if (!$rawName) continue;
    $rawName = trim($rawName);
    if (stripos($rawName, 'total') !== false && strlen($rawName) < 20) continue;

    $asignado   = numCol($rowLower, [' asignado ', 'asignado']);
    $modificado = numCol($rowLower, [' modificado ', 'modificado']);
    $vigente    = numCol($rowLower, [' vigente ', 'vigente']);
    $devengado  = numCol($rowLower, [' devengado ', 'devengado']);

    // Check Excel values raw
    $rawAsig = (float)str_replace(['Q', 'q', ',', '$', ' '], '', $cells['E'] ?? '0');
    $rawMod  = (float)str_replace(['Q', 'q', ',', '$', ' '], '', $cells['F'] ?? '0');
    $rawVig  = (float)str_replace(['Q', 'q', ',', '$', ' '], '', $cells['G'] ?? '0');
    $rawDev  = (float)str_replace(['Q', 'q', ',', '$', ' '], '', $cells['H'] ?? '0');

    $rowNum = $index + 1;
    if (abs($asignado - $rawAsig) > 0.01 || abs($modificado - $rawMod) > 0.01 || abs($vigente - $rawVig) > 0.01 || abs($devengado - $rawDev) > 0.01) {
        echo "MISMATCH on Row $rowNum: Name='$rawName'\n";
        echo "  ASIGNADO: Parsed=$asignado vs Excel=$rawAsig\n";
        echo "  MODIFICADO: Parsed=$modificado vs Excel=$rawMod\n";
        echo "  VIGENTE: Parsed=$vigente vs Excel=$rawVig\n";
        echo "  DEVENGADO: Parsed=$devengado vs Excel=$rawDev\n\n";
        $mismatches++;
    }
}

echo "Total mismatches found: $mismatches\n";
$zip->close();
