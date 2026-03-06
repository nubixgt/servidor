<?php
$replacements = [
    'á' => 'á',
    'é' => 'é',
    'í' => 'í',
    'ó' => 'ó',
    'ú' => 'ú',
    'ñ' => 'ñ',
    'Í' => 'Á',
    'É' => 'É',
    'Í' => 'Í', // Note: 'Í' can be Í
    'Ó' => 'Ó',
    'Ú' => 'Ú',
    'Ñ' => 'Ñ',
    'ü' => 'ü',
    'Ü' => 'Ü',
    '¿' => '¿',
    '¡' => '¡',
    '°' => '°',
    'º' => 'º',
    'TÍTULO' => 'TÍTULO',
];

// Re-adjust uppercase I mapping, as "Í" is used above, but typically Í is "Í"
$replacements['Í'] = 'Í'; // hex C3 8D

$directory = 'c:/xampp/htdocs/servidor/SistemaVotaciones';

$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory));
$filesFixed = 0;

foreach ($iterator as $file) {
    if ($file->isDir()) continue;
    
    $ext = pathinfo($file->getFilename(), PATHINFO_EXTENSION);
    if (!in_array($ext, ['php', 'js', 'html', 'md', 'css'])) continue;
    
    $filepath = $file->getPathname();
    $content = file_get_contents($filepath);
    
    if ($content === false) continue;
    
    $new_content = $content;
    foreach ($replacements as $wrong => $right) {
        $new_content = str_replace($wrong, $right, $new_content);
    }
    
    if ($new_content !== $content) {
        echo "Fixed encoding in: " . $filepath . "\n";
        file_put_contents($filepath, $new_content);
        $filesFixed++;
    }
}

echo "Total files fixed: $filesFixed\n";
?>
