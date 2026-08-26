<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Prueba el endpoint /search directamente en local
$apiBase = 'http://localhost/unificacion_maga/Backend/api/v1';

echo "=== TEST HTTP ENDPOINT /search ===\n\n";

$terms = ['sofia', 'guatemala', 'hernandez', 'congresista'];

foreach ($terms as $term) {
    $url = $apiBase . '/search?q=' . urlencode($term);
    echo "Buscando: '$term'\n";
    echo "URL: $url\n";

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json']);
    $resp = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);

    if ($err) {
        echo "❌ cURL error: $err\n";
    } else {
        echo "HTTP $httpCode: ";
        $data = json_decode($resp, true);
        if ($data && isset($data['status'])) {
            $count = count($data['data'] ?? []);
            echo "status={$data['status']}, resultados=$count\n";
            if ($count > 0) {
                echo "  Primer resultado: " . json_encode($data['data'][0]) . "\n";
            }
        } else {
            echo substr($resp, 0, 200) . "\n";
        }
    }
    echo "\n";
}

// También verificar con la URL de produccion
echo "=== TEST PRODUCCION ===\n\n";
$prodBase = 'https://maga.nubix.gt/Backend/api/v1';
$url = $prodBase . '/search?q=sofia';
echo "URL: $url\n";
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json']);
$resp = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$err = curl_error($ch);
curl_close($ch);
echo "HTTP $httpCode: " . ($err ? "Error: $err" : substr($resp, 0, 300)) . "\n";
