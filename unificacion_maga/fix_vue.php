<?php
$dir = __DIR__ . '/Frontend/src/views/admin/visan';

$replacements = [
    "import axios from 'axios'" => "import api from '@/services/api'",
    "axios.get(import.meta.env.VITE_API_BASE + '/index.php'" => "api.get('/visan/dashboard'",
    "axios.get(import.meta.env.VITE_API_BASE + '/tabla_datos.php'" => "api.get('/visan/tabla'",
    "axios.get(import.meta.env.VITE_API_BASE + '/historial_cambios.php'" => "api.get('/visan/historial'",
    "axios.post(import.meta.env.VITE_API_BASE + '/editar_datos.php'" => "api.post('/visan/editar'",
    "axios.get(import.meta.env.VITE_API_BASE + '/editar_datos.php'" => "api.get('/visan/editar'",
    "axios.post(import.meta.env.VITE_API_BASE + '/importar_excel.php'" => "api.post('/visan/importar'"
];

$files = glob($dir . '/*.vue');
foreach ($files as $file) {
    $content = file_get_contents($file);
    foreach ($replacements as $old => $new) {
        $content = str_replace($old, $new, $content);
    }
    file_put_contents($file, $content);
    echo "Fixed " . basename($file) . "\n";
}
