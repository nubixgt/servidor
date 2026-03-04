<?php
$files = glob("*.php");
$exclude = ['sidebar.php', 'config.php', 'auth.php', 'api_procesar.php', 'api-resumen.php', 'login.php', 'logout.php', 'generar_password.php', 'verificar.php', 'diagnostico.php', 'test_pdf.php', 'migrar.php'];

$sidebarPattern = '/<div class="col-lg-2 sidebar"[^>]*>.*?<\/nav>\s*<\/div>/is';

$jsFixPattern = '/<script>\s*\/\/\s*JavaScript para menú móvil responsive.*?<\/script>/is';

foreach ($files as $file) {
    if (in_array($file, $exclude)) continue;
    
    $content = file_get_contents($file);
    $modified = false;
    
    // Replace sidebar
    if (preg_match($sidebarPattern, $content)) {
        $content = preg_replace($sidebarPattern, '<?php include "sidebar.php"; ?>', $content, 1);
        $modified = true;
        echo "Sidebar replaced in $file\n";
    }
    
    // Replace inline JS with responsive.js
    if (preg_match($jsFixPattern, $content)) {
        $content = preg_replace($jsFixPattern, '<script src="responsive.js"></script>', $content, 1);
        $modified = true;
        echo "JS replaced in $file\n";
    }
    
    if ($modified) {
        file_put_contents($file, $content);
    }
}
?>
