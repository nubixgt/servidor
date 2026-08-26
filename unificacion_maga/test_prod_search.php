<?php
// Probar el endpoint de produccion con varios terminos y verificar exactamente que tablas y datos tiene
$prodBase = 'https://maga.nubix.gt/Backend/api/v1';

echo "=== DIAGNOSTICO PRODUCCION ===\n\n";

// Test con nombres reales que deberian existir
$queries = ['lopez', 'perez', 'garcia', 'san', 'quetzal'];

foreach ($queries as $q) {
    $url = $prodBase . '/search?q=' . urlencode($q);
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_HTTPHEADER => ['Accept: application/json']
    ]);
    $resp = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    $data = json_decode($resp, true);
    $count = count($data['data'] ?? []);
    echo "'$q' -> HTTP $httpCode, resultados: $count\n";
    if ($count > 0) {
        foreach ($data['data'] as $r) {
            echo "  [{$r['type']}] {$r['primary']}\n";
        }
    }
}

// Probar si SearchController esta registrado - ver si la ruta existe
echo "\n=== VERIFICAR RUTA /search EN PRODUCCION ===\n";
$url = $prodBase . '/search?q=test';
$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 10,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_HTTPHEADER => ['Accept: application/json'],
    CURLOPT_VERBOSE => false
]);
$resp = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
echo "HTTP: $httpCode\n";
echo "Respuesta completa: $resp\n";
