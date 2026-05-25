<?php
header('Content-Type: application/json');

$uploadsDir = __DIR__ . '/uploads';
$ministrosDir = __DIR__ . '/uploads/ministros';
$documentosDir = __DIR__ . '/uploads/documentos';

$results = [
    'php_user' => posix_getpwuid(posix_geteuid())['name'] ?? 'unknown',
    'upload_max_filesize' => ini_get('upload_max_filesize'),
    'post_max_size' => ini_get('post_max_size'),
    'memory_limit' => ini_get('memory_limit'),
    'uploads_exists' => file_exists($uploadsDir),
    'uploads_writeable' => is_writable($uploadsDir),
    'ministros_exists' => file_exists($ministrosDir),
    'ministros_writeable' => is_writable($ministrosDir),
    'documentos_exists' => file_exists($documentosDir),
    'documentos_writeable' => is_writable($documentosDir),
];

// Try creating a test file in ministros
if (file_exists($ministrosDir) && is_writable($ministrosDir)) {
    $testFile = $ministrosDir . '/test_write.txt';
    $written = @file_put_contents($testFile, 'test');
    $results['test_file_write'] = ($written !== false);
    if ($written !== false) {
        @unlink($testFile);
    }
} else {
    // Try making the directory
    $created = @mkdir($ministrosDir, 0777, true);
    $results['created_ministros_dir'] = $created;
    if ($created) {
        $results['ministros_writeable_after_create'] = is_writable($ministrosDir);
    }
}

echo json_encode($results, JSON_PRETTY_PRINT);
