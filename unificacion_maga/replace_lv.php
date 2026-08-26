<?php
$file = 'Backend/src/Repositories/VISAR/LibreVentaRepository.php';
$content = file_get_contents($file);
$content = str_replace('visar_lv_siia', 'visar_libre_venta', $content);
file_put_contents($file, $content);
echo "Replaced in $file\n";
