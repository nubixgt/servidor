<?php
/**
 * Simple PSR-4 Autoloader
 * Map namespace 'App\' to 'src/'
 */

spl_autoload_register(function ($class) {
    // Project-specific namespace prefix
    $prefix = 'App\\';
    $base_dir = __DIR__ . '/src/';

    // Smalot PdfParser Support
    $smalotPrefix = 'Smalot\\PdfParser\\';
    $smalotBaseDir = __DIR__ . '/src/Libs/PdfParser/src/Smalot/PdfParser/';

    // Does the class use the App\ namespace prefix?
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) === 0) {
        $relative_class = substr($class, $len);
        $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
        if (file_exists($file)) {
            require $file;
        }
        return;
    }

    // Does the class use the Smalot\PdfParser namespace prefix?
    $smalotLen = strlen($smalotPrefix);
    if (strncmp($smalotPrefix, $class, $smalotLen) === 0) {
        $relative_class = substr($class, $smalotLen);
        $file = $smalotBaseDir . str_replace('\\', '/', $relative_class) . '.php';
        if (file_exists($file)) {
            require $file;
        }
        return;
    }
});
