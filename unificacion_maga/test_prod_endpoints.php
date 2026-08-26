<?php
// Verificar que el SearchController esta registrado en produccion
// probando el endpoint directamente con curl sin token (debe dar 200 con data)

$tests = [
    // Ver version del index.php - si retorna 404 el /search no existe
    ['url' => 'https://maga.nubix.gt/Backend/api/v1/search?q=sofia',   'label' => '/search con sofia'],
    ['url' => 'https://maga.nubix.gt/Backend/api/v1/search?q=lopez',   'label' => '/search con lopez'],
    ['url' => 'https://maga.nubix.gt/Backend/api/v1/search?q=quetzal', 'label' => '/search con quetzal'],
    // Ruta que sabemos que funciona para comparar
    ['url' => 'https://maga.nubix.gt/Backend/api/v1/votaciones/congresistas?limit=3', 'label' => 'congresistas (control)'],
];

foreach ($tests as $test) {
    $ch = curl_init($test['url']);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_HTTPHEADER     => ['Accept: application/json'],
        CURLOPT_FOLLOWLOCATION => true,
    ]);
    $body     = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err      = curl_error($ch);
    curl_close($ch);

    echo "=== {$test['label']} ===\n";
    echo "HTTP: $httpCode\n";
    if ($err) { echo "Error: $err\n"; }
    $data = json_decode($body, true);
    if ($data) {
        echo "status: " . ($data['status'] ?? 'N/A') . "\n";
        $count = count($data['data'] ?? $data['results'] ?? []);
        echo "resultados: $count\n";
        if ($count > 0) {
            echo "Primero: " . json_encode(array_values($data['data'] ?? [])[0]) . "\n";
        }
    } else {
        echo "Respuesta raw: " . substr($body, 0, 200) . "\n";
    }
    echo "\n";
}
