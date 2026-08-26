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

if (!$uniSheet) {
    die("Sheet UNI EJE not found\n");
}

$target = 'xl/' . $rels[$uniSheet['rid']];
$wsXml = $zip->getFromName($target);
$dom = new DOMDocument();
$dom->loadXML($wsXml);

$rows = $dom->getElementsByTagName('row');
$rowCount = 0;
foreach ($rows as $row) {
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
    if ($cells) {
        echo "Row $rowNum: L='" . ($cells['L'] ?? '') . "' | M='" . ($cells['M'] ?? '') . "'\n";
    }
    $rowCount++;
    if ($rowCount >= 50) break;
}
$zip->close();
