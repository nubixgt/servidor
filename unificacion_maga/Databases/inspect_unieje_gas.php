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

$gasSheet = null;
foreach ($sheets as $sh) {
    if ($sh['name'] === 'UniEjeYGru_Gas') {
        $gasSheet = $sh;
        break;
    }
}

if (!$gasSheet) {
    die("Sheet UniEjeYGru_Gas not found\n");
}

$target = 'xl/' . $rels[$gasSheet['rid']];
$wsXml = $zip->getFromName($target);
$dom = new DOMDocument();
$dom->loadXML($wsXml);

$rows = $dom->getElementsByTagName('row');
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
    $rowNum = (int)$row->getAttribute('r');
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
    
    if (in_array('1740300', $cells) || in_array('1740300.0', $cells) || in_array('1740300.00', $cells)) {
        echo "Found 1740300 on Row $rowNum:\n";
        foreach ($cells as $k => $v) {
            $h = $headers[$k] ?? $k;
            echo "  $h ($k) = '$v'\n";
        }
        echo "\n";
    }
}

$zip->close();
