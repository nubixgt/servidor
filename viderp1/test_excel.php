<?php
require_once 'vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;

$file = 'VIDER 2025-4.xlsx';
$spreadsheet = IOFactory::load($file);
echo "Sheet names: " . implode(', ', $spreadsheet->getSheetNames()) . "\n";

$sheet = $spreadsheet->getSheetByName('DATOS');
if ($sheet) {
    echo "Found DATOS sheet\n";
    $highestCol = $sheet->getHighestColumn();
    $headers = [];
    for ($col = 'A'; $col <= $highestCol; $col++) {
        $val = $sheet->getCell($col . '1')->getValue();
        if ($val)
            $headers[] = $val;
    }
    echo "Headers:\n";
    foreach ($headers as $i => $h) {
        echo ($i + 1) . ". " . $h . "\n";
    }
} else {
    echo "DATOS sheet not found!\n";
}
